/* global Buffer, WebSocket, clearTimeout, console, fetch, process, setTimeout */

import fs from 'node:fs';
import path from 'node:path';

const endpoint = process.env.TASK010_CDP_ENDPOINT ?? 'http://127.0.0.1:19222';
const baseUrl = process.env.TASK010_RELEASE_URL ?? 'http://127.0.0.1:18081';
const email = process.env.TASK010_BROWSER_EMAIL ?? 'task010-browser@example.test';
const password = process.env.TASK010_BROWSER_PASSWORD ?? '';
const outputDirectory = process.env.TASK010_BROWSER_OUTPUT ?? '';

if (!password || !outputDirectory) {
  throw new Error('TASK010_BROWSER_PASSWORD and TASK010_BROWSER_OUTPUT are required.');
}

fs.mkdirSync(outputDirectory, { recursive: true });

const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

async function fetchJson(url, attempts = 30) {
  let lastError;
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      const response = await fetch(url);
      if (response.ok) return await response.json();
      lastError = new Error(`HTTP ${response.status}`);
    } catch (error) {
      lastError = error;
    }
    await sleep(250);
  }
  throw lastError ?? new Error(`Unable to read ${url}`);
}

class CdpConnection {
  constructor(url) {
    this.url = url;
    this.sequence = 0;
    this.pending = new Map();
    this.socket = null;
  }

  async connect() {
    this.socket = new WebSocket(this.url);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('CDP WebSocket connection timed out.')), 10000);
      this.socket.addEventListener('open', () => {
        clearTimeout(timer);
        resolve();
      }, { once: true });
      this.socket.addEventListener('error', () => {
        clearTimeout(timer);
        reject(new Error('CDP WebSocket connection failed.'));
      }, { once: true });
    });
    this.socket.addEventListener('message', (event) => {
      const message = JSON.parse(String(event.data));
      if (!message.id || !this.pending.has(message.id)) return;
      const pending = this.pending.get(message.id);
      this.pending.delete(message.id);
      if (message.error) pending.reject(new Error(message.error.message));
      else pending.resolve(message.result ?? {});
    });
  }

  send(method, params = {}, sessionId = undefined) {
    const id = ++this.sequence;
    const payload = { id, method, params };
    if (sessionId) payload.sessionId = sessionId;
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.socket.send(JSON.stringify(payload));
    });
  }

  close() {
    if (this.socket?.readyState === WebSocket.OPEN) this.socket.close();
  }
}

async function waitForExpression(cdp, sessionId, expression, timeoutMilliseconds = 15000) {
  const deadline = Date.now() + timeoutMilliseconds;
  let lastValue;
  while (Date.now() < deadline) {
    const result = await cdp.send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise: true,
    }, sessionId);
    lastValue = result.result?.value;
    if (lastValue) return lastValue;
    await sleep(200);
  }
  throw new Error(`Timed out waiting for browser expression. Last value: ${String(lastValue)}`);
}

async function navigate(cdp, sessionId, url) {
  await cdp.send('Page.navigate', { url }, sessionId);
  await waitForExpression(cdp, sessionId, 'document.readyState === "complete"');
}

async function setViewport(cdp, sessionId, width, height, mobile = false) {
  await cdp.send('Emulation.setDeviceMetricsOverride', {
    width,
    height,
    deviceScaleFactor: 1,
    mobile,
    screenWidth: width,
    screenHeight: height,
  }, sessionId);
}

async function evaluate(cdp, sessionId, expression) {
  const result = await cdp.send('Runtime.evaluate', {
    expression,
    returnByValue: true,
    awaitPromise: true,
  }, sessionId);
  if (result.exceptionDetails) {
    throw new Error(result.exceptionDetails.text ?? 'Browser evaluation failed.');
  }
  return result.result?.value;
}

async function screenshot(cdp, sessionId, filename) {
  await sleep(350);
  const result = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    fromSurface: true,
    captureBeyondViewport: false,
  }, sessionId);
  fs.writeFileSync(path.join(outputDirectory, filename), Buffer.from(result.data, 'base64'));
}

const resultPath = path.join(outputDirectory, 'BROWSER_GATE_RESULT.json');
let cdp;
try {
  const version = await fetchJson(`${endpoint}/json/version`);
  cdp = new CdpConnection(version.webSocketDebuggerUrl);
  await cdp.connect();

  const target = await cdp.send('Target.createTarget', { url: 'about:blank' });
  const attached = await cdp.send('Target.attachToTarget', { targetId: target.targetId, flatten: true });
  const sessionId = attached.sessionId;
  await cdp.send('Page.enable', {}, sessionId);
  await cdp.send('Runtime.enable', {}, sessionId);
  await cdp.send('Accessibility.enable', {}, sessionId);

  await setViewport(cdp, sessionId, 1440, 900, false);
  await navigate(cdp, sessionId, `${baseUrl}/login`);
  await screenshot(cdp, sessionId, '01_login_desktop.png');

  const credentials = JSON.stringify({ email, password });
  await evaluate(cdp, sessionId, `(() => {
    const credentials = ${credentials};
    const setValue = (element, value) => {
      const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
      setter.call(element, value);
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const emailInput = document.querySelector('#email');
    const passwordInput = document.querySelector('#password');
    if (!emailInput || !passwordInput) throw new Error('Login inputs were not found.');
    setValue(emailInput, credentials.email);
    setValue(passwordInput, credentials.password);
    document.querySelector('form').requestSubmit();
    return true;
  })()`);
  await waitForExpression(cdp, sessionId, 'location.pathname === "/release" && document.readyState === "complete"', 20000);

  await evaluate(cdp, sessionId, 'window.scrollTo(0, 0); true');
  await screenshot(cdp, sessionId, '02_release_readiness_desktop.png');
  await evaluate(cdp, sessionId, 'document.querySelector("#queue-heading")?.scrollIntoView({block:"start"}); true');
  await screenshot(cdp, sessionId, '03_queue_search_desktop.png');
  await evaluate(
    cdp,
    sessionId,
    `document.querySelector('[aria-label="عمليات الإصدار المقيدة"]')?.scrollIntoView({ block: 'start' }); true`,
  );
  await screenshot(cdp, sessionId, '04_operations_desktop.png');

  await setViewport(cdp, sessionId, 390, 844, true);
  await evaluate(cdp, sessionId, 'window.scrollTo(0, 0); true');
  await screenshot(cdp, sessionId, '05_release_mobile_top.png');
  await evaluate(
    cdp,
    sessionId,
    `document.querySelector('[aria-label="عمليات الإصدار المقيدة"]')?.scrollIntoView({ block: 'start' }); true`,
  );
  await screenshot(cdp, sessionId, '06_release_mobile_operations.png');

  await setViewport(cdp, sessionId, 1440, 900, false);
  await evaluate(cdp, sessionId, 'document.querySelector("#release-search")?.scrollIntoView({block:"center"}); document.querySelector("#release-search")?.focus(); true');
  await screenshot(cdp, sessionId, '07_keyboard_focus.png');
  await evaluate(
    cdp,
    sessionId,
    `document.querySelector('[aria-label="حدود جسر الذكاء الاصطناعي"]')?.scrollIntoView({ block: 'center' }); true`,
  );
  await screenshot(cdp, sessionId, '08_manual_ai_boundary.png');

  const accessibility = await cdp.send('Accessibility.getFullAXTree', {}, sessionId);
  fs.writeFileSync(path.join(outputDirectory, 'ACCESSIBILITY_TREE.json'), `${JSON.stringify(accessibility, null, 2)}\n`);

  const securityHeaders = await fetch(`${baseUrl}/release`, { redirect: 'manual' });
  const allowedHeaderNames = new Set([
    'cache-control',
    'content-security-policy',
    'cross-origin-opener-policy',
    'cross-origin-resource-policy',
    'permissions-policy',
    'referrer-policy',
    'x-content-type-options',
    'x-frame-options',
  ]);
  const headerSnapshot = Object.fromEntries(
    [...securityHeaders.headers.entries()]
      .filter(([name]) => allowedHeaderNames.has(name.toLowerCase()))
      .sort(([left], [right]) => left.localeCompare(right)),
  );
  fs.writeFileSync(path.join(outputDirectory, 'HTTP_SECURITY_HEADERS.json'), `${JSON.stringify({ status: securityHeaders.status, headers: headerSnapshot }, null, 2)}\n`);

  const result = {
    status: 'PASS',
    attempted: true,
    browser_product: version.Browser ?? 'unknown',
    release_url: baseUrl,
    screenshot_count: 8,
    screenshots: fs.readdirSync(outputDirectory).filter((name) => name.endsWith('.png')).sort(),
    accessibility_tree: 'ACCESSIBILITY_TREE.json',
    security_headers: 'HTTP_SECURITY_HEADERS.json',
    generated_at_utc: new Date().toISOString(),
  };
  fs.writeFileSync(resultPath, `${JSON.stringify(result, null, 2)}\n`);
  console.log(JSON.stringify(result));
} catch (error) {
  const result = {
    status: 'BLOCKED_BROWSER_ATTEMPT_FAILED',
    attempted: true,
    reason: error instanceof Error ? error.message : String(error),
    generated_at_utc: new Date().toISOString(),
  };
  fs.writeFileSync(resultPath, `${JSON.stringify(result, null, 2)}\n`);
  console.error(JSON.stringify(result));
  process.exitCode = 1;
} finally {
  cdp?.close();
}
