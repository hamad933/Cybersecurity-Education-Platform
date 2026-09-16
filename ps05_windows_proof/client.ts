import fs from 'node:fs';
const base = process.argv[2]; const outPath = process.argv[3];
if (!base || !outPath) throw new Error('BASE_AND_OUTPUT_REQUIRED');
const traffic: any[] = [];
async function req(method: string, p: string, body?: any) {
  const res = await fetch(base + p, { method, headers: body === undefined ? {} : {'content-type':'application/json'}, body: body === undefined ? undefined : JSON.stringify(body) });
  const json = await res.json(); traffic.push({ method, path: p, status: res.status, response: json }); return { status: res.status, json };
}
const cap = await req('GET','/v1/capabilities'); const health = await req('GET','/v1/health');
const backup = await req('POST','/v1/backup/stage',{fileName:'..\\..\\escape-attempt.sqlite'});
const exp = await req('POST','/v1/export/stage',{payload:{hello:'world'}});
const valid = await req('POST','/v1/import/validate',{fileName:exp.json.fileName, expectedSha256:exp.json.sha256});
const invalid = await req('POST','/v1/import/validate',{fileName:exp.json.fileName, expectedSha256:'0'.repeat(64)});
const forbiddenPaths = ['/v1/shell/exec','/v1/process/exec','/v1/ssh/connect','/v1/docker/run','/v1/vm/start','/v1/wsl/run'];
const forbidden = []; for (const p of forbiddenPaths) forbidden.push(await req('POST', p, {}));
const unavailable = ['shell','processExecution','ssh','docker','vmWsl'].every(k => cap.json.capabilities?.[k]?.availability === 'UNAVAILABLE');
const proof = {
  gate: 'E', separateClientProcess: true, loopbackOnly: base.startsWith('http://127.0.0.1:'),
  capabilities: cap.json.capabilities, health: health.json, backup: backup.json,
  importExport: { export: exp.json, importValid: valid.json, importInvalid: invalid.json },
  forbiddenCapabilityTests: forbidden.map(x => ({status:x.status, code:x.json.code})),
  unrestrictedExecutionExposed: !unavailable || forbidden.some(x => x.status !== 403 || x.json.code !== 'CAPABILITY_NOT_EXPOSED'),
  traffic
};
fs.writeFileSync(outPath, JSON.stringify(proof, null, 2));
if (!proof.loopbackOnly || proof.unrestrictedExecutionExposed || !valid.json.ok || invalid.json.ok || !backup.json.targetRootBounded) process.exitCode = 2;
