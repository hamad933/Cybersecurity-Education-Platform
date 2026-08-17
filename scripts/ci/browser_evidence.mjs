import { spawn } from 'node:child_process';
import { mkdir, mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8081').replace(/\/$/, '');
const email = process.env.BROWSER_EMAIL ?? 'task010-browser@example.test';
const password = process.env.BROWSER_PASSWORD ?? '';
const evidenceDir = process.env.EVIDENCE_DIR ?? 'evidence/browser';
const chromiumPath = process.env.CHROMIUM_PATH ?? '/usr/bin/chromium';

if (password.length < 20) throw new Error('BROWSER_PASSWORD must be a synthetic value of at least 20 characters.');
await mkdir(join(evidenceDir, 'screenshots'), { recursive: true });
const profileDir = await mkdtemp(join(tmpdir(), 'cep-browser-'));
const browserLog = [];
let chromium;

const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

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
    this.ws.addEventListener('message', event => {
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
    return () => this.listeners.set(key, (this.listeners.get(key) ?? []).filter(item => item !== listener));
  }

  close() { this.ws.close(); }
}

async function evaluate(client, sessionId, expression) {
  const result = await client.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true, userGesture: true }, sessionId);
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

function safeSlug(pathname) {
  return pathname === '/' ? 'dashboard' : pathname.replace(/^\//, '').replace(/[^a-zA-Z0-9]+/g, '-').replace(/-+$/, '');
}

try {
  chromium = spawn(chromiumPath, [
    '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-background-networking',
    '--disable-default-apps', '--disable-extensions', '--disable-sync', '--metrics-recording-only',
    '--no-first-run', '--hide-scrollbars', '--remote-debugging-address=127.0.0.1',
    '--remote-debugging-port=9222', `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: ['ignore', 'pipe', 'pipe'] });
  chromium.stdout.on('data', chunk => browserLog.push(chunk.toString()));
  chromium.stderr.on('data', chunk => browserLog.push(chunk.toString()));

  const version = await waitForJson('http://127.0.0.1:9222/json/version');
  const client = new CdpClient(version.webSocketDebuggerUrl);
  await client.ready;
  const { targetId } = await client.send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await client.send('Target.attachToTarget', { targetId, flatten: true });
  await Promise.all([
    client.send('Page.enable', {}, sessionId), client.send('Runtime.enable', {}, sessionId),
    client.send('Network.enable', {}, sessionId), client.send('Log.enable', {}, sessionId),
    client.send('Accessibility.enable', {}, sessionId),
  ]);

  let consoleEntries = [];
  let networkFailures = [];
  let documentResponse = null;
  client.on('Log.entryAdded', ({ entry }) => {
    if (entry?.level === 'error') consoleEntries.push({ source: entry.source, text: entry.text, url: entry.url, lineNumber: entry.lineNumber });
  }, sessionId);
  client.on('Runtime.exceptionThrown', ({ exceptionDetails }) => {
    consoleEntries.push({ source: 'runtime', text: exceptionDetails?.text ?? 'Unhandled runtime exception' });
  }, sessionId);
  client.on('Network.loadingFailed', event => {
    if (!event.canceled && event.errorText !== 'net::ERR_ABORTED') networkFailures.push({ requestId: event.requestId, errorText: event.errorText, type: event.type });
  }, sessionId);
  client.on('Network.responseReceived', ({ response, type }) => {
    if (type === 'Document') documentResponse = { url: response.url, status: response.status, headers: response.headers };
  }, sessionId);

  async function navigate(pathname) {
    consoleEntries = [];
    networkFailures = [];
    documentResponse = null;
    const url = `${baseUrl}${pathname}`;
    const loaded = new Promise(resolve => {
      const off = client.on('Page.loadEventFired', () => { off(); resolve(); }, sessionId);
    });
    await client.send('Page.navigate', { url }, sessionId);
    await Promise.race([loaded, delay(20000).then(() => { throw new Error(`Load timed out: ${url}`); })]);
    await waitForCondition(client, sessionId, 'document.readyState === "complete"');
    await delay(500);
  }

  async function setViewport(viewport) {
    await client.send('Emulation.setDeviceMetricsOverride', {
      width: viewport.width, height: viewport.height, deviceScaleFactor: 1, mobile: viewport.mobile,
      screenWidth: viewport.width, screenHeight: viewport.height,
    }, sessionId);
  }

  await setViewport({ width: 1440, height: 1000, mobile: false });
  await navigate('/login');
  const loginScreenshot = await client.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true }, sessionId);
  await writeFile(join(evidenceDir, 'screenshots', 'desktop-login.png'), Buffer.from(loginScreenshot.data, 'base64'));

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
  await delay(750);

  const pages = [
    '/', '/release/',
    '/vs001/sources', '/vs001/lesson', '/vs001/lab', '/vs001/evidence',
    '/vs002/sources', '/vs002/lesson', '/vs002/lab', '/vs002/evidence',
    '/vs003/lab',
  ];
  const viewports = [
    { name: 'desktop', width: 1440, height: 1000, mobile: false },
    { name: 'mobile', width: 390, height: 844, mobile: true },
  ];
  const pageResults = [];

  for (const viewport of viewports) {
    await setViewport(viewport);
    for (const pathname of pages) {
      await navigate(pathname);
      const currentPath = await evaluate(client, sessionId, 'location.pathname');
      if (currentPath === '/login') throw new Error(`Authentication was not retained for ${pathname}.`);

      const dom = await evaluate(client, sessionId, `(() => {
        const interactive = document.querySelector('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (interactive) interactive.focus();
        const style = interactive ? getComputedStyle(interactive) : null;
        const bodyText = document.body?.innerText ?? '';
        return {
          title: document.title,
          lang: document.documentElement.lang,
          dir: document.documentElement.dir || getComputedStyle(document.documentElement).direction,
          arabicVisible: /[\u0600-\u06FF]/.test(bodyText),
          technicalLtrCount: document.querySelectorAll('[dir="ltr"], code, pre, .direction-ltr').length,
          rtlCount: document.querySelectorAll('[dir="rtl"]').length,
          focus: interactive ? { tag: interactive.tagName, id: interactive.id, outlineStyle: style.outlineStyle, outlineWidth: style.outlineWidth, boxShadow: style.boxShadow } : null,
          overflowX: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
          pathname: location.pathname,
        };
      })()`);
      const ax = await client.send('Accessibility.getFullAXTree', {}, sessionId);
      const unnamedInteractive = (ax.nodes ?? []).filter(node => {
        const role = node.role?.value;
        return ['button', 'link', 'textbox', 'combobox', 'checkbox', 'radio', 'switch'].includes(role) && !(node.name?.value ?? '').trim() && !node.ignored;
      }).map(node => ({ role: node.role?.value, backendDOMNodeId: node.backendDOMNodeId }));

      const shot = await client.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true }, sessionId);
      const slug = `${viewport.name}-${safeSlug(pathname)}`;
      await writeFile(join(evidenceDir, 'screenshots', `${slug}.png`), Buffer.from(shot.data, 'base64'));

      const headers = Object.fromEntries(Object.entries(documentResponse?.headers ?? {}).map(([key, value]) => [key.toLowerCase(), String(value)]));
      const securityHeaders = {
        'content-security-policy': headers['content-security-policy'] ?? null,
        'x-content-type-options': headers['x-content-type-options'] ?? null,
        'x-frame-options': headers['x-frame-options'] ?? null,
        'referrer-policy': headers['referrer-policy'] ?? null,
        'permissions-policy': headers['permissions-policy'] ?? null,
      };
      const focusVisible = Boolean(dom.focus && ((dom.focus.outlineStyle !== 'none' && dom.focus.outlineWidth !== '0px') || dom.focus.boxShadow !== 'none'));
      pageResults.push({
        viewport: viewport.name, path: pathname, status: documentResponse?.status ?? null, dom, focusVisible,
        consoleErrors: consoleEntries, failedNetworkRequests: networkFailures,
        accessibility: { nodeCount: ax.nodes?.length ?? 0, unnamedInteractive }, securityHeaders,
        screenshot: `screenshots/${slug}.png`,
      });
    }
  }

  const requiredHeaders = ['content-security-policy', 'x-content-type-options', 'x-frame-options', 'referrer-policy'];
  const failures = [];
  for (const page of pageResults) {
    if (page.status !== 200) failures.push(`${page.viewport}:${page.path}:http-${page.status}`);
    if (page.dom.lang !== 'ar') failures.push(`${page.viewport}:${page.path}:lang-not-ar`);
    if (page.dom.dir !== 'rtl') failures.push(`${page.viewport}:${page.path}:dir-not-rtl`);
    if (!page.dom.arabicVisible) failures.push(`${page.viewport}:${page.path}:arabic-not-visible`);
    if (!page.focusVisible) failures.push(`${page.viewport}:${page.path}:focus-not-visible`);
    if (page.dom.overflowX) failures.push(`${page.viewport}:${page.path}:document-overflow-x`);
    if (page.consoleErrors.length) failures.push(`${page.viewport}:${page.path}:console-errors`);
    if (page.failedNetworkRequests.length) failures.push(`${page.viewport}:${page.path}:network-failures`);
    if (page.accessibility.unnamedInteractive.length) failures.push(`${page.viewport}:${page.path}:unnamed-interactive`);
    for (const header of requiredHeaders) if (!page.securityHeaders[header]) failures.push(`${page.viewport}:${page.path}:missing-${header}`);
  }
  if (!pageResults.some(page => page.dom.technicalLtrCount > 0)) failures.push('technical-ltr-content-not-detected');

  const result = {
    schema: 'cep.browser-evidence.v1', browser: version.Browser, protocolVersion: version['Protocol-Version'], baseUrl,
    status: failures.length ? 'FAIL' : 'PASS', authenticatedFlow: { email, passwordRecorded: false, result: 'PASS' },
    pages: pageResults, failures,
  };
  await writeFile(join(evidenceDir, 'browser-result.json'), JSON.stringify(result, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'accessibility-result.json'), JSON.stringify({
    schema: 'cep.accessibility-evidence.v1',
    status: failures.some(item => item.includes('focus') || item.includes('overflow') || item.includes('unnamed') || item.includes('lang-') || item.includes('dir-')) ? 'FAIL' : 'PASS',
    pages: pageResults.map(({ viewport, path, dom, focusVisible, accessibility }) => ({ viewport, path, lang: dom.lang, dir: dom.dir, overflowX: dom.overflowX, focusVisible, accessibility })),
  }, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'security-header-snapshot.json'), JSON.stringify({
    schema: 'cep.security-headers.v1', pages: pageResults.map(({ viewport, path, status, securityHeaders }) => ({ viewport, path, status, securityHeaders })),
  }, null, 2) + '\n');

  if (failures.length) {
    const diagnostics = await evaluate(client, sessionId, `({ url: location.href, title: document.title, html: document.documentElement.outerHTML.slice(0, 200000) })`);
    await writeFile(join(evidenceDir, 'failure-diagnostic.json'), JSON.stringify({ failures, diagnostics }, null, 2) + '\n');
    throw new Error(`Browser evidence failed: ${failures.join(', ')}`);
  }
  client.close();
} catch (error) {
  await writeFile(join(evidenceDir, 'browser-harness-error.json'), JSON.stringify({
    schema: 'cep.browser-harness-error.v1', status: 'FAIL', message: error instanceof Error ? error.message : String(error),
  }, null, 2) + '\n');
  throw error;
} finally {
  chromium?.kill('SIGTERM');
  await writeFile(join(evidenceDir, 'chromium-sanitized.log'), browserLog.join('').replaceAll(password, '[REDACTED]'));
  await rm(profileDir, { recursive: true, force: true });
}
