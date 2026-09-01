import { spawn } from 'node:child_process';
import { mkdir, mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8081').replace(/\/$/, '');
const email = process.env.BROWSER_EMAIL ?? 'task010-browser@example.test';
const password = process.env.BROWSER_PASSWORD ?? '';
const chromiumPath = process.env.CHROMIUM_PATH ?? '/usr/bin/chromium';
const evidenceDir = process.env.EVIDENCE_DIR ?? 'evidence/w02-library';
const productSha = process.env.W02_PRODUCT_SHA ?? null;
const evidenceSha = process.env.GITHUB_SHA ?? null;
const objectPath = '/knowledge?object=KU-W02-VISUAL';
const viewports = [
  { name: 'desktop-1440', width: 1440, height: 1000 },
  { name: 'medium-1024', width: 1024, height: 900 },
];

if (password.length < 20) throw new Error('BROWSER_PASSWORD must be a synthetic value of at least 20 characters.');
await mkdir(join(evidenceDir, 'screenshots'), { recursive: true });

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function waitForJson(url, timeoutMs = 30000) {
  const started = Date.now();
  while (Date.now() - started < timeoutMs) {
    try {
      const response = await fetch(url);
      if (response.ok) return await response.json();
    } catch {}
    await delay(250);
  }
  throw new Error(`Timed out waiting for ${url}`);
}

class CdpClient {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.nextId = 1;
    this.pending = new Map();
    this.listeners = new Map();
    this.ready = new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });
    this.ws.addEventListener('message', (event) => {
      const message = JSON.parse(event.data);
      if (message.id && this.pending.has(message.id)) {
        const { resolve, reject } = this.pending.get(message.id);
        this.pending.delete(message.id);
        if (message.error) reject(new Error(`${message.error.message} (${message.error.code})`));
        else resolve(message.result ?? {});
        return;
      }
      const key = `${message.sessionId ?? 'browser'}:${message.method}`;
      for (const listener of this.listeners.get(key) ?? []) listener(message.params ?? {});
    });
  }

  async send(method, params = {}, sessionId = undefined) {
    await this.ready;
    const id = this.nextId++;
    return await new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
  }

  on(method, listener, sessionId = undefined) {
    const key = `${sessionId ?? 'browser'}:${method}`;
    const listeners = this.listeners.get(key) ?? [];
    listeners.push(listener);
    this.listeners.set(key, listeners);
    return () => this.listeners.set(key, (this.listeners.get(key) ?? []).filter((item) => item !== listener));
  }

  close() { this.ws.close(); }
}

async function evaluate(client, sessionId, expression) {
  const result = await client.send('Runtime.evaluate', {
    expression,
    awaitPromise: true,
    returnByValue: true,
    userGesture: true,
  }, sessionId);
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.text ?? 'Runtime evaluation failed.');
  return result.result?.value;
}

async function waitForCondition(client, sessionId, expression, timeoutMs = 20000) {
  const started = Date.now();
  while (Date.now() - started < timeoutMs) {
    if (await evaluate(client, sessionId, expression)) return;
    await delay(200);
  }
  throw new Error(`Condition timed out: ${expression}`);
}

let chromium;
let profileDir;
let client;
const results = [];
const consoleErrors = [];
const networkFailures = [];

try {
  profileDir = await mkdtemp(join(tmpdir(), 'cep-w02-library-'));
  chromium = spawn(chromiumPath, [
    '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-background-networking',
    '--disable-default-apps', '--disable-extensions', '--disable-sync', '--metrics-recording-only',
    '--no-first-run', '--hide-scrollbars', '--remote-debugging-address=127.0.0.1',
    '--remote-debugging-port=9222', `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: ['ignore', 'pipe', 'pipe'] });

  const version = await waitForJson('http://127.0.0.1:9222/json/version');
  client = new CdpClient(version.webSocketDebuggerUrl);
  await client.ready;
  const { targetId } = await client.send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await client.send('Target.attachToTarget', { targetId, flatten: true });
  await Promise.all([
    client.send('Page.enable', {}, sessionId),
    client.send('Runtime.enable', {}, sessionId),
    client.send('Network.enable', {}, sessionId),
    client.send('Log.enable', {}, sessionId),
  ]);

  client.on('Log.entryAdded', ({ entry }) => {
    if (entry?.level === 'error') consoleErrors.push({ source: entry.source, text: entry.text, url: entry.url });
  }, sessionId);
  client.on('Runtime.exceptionThrown', ({ exceptionDetails }) => {
    consoleErrors.push({ source: 'runtime', text: exceptionDetails?.text ?? 'Unhandled runtime exception' });
  }, sessionId);
  client.on('Network.loadingFailed', (event) => {
    if (!event.canceled && event.errorText !== 'net::ERR_ABORTED') {
      networkFailures.push({ errorText: event.errorText, type: event.type });
    }
  }, sessionId);

  async function navigate(pathname) {
    const loaded = new Promise((resolve) => {
      const off = client.on('Page.loadEventFired', () => { off(); resolve(); }, sessionId);
    });
    await client.send('Page.navigate', { url: `${baseUrl}${pathname}` }, sessionId);
    await Promise.race([loaded, delay(20000).then(() => { throw new Error(`Load timed out: ${pathname}`); })]);
    await waitForCondition(client, sessionId, 'document.readyState === "complete"');
    await delay(650);
  }

  async function setViewport(viewport) {
    await client.send('Emulation.setDeviceMetricsOverride', {
      width: viewport.width,
      height: viewport.height,
      screenWidth: viewport.width,
      screenHeight: viewport.height,
      deviceScaleFactor: 1,
      mobile: false,
    }, sessionId);
  }

  async function screenshot(name) {
    const shot = await client.send('Page.captureScreenshot', {
      format: 'png',
      captureBeyondViewport: true,
    }, sessionId);
    const relative = `screenshots/${name}.png`;
    await writeFile(join(evidenceDir, relative), Buffer.from(shot.data, 'base64'));
    return relative;
  }

  await setViewport(viewports[0]);
  await navigate('/login');
  await evaluate(client, sessionId, `(() => {
    const setValue = (selector, value) => {
      const input = document.querySelector(selector);
      if (!input) throw new Error('Missing login input: ' + selector);
      const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
      setter.call(input, value);
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    setValue('#email', ${JSON.stringify(email)});
    setValue('#password', ${JSON.stringify(password)});
    const form = document.querySelector('form');
    if (!form) throw new Error('Missing login form.');
    form.requestSubmit();
    return true;
  })()`);
  await waitForCondition(client, sessionId, `location.pathname !== '/login'`, 30000);

  for (const viewport of viewports) {
    await setViewport(viewport);
    await navigate(objectPath);
    await waitForCondition(client, sessionId, `document.querySelector('article.library-document') !== null`);

    const baseState = await evaluate(client, sessionId, `(() => {
      const text = document.body?.innerText ?? '';
      return {
        pathname: location.pathname,
        title: document.title,
        dir: document.documentElement.dir || getComputedStyle(document.documentElement).direction,
        lang: document.documentElement.lang,
        overflowX: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
        arabicVisible: /[\u0600-\u06FF]/.test(text),
        englishVisible: text.includes('Identity and Access Management'),
        technicalVisible: text.includes('curl -I https://example.test/reference'),
        technicalLtrCount: document.querySelectorAll('textarea[dir="ltr"], [dir="ltr"], code, pre').length,
        revisionLabelVisible: text.includes('v3'),
        documentPresent: Boolean(document.querySelector('article.library-document')),
        editorPresent: Boolean(document.querySelector('#knowledge-editor')),
      };
    })()`);
    const baseScreenshot = await screenshot(`${viewport.name}-library-base`);

    const historyOpened = await evaluate(client, sessionId, `(() => {
      const button = [...document.querySelectorAll('button')].find((item) => (item.textContent ?? '').includes('السجل'));
      if (!button) return false;
      button.click();
      return true;
    })()`);
    await delay(400);
    const historyState = await evaluate(client, sessionId, `(() => ({
      expanded: [...document.querySelectorAll('button')].some((item) => (item.textContent ?? '').includes('السجل') && item.getAttribute('aria-expanded') === 'true'),
      historyLabelVisible: (document.body?.innerText ?? '').includes('سجل المراجعات'),
      revisionOneVisible: (document.body?.innerText ?? '').includes('v1'),
      revisionTwoVisible: (document.body?.innerText ?? '').includes('v2'),
      revisionThreeVisible: (document.body?.innerText ?? '').includes('v3'),
    }))()`);
    const historyScreenshot = historyOpened ? await screenshot(`${viewport.name}-library-history`) : null;

    await navigate(objectPath);
    await waitForCondition(client, sessionId, `document.querySelector('article.library-document') !== null`);
    const foldResult = await evaluate(client, sessionId, `(() => {
      const button = document.querySelector('button[aria-label="طي القسم"]');
      if (!button) return { available: false, collapsed: false };
      button.click();
      return {
        available: true,
        collapsed: button.getAttribute('aria-expanded') === 'false' || button.getAttribute('aria-label') === 'توسيع القسم',
      };
    })()`);
    await delay(300);
    const foldedState = await evaluate(client, sessionId, `(() => ({
      expandControlVisible: Boolean(document.querySelector('button[aria-label="توسيع القسم"]')),
      hiddenEditorBlocks: [...document.querySelectorAll('.library-editor-block')].filter((el) => getComputedStyle(el).display === 'none').length,
    }))()`);
    const foldedScreenshot = foldResult.available ? await screenshot(`${viewport.name}-library-folded`) : null;

    results.push({
      viewport,
      baseState,
      baseScreenshot,
      historyOpened,
      historyState,
      historyScreenshot,
      foldResult,
      foldedState,
      foldedScreenshot,
    });
  }

  const failures = [];
  for (const item of results) {
    if (!item.baseState.documentPresent || !item.baseState.editorPresent) failures.push(`${item.viewport.name}:library-document-or-editor-missing`);
    if (!item.baseState.arabicVisible || !item.baseState.englishVisible || !item.baseState.technicalVisible) failures.push(`${item.viewport.name}:mixed-content-missing`);
    if (item.baseState.technicalLtrCount < 1) failures.push(`${item.viewport.name}:technical-ltr-island-missing`);
    if (item.baseState.overflowX) failures.push(`${item.viewport.name}:horizontal-overflow`);
    if (!item.historyOpened || !item.historyState.expanded || !item.historyState.historyLabelVisible) failures.push(`${item.viewport.name}:history-state-not-visible`);
    if (!item.foldResult.available || !item.foldResult.collapsed || !item.foldedState.expandControlVisible) failures.push(`${item.viewport.name}:fold-state-not-visible`);
  }
  if (consoleErrors.length) failures.push('console-errors');
  if (networkFailures.length) failures.push('network-failures');

  const output = {
    schema: 'cep.w02-library-visual-evidence.v1',
    status: failures.length ? 'FAIL' : 'PASS',
    productCandidateSha: productSha,
    evidenceHarnessSha: evidenceSha,
    applicationBytesChangedByHarness: false,
    browser: version.Browser,
    route: objectPath,
    results,
    consoleErrors,
    networkFailures,
    failures,
  };
  await writeFile(join(evidenceDir, 'w02-library-visual-evidence.json'), `${JSON.stringify(output, null, 2)}\n`);
  if (failures.length) throw new Error(`W02 Library visual evidence failed: ${failures.join(', ')}`);
} finally {
  try { client?.close(); } catch {}
  if (chromium && chromium.exitCode === null) chromium.kill('SIGTERM');
  if (profileDir) await rm(profileDir, { recursive: true, force: true });
}
