/* global Buffer, WebSocket, fetch, process, setTimeout */

import { spawn } from 'node:child_process';
import { mkdtemp, mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';

const chromePath = process.env.CHROME_PATH;
const baseUrl = process.env.SCREENSHOT_BASE_URL ?? 'http://127.0.0.1:8087';
const email = process.env.SCREENSHOT_EMAIL;
const password = process.env.SCREENSHOT_PASSWORD;
const outputDirectory = resolve(
    process.env.SCREENSHOT_OUTPUT ?? 'review-packets/vs001-007/rendered',
);
const debuggingPort = 9337;

if (!chromePath || !email || !password) {
    throw new Error('CHROME_PATH, SCREENSHOT_EMAIL, and SCREENSHOT_PASSWORD are required.');
}

await mkdir(outputDirectory, { recursive: true });
const profile = await mkdtemp(join(tmpdir(), 'task007-chrome-'));
const chrome = spawn(
    chromePath,
    [
        '--headless=new',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-dev-shm-usage',
        '--no-sandbox',
        '--hide-scrollbars',
        '--no-first-run',
        '--no-default-browser-check',
        `--remote-debugging-port=${debuggingPort}`,
        '--remote-debugging-address=127.0.0.1',
        `--user-data-dir=${profile}`,
        '--window-size=1440,1000',
        'about:blank',
    ],
    { stdio: 'ignore', windowsHide: true },
);

const sleep = (milliseconds) =>
    new Promise((resolvePromise) => setTimeout(resolvePromise, milliseconds));

async function jsonEndpoint(pathname, options = {}) {
    const response = await fetch(`http://127.0.0.1:${debuggingPort}${pathname}`, options);
    if (!response.ok) {
        throw new Error(`Chrome debugging endpoint failed: ${response.status}`);
    }

    return response.json();
}

async function waitForDebugger() {
    for (let attempt = 0; attempt < 60; attempt += 1) {
        try {
            return await jsonEndpoint('/json/version');
        } catch {
            await sleep(250);
        }
    }

    throw new Error('Chrome debugging endpoint did not become ready.');
}

class CdpSession {
    constructor(url) {
        this.socket = new WebSocket(url);
        this.sequence = 0;
        this.pending = new Map();
    }

    async open() {
        await new Promise((resolvePromise, reject) => {
            this.socket.addEventListener('open', resolvePromise, { once: true });
            this.socket.addEventListener('error', reject, { once: true });
        });
        this.socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);
            if (!message.id || !this.pending.has(message.id)) return;
            const { resolvePromise, reject } = this.pending.get(message.id);
            this.pending.delete(message.id);
            if (message.error) reject(new Error(message.error.message));
            else resolvePromise(message.result ?? {});
        });
    }

    send(method, params = {}) {
        const id = ++this.sequence;
        return new Promise((resolvePromise, reject) => {
            this.pending.set(id, { resolvePromise, reject });
            this.socket.send(JSON.stringify({ id, method, params }));
        });
    }

    close() {
        this.socket.close();
    }
}

async function evaluate(session, expression) {
    const result = await session.send('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true,
    });
    if (result.exceptionDetails) throw new Error('Browser evaluation failed.');
    return result.result?.value;
}

async function waitFor(session, expression) {
    for (let attempt = 0; attempt < 80; attempt += 1) {
        if (await evaluate(session, expression)) return;
        await sleep(250);
    }

    throw new Error(`Timed out waiting for: ${expression}`);
}

async function viewport(session, width, height, mobile = false) {
    await session.send('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 1,
        mobile,
    });
}

async function navigate(session, url, expectedPath) {
    await session.send('Page.navigate', { url });
    await waitFor(
        session,
        `document.readyState === 'complete' && location.pathname === ${JSON.stringify(expectedPath)}`,
    );
    await sleep(500);
}

async function screenshot(session, filename, clip = undefined) {
    const result = await session.send('Page.captureScreenshot', {
        format: 'png',
        fromSurface: true,
        captureBeyondViewport: false,
        ...(clip ? { clip } : {}),
    });
    await writeFile(join(outputDirectory, filename), Buffer.from(result.data, 'base64'));
}

async function assertNoHorizontalOverflow(session, label) {
    const hasNoOverflow = await evaluate(
        session,
        `document.documentElement.scrollWidth <= document.documentElement.clientWidth`,
    );
    if (!hasNoOverflow) throw new Error(`Horizontal overflow detected at ${label}.`);
}

let session;
try {
    await waitForDebugger();
    const target = await jsonEndpoint(`/json/new?${encodeURIComponent(`${baseUrl}/login`)}`, {
        method: 'PUT',
    });
    session = new CdpSession(target.webSocketDebuggerUrl);
    await session.open();
    await session.send('Page.enable');
    await session.send('Runtime.enable');

    await viewport(session, 1440, 1000);
    await navigate(session, `${baseUrl}/login`, '/login');
    await assertNoHorizontalOverflow(session, 'login 1440x1000');
    await screenshot(session, 'foundation-login-desktop.png');

    await viewport(session, 390, 844, true);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/login'`);
    await assertNoHorizontalOverflow(session, 'login 390x844');
    await screenshot(session, 'foundation-login-mobile.png');

    await viewport(session, 1440, 1000);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/login'`);
    await evaluate(
        session,
        `(() => {
      const setValue = (selector, value) => {
        const element = document.querySelector(selector);
        const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
        setter.call(element, value);
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
      };
      setValue('#email', ${JSON.stringify(email)});
      setValue('#password', ${JSON.stringify(password)});
      document.querySelector('form').requestSubmit();
      return true;
    })()`,
    );
    await waitFor(session, `location.pathname === '/' && document.querySelector('main') !== null`);
    await sleep(750);
    await assertNoHorizontalOverflow(session, 'dashboard 1440x1000');
    await screenshot(session, 'foundation-dashboard-desktop.png');

    await viewport(session, 1024, 700);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/'`);
    await assertNoHorizontalOverflow(session, 'dashboard 1024x700');
    await screenshot(session, 'foundation-bidi-closeup.png');

    await viewport(session, 1024, 768);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/'`);
    await evaluate(
        session,
        `(() => { const element = document.querySelector('button'); element?.focus(); return document.activeElement === element; })()`,
    );
    await screenshot(session, 'foundation-focus-state.png');

    await viewport(session, 512, 384);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/'`);
    await assertNoHorizontalOverflow(session, 'dashboard 200 percent reflow simulation 512x384');

    await viewport(session, 390, 844, true);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/'`);
    await assertNoHorizontalOverflow(session, 'dashboard 390x844');
    await screenshot(session, 'foundation-dashboard-mobile.png');

    await viewport(session, 1440, 1000);
    await navigate(session, `${baseUrl}/vs001/sources`, '/vs001/sources');
    await assertNoHorizontalOverflow(session, 'VS-001 source review 1440x1000');
    await screenshot(session, 'vs001-source-review-desktop.png');

    await navigate(session, `${baseUrl}/vs001/lesson/editor`, '/vs001/lesson/editor');
    await assertNoHorizontalOverflow(session, 'VS-001 lesson editor 1440x1000');
    await screenshot(session, 'vs001-lesson-editor-desktop.png');

    await viewport(session, 390, 844, true);
    await navigate(session, `${baseUrl}/vs001/lesson`, '/vs001/lesson');
    await assertNoHorizontalOverflow(session, 'VS-001 lesson reader 390x844');
    await screenshot(session, 'vs001-lesson-reader-mobile.png');

    await navigate(session, `${baseUrl}/vs001/practice`, '/vs001/practice');
    await assertNoHorizontalOverflow(session, 'VS-001 micro practice 390x844');
    await screenshot(session, 'vs001-micro-practice-mobile.png');

    await viewport(session, 1440, 1000);
    await navigate(session, `${baseUrl}/vs001/lab`, '/vs001/lab');
    await evaluate(
        session,
        `(() => {
      const select = document.querySelector('#case');
      const setter = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value').set;
      setter.call(select, 'CASE-003-DENY-BEFORE-ALLOW');
      select.dispatchEvent(new Event('change', { bubbles: true }));
      document.querySelector('form').requestSubmit();
      return true;
    })()`,
    );
    await waitFor(session, `document.body.innerText.includes('LATEST RUN') && document.body.innerText.includes('RULE-ACE-DENY')`);
    await sleep(500);
    await assertNoHorizontalOverflow(session, 'VS-001 guided lab 1440x1000');
    await screenshot(session, 'vs001-guided-lab-desktop.png');

    await viewport(session, 390, 844, true);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && location.pathname === '/vs001/lab'`);
    await assertNoHorizontalOverflow(session, 'VS-001 guided lab 390x844');
    await screenshot(session, 'vs001-guided-lab-mobile.png');

    await viewport(session, 1024, 700);
    await session.send('Page.reload', { ignoreCache: true });
    await waitFor(session, `document.readyState === 'complete' && document.body.innerText.includes('RULE-ACE-DENY')`);
    await assertNoHorizontalOverflow(session, 'VS-001 decision trace 1024x700');
    await screenshot(session, 'vs001-decision-trace-closeup.png');

    await viewport(session, 1440, 1000);
    await navigate(session, `${baseUrl}/vs001/evidence`, '/vs001/evidence');
    await assertNoHorizontalOverflow(session, 'VS-001 evidence mastery 1440x1000');
    await screenshot(session, 'vs001-evidence-mastery-desktop.png');

    await viewport(session, 1024, 768);
    await navigate(session, `${baseUrl}/vs001/practice`, '/vs001/practice');
    await evaluate(session, `(() => { const target = document.querySelector('input[type="radio"]'); target?.focus(); return document.activeElement === target; })()`);
    await screenshot(session, 'vs001-focus-state.png');

    await viewport(session, 1024, 700);
    await navigate(session, `${baseUrl}/vs001/sources`, '/vs001/sources');
    await assertNoHorizontalOverflow(session, 'VS-001 bidi closeup 1024x700');
    await screenshot(session, 'vs001-bidi-closeup.png');

    await viewport(session, 512, 384);
    await navigate(session, `${baseUrl}/vs001/lab`, '/vs001/lab');
    await assertNoHorizontalOverflow(session, 'VS-001 200 percent reflow simulation 512x384');

    process.stdout.write(`${outputDirectory}\nREFLOW_200_PERCENT=PASS\nHORIZONTAL_OVERFLOW=0\n`);
} finally {
    session?.close();
    if (chrome.exitCode === null) {
        chrome.kill();
        await Promise.race([
            new Promise((resolvePromise) => chrome.once('exit', resolvePromise)),
            sleep(5_000),
        ]);
    }
    if (profile.startsWith(join(tmpdir(), 'task007-chrome-'))) {
        for (let attempt = 0; attempt < 20; attempt += 1) {
            try {
                await rm(profile, { recursive: true, force: true });
                break;
      } catch (error) {
        if (error.code !== 'EBUSY' || attempt === 19) {
          process.stderr.write(`Unable to remove temporary Chrome profile: ${error.message}\n`);
          process.exitCode = 1;
          break;
        }
        await sleep(250);
      }
        }
    }
}
