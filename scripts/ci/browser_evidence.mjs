import { spawn } from 'node:child_process';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8081').replace(/\/$/, '');
const email = process.env.BROWSER_EMAIL ?? 'task010-browser@example.test';
const password = process.env.BROWSER_PASSWORD ?? '';
const evidenceDir = process.env.EVIDENCE_DIR ?? 'evidence/browser';
const chromiumPath = process.env.CHROMIUM_PATH ?? '/usr/bin/chromium';
const routeProfilePath = process.env.BROWSER_ROUTE_PROFILE_FILE ?? 'scripts/ci/browser_route_profiles.json';
const requestedProfileValue = process.env.BROWSER_ROUTE_PROFILES?.trim() ?? '';
const validateProfilesOnly = process.argv.includes('--validate-profiles');
const viewports = [
  { name: 'desktop', width: 1440, height: 1000, mobile: false },
  { name: 'mobile', width: 390, height: 844, mobile: true },
];

await mkdir(join(evidenceDir, 'screenshots'), { recursive: true });
const browserLog = [];
const pageResults = [];
let chromium;
let profileDir;
let routeProfiles;
let selectedProfiles = [];
let selectedRoutes;

const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

function parseProfileNames(value) {
  return value.split(',').map(item => item.trim()).filter(Boolean);
}

function validateRoute(pathname, profileName) {
  if (typeof pathname !== 'string' || !pathname.startsWith('/') || /[\s?#]/.test(pathname) || pathname.startsWith('//')) {
    throw new Error(`Malformed route in profile ${profileName}: ${JSON.stringify(pathname)}`);
  }
}

async function loadRouteProfiles() {
  let parsed;
  try {
    parsed = JSON.parse(await readFile(routeProfilePath, 'utf8'));
  } catch (error) {
    throw new Error(`Unable to parse route profile file ${routeProfilePath}: ${error instanceof Error ? error.message : String(error)}`, { cause: error });
  }
  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) throw new Error('Route profile document must be a JSON object.');
  if (parsed.schema !== 'cep.browser-route-profiles.v1') throw new Error(`Unsupported route profile schema: ${JSON.stringify(parsed.schema)}`);
  if (!Array.isArray(parsed.requiredProfiles) || parsed.requiredProfiles.length === 0) throw new Error('requiredProfiles must be a non-empty array.');
  if (!Array.isArray(parsed.defaultProfiles) || parsed.defaultProfiles.length === 0) throw new Error('defaultProfiles must be a non-empty array.');
  if (!parsed.profiles || typeof parsed.profiles !== 'object' || Array.isArray(parsed.profiles)) throw new Error('profiles must be an object.');

  const requiredProfiles = parsed.requiredProfiles;
  const defaults = parsed.defaultProfiles;
  for (const [label, values] of [['requiredProfiles', requiredProfiles], ['defaultProfiles', defaults]]) {
    if (new Set(values).size !== values.length) throw new Error(`${label} contains duplicate profile names.`);
    for (const name of values) {
      if (typeof name !== 'string' || !/^[A-Z][A-Z0-9_-]*$/.test(name)) throw new Error(`Malformed profile name in ${label}: ${JSON.stringify(name)}`);
      if (!Object.hasOwn(parsed.profiles, name)) throw new Error(`${label} references unknown profile ${name}.`);
    }
  }

  for (const [name, profile] of Object.entries(parsed.profiles)) {
    if (!/^[A-Z][A-Z0-9_-]*$/.test(name)) throw new Error(`Malformed profile key: ${JSON.stringify(name)}`);
    if (!profile || typeof profile !== 'object' || Array.isArray(profile)) throw new Error(`Profile ${name} must be an object.`);
    if (typeof profile.description !== 'string' || !profile.description.trim()) throw new Error(`Profile ${name} requires a non-empty description.`);
    if (!Array.isArray(profile.routes) || profile.routes.length === 0) throw new Error(`Profile ${name} routes must be a non-empty array.`);
    if (new Set(profile.routes).size !== profile.routes.length) throw new Error(`Profile ${name} contains duplicate routes.`);
    for (const route of profile.routes) validateRoute(route, name);
  }

  const selected = requestedProfileValue ? parseProfileNames(requestedProfileValue) : defaults;
  if (selected.length === 0) throw new Error('No browser route profiles were selected.');
  if (new Set(selected).size !== selected.length) throw new Error('Selected browser route profiles contain duplicates.');
  for (const name of selected) {
    if (!/^[A-Z][A-Z0-9_-]*$/.test(name)) throw new Error(`Malformed required profile: ${JSON.stringify(name)}`);
    if (!Object.hasOwn(parsed.profiles, name)) throw new Error(`Unknown required browser route profile: ${name}`);
    if (!requiredProfiles.includes(name)) throw new Error(`Selected profile ${name} is not declared in requiredProfiles.`);
  }

  const catalog = new Map();
  for (const name of selected) {
    for (const route of parsed.profiles[name].routes) {
      const membership = catalog.get(route) ?? [];
      membership.push(name);
      catalog.set(route, membership);
    }
  }
  const routes = [...catalog.entries()].map(([path, profiles]) => ({ path, profiles }));
  return { ...parsed, selected, routes };
}

async function writeRouteProfileEvidence(reason = null) {
  if (!routeProfiles) return;
  const profiles = Object.entries(routeProfiles.profiles).map(([name, profile]) => {
    if (!selectedProfiles.includes(name)) {
      return {
        name,
        selected: false,
        status: 'SKIPPED',
        verification: 'NOT VERIFIED',
        reason: 'profile-not-selected',
        routes: profile.routes.map(path => ({ path, status: 'SKIPPED', verification: 'NOT VERIFIED', reason: 'profile-not-selected' })),
      };
    }
    const routes = profile.routes.map(path => {
      const records = pageResults.filter(page => page.path === path);
      if (records.length < viewports.length) {
        return {
          path,
          status: 'NOT VERIFIED',
          verification: 'NOT VERIFIED',
          reason: reason ?? 'route-not-exercised-in-all-required-viewports',
          exercisedViewports: records.map(record => record.viewport),
        };
      }
      const failing = records.filter(record => record.genericChecks !== 'PASS');
      return {
        path,
        status: failing.length ? 'FAIL' : 'VERIFIED',
        verification: failing.length ? 'GENERIC ROUTE CHECK FAILED' : 'GENERIC ROUTE VERIFIED',
        reason: failing.length ? 'one-or-more-generic-browser-checks-failed' : null,
        exercisedViewports: records.map(record => record.viewport),
      };
    });
    const failed = routes.some(route => route.status === 'FAIL');
    const incomplete = routes.some(route => route.status === 'NOT VERIFIED');
    return {
      name,
      selected: true,
      status: failed ? 'FAIL' : incomplete ? 'NOT VERIFIED' : 'VERIFIED',
      verification: failed ? 'GENERIC ROUTE CHECK FAILED' : incomplete ? 'NOT VERIFIED' : 'GENERIC ROUTE VERIFIED',
      productAssurance: 'NOT ESTABLISHED',
      reason: incomplete ? (reason ?? 'one-or-more-routes-not-exercised') : failed ? 'one-or-more-routes-failed-generic-checks' : null,
      routes,
    };
  });
  await writeFile(join(evidenceDir, 'route-profile-coverage.json'), JSON.stringify({
    schema: 'cep.browser-route-coverage.v1',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    selectedProfiles,
    profiles,
  }, null, 2) + '\n');
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
  routeProfiles = await loadRouteProfiles();
  selectedProfiles = routeProfiles.selected;
  selectedRoutes = routeProfiles.routes;
  await writeFile(join(evidenceDir, 'route-profile-selection.json'), JSON.stringify({
    schema: 'cep.browser-route-selection.v1',
    source: routeProfilePath,
    requested: requestedProfileValue || null,
    selectedProfiles,
    defaultSelectionUsed: !requestedProfileValue,
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    routes: selectedRoutes,
  }, null, 2) + '\n');
  await writeRouteProfileEvidence('browser-capture-not-started');

  if (validateProfilesOnly) {
    await writeFile(join(evidenceDir, 'route-profile-validation.json'), JSON.stringify({
      schema: 'cep.browser-route-profile-validation.v1',
      status: 'PASS',
      source: routeProfilePath,
      requiredProfiles: routeProfiles.requiredProfiles,
      selectedProfiles,
      enumeratedRoutes: selectedRoutes,
      productAssurance: 'NOT ESTABLISHED',
    }, null, 2) + '\n');
  } else {
  if (password.length < 20) throw new Error('BROWSER_PASSWORD must be a synthetic value of at least 20 characters.');
  profileDir = await mkdtemp(join(tmpdir(), 'cep-browser-'));
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

  await setViewport(viewports[0]);
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

  for (const viewport of viewports) {
    await setViewport(viewport);
    for (const route of selectedRoutes) {
      const pathname = route.path;
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
      const genericFailures = [];
      if ((documentResponse?.status ?? null) !== 200) genericFailures.push(`http-${documentResponse?.status ?? 'missing'}`);
      if (dom.lang !== 'ar') genericFailures.push('lang-not-ar');
      if (dom.dir !== 'rtl') genericFailures.push('dir-not-rtl');
      if (!dom.arabicVisible) genericFailures.push('arabic-not-visible');
      if (!focusVisible) genericFailures.push('focus-not-visible');
      if (dom.overflowX) genericFailures.push('document-overflow-x');
      if (consoleEntries.length) genericFailures.push('console-errors');
      if (networkFailures.length) genericFailures.push('network-failures');
      if (unnamedInteractive.length) genericFailures.push('unnamed-interactive');
      for (const header of ['content-security-policy', 'x-content-type-options', 'x-frame-options', 'referrer-policy']) {
        if (!securityHeaders[header]) genericFailures.push(`missing-${header}`);
      }
      pageResults.push({
        viewport: viewport.name,
        path: pathname,
        routeProfiles: route.profiles,
        assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
        productAssurance: 'NOT ESTABLISHED',
        status: documentResponse?.status ?? null,
        genericChecks: genericFailures.length ? 'FAIL' : 'PASS',
        genericFailures,
        dom,
        focusVisible,
        consoleErrors: consoleEntries,
        failedNetworkRequests: networkFailures,
        accessibility: { nodeCount: ax.nodes?.length ?? 0, unnamedInteractive },
        securityHeaders,
        screenshot: `screenshots/${slug}.png`,
      });
      await writeRouteProfileEvidence();
    }
  }

  const failures = [];
  for (const page of pageResults) {
    for (const failure of page.genericFailures) failures.push(`${page.viewport}:${page.path}:${failure}`);
  }
  if (!pageResults.some(page => page.dom.technicalLtrCount > 0)) failures.push('technical-ltr-content-not-detected');

  const result = {
    schema: 'cep.browser-evidence.v2',
    browser: version.Browser,
    protocolVersion: version['Protocol-Version'],
    baseUrl,
    status: failures.length ? 'FAIL' : 'PASS',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    routeProfiles: selectedProfiles,
    authenticatedFlow: { email, passwordRecorded: false, result: 'PASS' },
    pages: pageResults,
    failures,
  };
  await writeFile(join(evidenceDir, 'browser-result.json'), JSON.stringify(result, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'accessibility-result.json'), JSON.stringify({
    schema: 'cep.accessibility-evidence.v1',
    status: failures.some(item => item.includes('focus') || item.includes('overflow') || item.includes('unnamed') || item.includes('lang-') || item.includes('dir-')) ? 'FAIL' : 'PASS',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    pages: pageResults.map(({ viewport, path, routeProfiles: profiles, dom, focusVisible, accessibility }) => ({ viewport, path, routeProfiles: profiles, lang: dom.lang, dir: dom.dir, overflowX: dom.overflowX, focusVisible, accessibility })),
  }, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'console-network-result.json'), JSON.stringify({
    schema: 'cep.console-network-evidence.v1',
    status: pageResults.some(page => page.consoleErrors.length || page.failedNetworkRequests.length) ? 'FAIL' : 'PASS',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    pages: pageResults.map(({ viewport, path, routeProfiles: profiles, consoleErrors, failedNetworkRequests }) => ({ viewport, path, routeProfiles: profiles, consoleErrors, failedNetworkRequests })),
  }, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'compatibility-result.json'), JSON.stringify({
    schema: 'cep.browser-compatibility-evidence.v1',
    status: pageResults.some(page => page.dom.overflowX || page.dom.lang !== 'ar' || page.dom.dir !== 'rtl') ? 'FAIL' : 'PASS',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    requiredViewports: viewports.map(({ name, width, height, mobile }) => ({ name, width, height, mobile })),
    pages: pageResults.map(({ viewport, path, routeProfiles: profiles, dom }) => ({ viewport, path, routeProfiles: profiles, pathname: dom.pathname, lang: dom.lang, dir: dom.dir, overflowX: dom.overflowX })),
  }, null, 2) + '\n');
  await writeFile(join(evidenceDir, 'security-header-snapshot.json'), JSON.stringify({
    schema: 'cep.security-headers.v1',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    pages: pageResults.map(({ viewport, path, routeProfiles: profiles, status, securityHeaders }) => ({ viewport, path, routeProfiles: profiles, status, securityHeaders })),
  }, null, 2) + '\n');
  await writeRouteProfileEvidence(failures.length ? 'generic-browser-checks-failed' : null);

  if (failures.length) {
    const diagnostics = await evaluate(client, sessionId, `({ url: location.href, title: document.title, html: document.documentElement.outerHTML.slice(0, 200000) })`);
    await writeFile(join(evidenceDir, 'failure-diagnostic.json'), JSON.stringify({ failures, diagnostics }, null, 2) + '\n');
    throw new Error(`Browser evidence failed: ${failures.join(', ')}`);
  }
  client.close();
  }
} catch (error) {
  await writeRouteProfileEvidence('browser-harness-aborted-before-route-completed');
  await writeFile(join(evidenceDir, 'browser-harness-error.json'), JSON.stringify({
    schema: 'cep.browser-harness-error.v1',
    status: 'FAIL',
    assuranceScope: 'GENERIC_BROWSER_ROUTE_EVIDENCE',
    productAssurance: 'NOT ESTABLISHED',
    selectedProfiles,
    message: error instanceof Error ? error.message : String(error),
  }, null, 2) + '\n');
  throw error;
} finally {
  chromium?.kill('SIGTERM');
  const sanitizedLog = password ? browserLog.join('').replaceAll(password, '[REDACTED]') : browserLog.join('');
  await writeFile(join(evidenceDir, 'chromium-sanitized.log'), sanitizedLog);
  if (profileDir) await rm(profileDir, { recursive: true, force: true });
}
