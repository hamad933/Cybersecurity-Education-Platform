import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8081').replace(/\/$/, '');
const email = process.env.BROWSER_EMAIL ?? 'task010-browser@example.test';
const password = process.env.BROWSER_PASSWORD ?? '';
const evidenceDir = process.env.EVIDENCE_DIR ?? 'evidence/r03-exact';
const chromiumPath = process.env.CHROMIUM_PATH ?? '/usr/bin/chromium';
const routeProfilePath = process.env.BROWSER_ROUTE_PROFILE_FILE ?? 'candidate/scripts/ci/browser_route_profiles.json';
const laneProfile = process.env.LANE_PROFILE ?? '';
const expectedSha = process.env.EXPECTED_CANDIDATE_SHA ?? '';
const actualSha = process.env.ACTUAL_CANDIDATE_SHA ?? '';
const candidateBranch = process.env.CANDIDATE_BRANCH ?? '';
const githubRunId = process.env.GITHUB_RUN_ID ?? null;
const githubRunAttempt = process.env.GITHUB_RUN_ATTEMPT ?? null;
const githubJob = process.env.GITHUB_JOB ?? null;
const githubWorkflow = process.env.GITHUB_WORKFLOW ?? null;
const runnerOs = process.env.RUNNER_OS ?? null;
const runnerArch = process.env.RUNNER_ARCH ?? null;

const viewports = [
  { name: 'desktop-1440', width: 1440, height: 1000, mobile: false },
  { name: 'tablet-768', width: 768, height: 1024, mobile: false },
  { name: 'narrow-430', width: 430, height: 932, mobile: true },
  { name: 'mobile-390', width: 390, height: 844, mobile: true },
];

if (!laneProfile) throw new Error('LANE_PROFILE is required.');
if (!expectedSha || !actualSha || expectedSha !== actualSha) {
  throw new Error(`Candidate SHA mismatch: expected=${expectedSha || 'missing'} actual=${actualSha || 'missing'}`);
}
if (password.length < 20) throw new Error('BROWSER_PASSWORD must be an ephemeral synthetic value of at least 20 characters.');

await mkdir(join(evidenceDir, 'screenshots'), { recursive: true });

const browserLog = [];
const pageResults = [];
const screenshotManifest = [];
let chromium;
let profileDir;

const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
const sha256 = buffer => createHash('sha256').update(buffer).digest('hex');

function parsePng(buffer) {
  const sig = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
  if (buffer.length < 24 || !buffer.subarray(0, 8).equals(sig)) {
    return { mediaType: 'UNKNOWN', width: null, height: null };
  }
  return {
    mediaType: 'image/png',
    width: buffer.readUInt32BE(16),
    height: buffer.readUInt32BE(20),
  };
}

function safeSlug(pathname) {
  return pathname === '/' ? 'root' : pathname.replace(/^\//, '').replace(/[^a-zA-Z0-9]+/g, '-').replace(/-+$/, '');
}

async function loadRoutes() {
  const parsed = JSON.parse(await readFile(routeProfilePath, 'utf8'));
  if (parsed?.schema !== 'cep.browser-route-profiles.v1') throw new Error(`Unsupported route profile schema: ${parsed?.schema}`);
  if (!parsed.profiles?.[laneProfile]) throw new Error(`Missing route profile ${laneProfile}`);
  const routes = parsed.profiles[laneProfile].routes;
  if (!Array.isArray(routes) || routes.length === 0) throw new Error(`No routes for ${laneProfile}`);
  for (const route of routes) {
    if (typeof route !== 'string' || !route.startsWith('/') || /[\s?#]/.test(route)) throw new Error(`Malformed route: ${route}`);
  }
  return { schema: parsed.schema, description: parsed.profiles[laneProfile].description, routes };
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

async function evaluate(client, sessionId, expression) {
  const result = await client.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true, userGesture: true }, sessionId);
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.text ?? 'Runtime evaluation failed.');
  return result.result?.value;
}

async function waitForCondition(client, sessionId, expression, timeoutMs = 30000) {
  const started = Date.now();
  while (Date.now() - started < timeoutMs) {
    if (await evaluate(client, sessionId, expression)) return;
    await delay(200);
  }
  throw new Error(`Condition timed out: ${expression}`);
}

async function saveScreenshot(client, sessionId, relativePath, expectedViewport = null, captureBeyondViewport = false) {
  const shot = await client.send('Page.captureScreenshot', { format: 'png', fromSurface: true, captureBeyondViewport }, sessionId);
  const bytes = Buffer.from(shot.data, 'base64');
  const parsed = parsePng(bytes);
  const absolutePath = join(evidenceDir, relativePath);
  await writeFile(absolutePath, bytes);
  const record = {
    file: relativePath.replaceAll('\\', '/'),
    mediaType: parsed.mediaType,
    width: parsed.width,
    height: parsed.height,
    sha256: sha256(bytes),
    bytes: bytes.length,
    captureBeyondViewport,
    expectedViewport,
    exactViewportDimensions: expectedViewport
      ? parsed.width === expectedViewport.width && parsed.height === expectedViewport.height
      : null,
  };
  screenshotManifest.push(record);
  if (expectedViewport && !captureBeyondViewport && !record.exactViewportDimensions) {
    throw new Error(`Screenshot dimension mismatch for ${relativePath}: expected ${expectedViewport.width}x${expectedViewport.height}, got ${parsed.width}x${parsed.height}`);
  }
  if (parsed.mediaType !== 'image/png') throw new Error(`Screenshot is not a PNG: ${relativePath}`);
  return record;
}

const profile = await loadRoutes();
let client;
let sessionId;
let mainError = null;

try {
  profileDir = await mkdtemp(join(tmpdir(), `cep-r03-${laneProfile.toLowerCase()}-`));
  chromium = spawn(chromiumPath, [
    '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-background-networking',
    '--disable-default-apps', '--disable-extensions', '--disable-sync', '--metrics-recording-only',
    '--no-first-run', '--hide-scrollbars', '--remote-debugging-address=127.0.0.1',
    '--remote-debugging-port=9222', `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: ['ignore', 'pipe', 'pipe'] });
  chromium.stdout.on('data', chunk => browserLog.push(chunk.toString()));
  chromium.stderr.on('data', chunk => browserLog.push(chunk.toString()));

  const version = await waitForJson('http://127.0.0.1:9222/json/version');
  client = new CdpClient(version.webSocketDebuggerUrl);
  await client.ready;
  const { targetId } = await client.send('Target.createTarget', { url: 'about:blank' });
  ({ sessionId } = await client.send('Target.attachToTarget', { targetId, flatten: true }));
  await Promise.all([
    client.send('Page.enable', {}, sessionId),
    client.send('Runtime.enable', {}, sessionId),
    client.send('Network.enable', {}, sessionId),
    client.send('Log.enable', {}, sessionId),
    client.send('Accessibility.enable', {}, sessionId),
  ]);

  let consoleErrors = [];
  let networkFailures = [];
  let documentResponse = null;

  client.on('Log.entryAdded', ({ entry }) => {
    if (entry?.level === 'error') consoleErrors.push({ source: entry.source, text: entry.text, url: entry.url, lineNumber: entry.lineNumber });
  }, sessionId);
  client.on('Runtime.exceptionThrown', ({ exceptionDetails }) => {
    consoleErrors.push({ source: 'runtime', text: exceptionDetails?.text ?? 'Unhandled runtime exception' });
  }, sessionId);
  client.on('Network.loadingFailed', event => {
    if (!event.canceled && event.errorText !== 'net::ERR_ABORTED') networkFailures.push({ requestId: event.requestId, errorText: event.errorText, type: event.type });
  }, sessionId);
  client.on('Network.responseReceived', ({ response, type }) => {
    if (type === 'Document') documentResponse = { url: response.url, status: response.status, headers: response.headers };
  }, sessionId);

  async function setViewport(viewport) {
    await client.send('Emulation.setDeviceMetricsOverride', {
      width: viewport.width,
      height: viewport.height,
      deviceScaleFactor: 1,
      mobile: viewport.mobile,
      screenWidth: viewport.width,
      screenHeight: viewport.height,
    }, sessionId);
  }

  async function navigate(pathname) {
    consoleErrors = [];
    networkFailures = [];
    documentResponse = null;
    const loaded = new Promise(resolve => {
      const off = client.on('Page.loadEventFired', () => { off(); resolve(); }, sessionId);
    });
    await client.send('Page.navigate', { url: `${baseUrl}${pathname}` }, sessionId);
    await Promise.race([loaded, delay(25000).then(() => { throw new Error(`Load timed out: ${pathname}`); })]);
    await waitForCondition(client, sessionId, 'document.readyState === "complete"');
    await delay(650);
  }

  await setViewport(viewports[0]);
  await navigate('/login');
  await saveScreenshot(client, sessionId, 'screenshots/login-desktop-1440.png', viewports[0], false);

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

  for (const viewport of viewports) {
    await setViewport(viewport);
    for (const pathname of profile.routes) {
      await navigate(pathname);
      const currentPath = await evaluate(client, sessionId, 'location.pathname');
      if (currentPath === '/login') throw new Error(`Authentication was not retained for ${pathname}`);

      const dom = await evaluate(client, sessionId, `(() => {
        const bodyText = document.body?.innerText ?? '';
        const html = document.documentElement;
        const body = document.body;
        const interactive = document.querySelector('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (interactive) interactive.focus();
        const style = interactive ? getComputedStyle(interactive) : null;
        const rect = interactive ? interactive.getBoundingClientRect() : null;
        return {
          title: document.title,
          pathname: location.pathname,
          href: location.href,
          lang: html.lang,
          dir: html.dir || getComputedStyle(html).direction,
          viewport: { innerWidth: window.innerWidth, innerHeight: window.innerHeight, devicePixelRatio: window.devicePixelRatio },
          document: { clientWidth: html.clientWidth, clientHeight: html.clientHeight, scrollWidth: html.scrollWidth, scrollHeight: html.scrollHeight },
          body: body ? { clientWidth: body.clientWidth, scrollWidth: body.scrollWidth, scrollHeight: body.scrollHeight } : null,
          overflowX: html.scrollWidth > html.clientWidth + 1,
          arabicVisible: /[\\u0600-\\u06FF]/.test(bodyText),
          technicalLtrCount: document.querySelectorAll('[dir="ltr"], code, pre, .direction-ltr').length,
          rtlCount: document.querySelectorAll('[dir="rtl"]').length,
          focus: interactive ? {
            tag: interactive.tagName,
            id: interactive.id,
            ariaLabel: interactive.getAttribute('aria-label'),
            text: (interactive.textContent ?? '').trim().slice(0, 160),
            outlineStyle: style.outlineStyle,
            outlineWidth: style.outlineWidth,
            boxShadow: style.boxShadow,
            rect: rect ? { x: rect.x, y: rect.y, width: rect.width, height: rect.height } : null,
          } : null,
        };
      })()`);

      const ax = await client.send('Accessibility.getFullAXTree', {}, sessionId);
      const unnamedInteractive = (ax.nodes ?? []).filter(node => {
        const role = node.role?.value;
        return ['button', 'link', 'textbox', 'combobox', 'checkbox', 'radio', 'switch'].includes(role)
          && !(node.name?.value ?? '').trim() && !node.ignored;
      }).map(node => ({ role: node.role?.value, backendDOMNodeId: node.backendDOMNodeId }));

      const slug = `${laneProfile.toLowerCase()}-${viewport.name}-${safeSlug(pathname)}`;
      const viewportShot = await saveScreenshot(client, sessionId, `screenshots/${slug}.png`, viewport, false);
      let fullPageShot = null;
      if (viewport.name === 'desktop-1440') {
        fullPageShot = await saveScreenshot(client, sessionId, `screenshots/${laneProfile.toLowerCase()}-full-${safeSlug(pathname)}.png`, null, true);
      }

      const headers = Object.fromEntries(Object.entries(documentResponse?.headers ?? {}).map(([key, value]) => [key.toLowerCase(), String(value)]));
      const focusVisible = Boolean(dom.focus && ((dom.focus.outlineStyle !== 'none' && dom.focus.outlineWidth !== '0px') || dom.focus.boxShadow !== 'none'));
      const genericFailures = [];
      if ((documentResponse?.status ?? null) !== 200) genericFailures.push(`http-${documentResponse?.status ?? 'missing'}`);
      if (dom.lang !== 'ar') genericFailures.push('lang-not-ar');
      if (dom.dir !== 'rtl') genericFailures.push('dir-not-rtl');
      if (!dom.arabicVisible) genericFailures.push('arabic-not-visible');
      if (!focusVisible) genericFailures.push('focus-not-visible');
      if (dom.overflowX) genericFailures.push('document-overflow-x');
      if (consoleErrors.length) genericFailures.push('console-errors');
      if (networkFailures.length) genericFailures.push('network-failures');
      if (unnamedInteractive.length) genericFailures.push('unnamed-interactive');
      for (const header of ['content-security-policy', 'x-content-type-options', 'x-frame-options', 'referrer-policy']) {
        if (!headers[header]) genericFailures.push(`missing-${header}`);
      }

      pageResults.push({
        laneProfile,
        candidateBranch,
        candidateSha: actualSha,
        viewport,
        path: pathname,
        httpStatus: documentResponse?.status ?? null,
        genericChecks: genericFailures.length ? 'FAIL' : 'PASS',
        genericFailures,
        dom,
        focusVisible,
        consoleErrors,
        networkFailures,
        accessibility: { nodeCount: ax.nodes?.length ?? 0, unnamedInteractive },
        securityHeaders: {
          'content-security-policy': headers['content-security-policy'] ?? null,
          'x-content-type-options': headers['x-content-type-options'] ?? null,
          'x-frame-options': headers['x-frame-options'] ?? null,
          'referrer-policy': headers['referrer-policy'] ?? null,
          'permissions-policy': headers['permissions-policy'] ?? null,
        },
        screenshot: viewportShot.file,
        fullPageScreenshot: fullPageShot?.file ?? null,
      });
    }
  }

  const failures = pageResults.flatMap(page => page.genericFailures.map(failure => `${page.viewport.name}:${page.path}:${failure}`));
  const result = {
    schema: 'cep.r03-exact-visual-evidence.v1',
    status: failures.length ? 'FAIL' : 'PASS',
    assuranceScope: 'EXACT_CANDIDATE_VISUAL_AND_GENERIC_BROWSER_EVIDENCE',
    productAcceptance: 'NOT AUTOMATICALLY ESTABLISHED',
    repository: process.env.GITHUB_REPOSITORY ?? null,
    laneProfile,
    candidateBranch,
    expectedCandidateSha: expectedSha,
    actualCandidateSha: actualSha,
    exactCandidateShaMatch: expectedSha === actualSha,
    profile,
    viewports,
    github: { workflow: githubWorkflow, runId: githubRunId, runAttempt: githubRunAttempt, job: githubJob },
    runner: { os: runnerOs, arch: runnerArch },
    browser: version.Browser,
    protocolVersion: version['Protocol-Version'],
    baseUrl,
    authenticatedFlow: { email, passwordRecorded: false, result: 'PASS' },
    pages: pageResults,
    failures,
  };

  await writeFile(join(evidenceDir, 'browser-result.json'), JSON.stringify(result, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'screenshot-manifest.json'), JSON.stringify({
    schema: 'cep.r03-screenshot-manifest.v1',
    laneProfile,
    candidateSha: actualSha,
    screenshots: screenshotManifest,
  }, null, 2) + '\n');

  if (failures.length) throw new Error(`Generic browser evidence failed: ${failures.join(', ')}`);
} catch (error) {
  mainError = error;
  await writeFile(join(evidenceDir, 'browser-harness-error.json'), JSON.stringify({
    schema: 'cep.r03-browser-harness-error.v1',
    status: 'FAIL',
    laneProfile,
    expectedCandidateSha: expectedSha,
    actualCandidateSha: actualSha,
    message: error instanceof Error ? error.message : String(error),
  }, null, 2) + '\n');
  throw error;
} finally {
  try {
    if (client) client.close();
  } catch {}
  try {
    if (chromium && chromium.exitCode === null && chromium.signalCode === null) {
      chromium.kill('SIGTERM');
      await delay(1000);
      if (chromium.exitCode === null && chromium.signalCode === null) chromium.kill('SIGKILL');
    }
  } catch {}
  try { if (profileDir) await rm(profileDir, { recursive: true, force: true }); } catch {}
  await writeFile(join(evidenceDir, 'chromium-sanitized.log'), browserLog.join('').slice(-200000));
  if (mainError && screenshotManifest.length) {
    await writeFile(join(evidenceDir, 'partial-screenshot-manifest.json'), JSON.stringify({ screenshots: screenshotManifest }, null, 2) + '\n');
  }
}
