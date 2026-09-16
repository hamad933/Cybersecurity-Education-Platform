import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const root = process.argv[2];
if (!root) throw new Error('STAGING_ROOT_REQUIRED');
fs.mkdirSync(root, { recursive: true });
const backupRoot = path.join(root, 'backup');
const exportRoot = path.join(root, 'export');
fs.mkdirSync(backupRoot, { recursive: true });
fs.mkdirSync(exportRoot, { recursive: true });
const baseline = path.join(root, 'baseline.bin');
fs.writeFileSync(baseline, 'CEP_PS05_BOUNDED_BACKUP_SOURCE\n', 'utf8');
const sha256 = (b: Buffer | string) => crypto.createHash('sha256').update(b).digest('hex');
const boundedName = (name: unknown) => path.basename(String(name || 'artifact.bin')).replace(/[^A-Za-z0-9._-]/g, '_');
const send = (res: http.ServerResponse, status: number, body: unknown) => {
  const raw = JSON.stringify(body);
  res.writeHead(status, { 'content-type': 'application/json', 'content-length': Buffer.byteLength(raw) });
  res.end(raw);
};
const readBody = async (req: http.IncomingMessage) => {
  let data = '';
  for await (const chunk of req) {
    data += chunk;
    if (data.length > 1024 * 1024) throw new Error('BODY_TOO_LARGE');
  }
  return data ? JSON.parse(data) : {};
};
const forbidden = new Set(['/v1/shell/exec','/v1/process/exec','/v1/ssh/connect','/v1/docker/run','/v1/vm/start','/v1/wsl/run']);
const capabilities = {
  persistence: { availability: 'AVAILABLE', semanticBackend: false },
  backupStaging: { availability: 'AVAILABLE', arbitraryDestinationPath: false },
  importExport: { availability: 'AVAILABLE', canonicalPublication: false },
  shell: { availability: 'UNAVAILABLE' }, processExecution: { availability: 'UNAVAILABLE' },
  ssh: { availability: 'UNAVAILABLE' }, docker: { availability: 'UNAVAILABLE' }, vmWsl: { availability: 'UNAVAILABLE' }
};
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url || '/', 'http://127.0.0.1');
    if (req.method === 'GET' && url.pathname === '/v1/capabilities') return send(res, 200, { ok: true, capabilities });
    if (req.method === 'GET' && url.pathname === '/v1/health') return send(res, 200, { ok: true, loopbackOnly: true, semanticBackend: false });
    if (forbidden.has(url.pathname)) return send(res, 403, { ok: false, code: 'CAPABILITY_NOT_EXPOSED' });
    if (req.method === 'POST' && url.pathname === '/v1/backup/stage') {
      const body = await readBody(req); const fileName = boundedName(body.fileName || 'backup.bin'); const dest = path.join(backupRoot, fileName);
      fs.copyFileSync(baseline, dest); return send(res, 200, { ok: true, fileName, targetRootBounded: path.dirname(dest) === backupRoot, sha256: sha256(fs.readFileSync(dest)) });
    }
    if (req.method === 'POST' && url.pathname === '/v1/export/stage') {
      const body = await readBody(req); const raw = Buffer.from(JSON.stringify({ schema: 'CEP_PS05_EXPORT_V1', canonicalPublication: false, payload: body.payload ?? null }));
      const digest = sha256(raw); const fileName = `package-${digest.slice(0,12)}.json`; const dest = path.join(exportRoot, fileName); fs.writeFileSync(dest, raw);
      return send(res, 200, { ok: true, fileName, sha256: digest, targetRootBounded: path.dirname(dest) === exportRoot, canonicalPublication: false });
    }
    if (req.method === 'POST' && url.pathname === '/v1/import/validate') {
      const body = await readBody(req); const fileName = boundedName(body.fileName); const src = path.join(exportRoot, fileName);
      if (!fs.existsSync(src)) return send(res, 404, { ok: false, reason: 'STAGED_FILE_NOT_FOUND', stagedOnly: true, canonicalPublication: false });
      const computedSha256 = sha256(fs.readFileSync(src)); const ok = computedSha256 === String(body.expectedSha256 || '');
      return send(res, ok ? 200 : 422, { ok, stagedOnly: true, canonicalPublication: false, reason: ok ? null : 'IMPORT_VALIDATION_FAILED', computedSha256 });
    }
    return send(res, 404, { ok: false, code: 'NOT_FOUND' });
  } catch (error: any) { return send(res, 500, { ok: false, code: 'HOST_ERROR', message: String(error?.message || error) }); }
});
server.listen(0, '127.0.0.1', () => {
  const address = server.address();
  if (!address || typeof address === 'string') throw new Error('NO_TCP_ADDRESS');
  process.stdout.write(JSON.stringify({ ready: true, host: '127.0.0.1', port: address.port }) + '\n');
});
const shutdown = () => server.close(() => process.exit(0));
process.on('SIGTERM', shutdown); process.on('SIGINT', shutdown);
