import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { performance } from 'node:perf_hooks';
import { DatabaseSync, backup as nodeBackup } from 'node:sqlite';

const root = path.resolve(process.argv[2] || 'evidence');
fs.mkdirSync(root, { recursive: true });
const logPath = path.join(root, 'test_output.log');
const log = (...xs) => { const line = xs.map(x => typeof x === 'string' ? x : JSON.stringify(x)).join(' '); fs.appendFileSync(logPath, line + '\n'); console.log(line); };
const sha256 = value => crypto.createHash('sha256').update(value).digest('hex');
const json = (name, value) => fs.writeFileSync(path.join(root, name), JSON.stringify(value, null, 2));
const canonical = v => Array.isArray(v) ? v.map(canonical) : v && typeof v === 'object' ? Object.fromEntries(Object.keys(v).sort().map(k => [k, canonical(v[k])])) : v;
const canonicalJson = v => JSON.stringify(canonical(v));
const tmpRoot = fs.mkdtempSync(path.join(process.env.RUNNER_TEMP || os.tmpdir(), 'cep-ps05-'));

const sourceBinding = {
  mission: 'PS05_GITHUB_HOSTED_WINDOWS_TARGET_PERSISTENCE_PROOF',
  classification: 'NON_PRODUCT_MUTATING__EXECUTION_CARRIER_ONLY__NO_SOURCE_AUTHORITY__NO_STACK_FREEZE',
  acceptedSuccessor: 'CEP-FR-W02-FINAL-CONVERGENCE-CONTROLLER-ACCEPTED-f7643a4b',
  acceptedCanonicalSourceSha256: 'f7643a4bb9e0010e431d4a4f064c526fa36911430f4fbf4867d2c26a707c2837',
  acceptedSourcePublishedToCarrier: false,
  harnessScope: ['Gate A','Gate C','Gate E'],
  excludedClaims: ['PlatformWindow/HWND/OS always-on-top','active keyboard source','real PTY/PowerShell/SSH','Docker/VM/WSL control']
};
json('source_binding.json', sourceBinding);

const environment = {
  platform: process.platform, arch: process.arch, node: process.version,
  npm: process.env.PS05_NPM_VERSION || null,
  runnerOS: process.env.RUNNER_OS || null, runnerArch: process.env.RUNNER_ARCH || null,
  imageOS: process.env.ImageOS || process.env.IMAGE_OS || null, imageVersion: process.env.ImageVersion || process.env.IMAGE_VERSION || null,
  osType: os.type(), osRelease: os.release(), osVersion: os.version(), hostname: os.hostname(), cwd: process.cwd(), tempRoot: tmpRoot,
  githubRunId: process.env.GITHUB_RUN_ID || null, githubRunAttempt: process.env.GITHUB_RUN_ATTEMPT || null,
  githubSha: process.env.GITHUB_SHA || null, githubRef: process.env.GITHUB_REF || null
};
json('environment.json', environment);

let BetterSqlite3 = null; let betterImportError = null; let betterVersion = null; let betterMainPath = null;
try {
  const m = await import('better-sqlite3'); BetterSqlite3 = m.default;
  try {
    const require = createRequire(import.meta.url); betterMainPath = require.resolve('better-sqlite3'); let d = path.dirname(betterMainPath);
    for (let i=0;i<6;i++) { const pj=path.join(d,'package.json'); if(fs.existsSync(pj)){ const meta=JSON.parse(fs.readFileSync(pj,'utf8')); if(meta.name==='better-sqlite3'){ betterVersion=meta.version; break; } } d=path.dirname(d); }
  } catch {}
} catch (e) { betterImportError = {name:e?.name, code:e?.code || null, message:String(e?.message || e)}; }

class Adapter {
  constructor(driverId, dbPath) {
    this.driverId = driverId; this.dbPath = dbPath;
    if (driverId === 'node:sqlite') this.db = new DatabaseSync(dbPath);
    else if (BetterSqlite3) this.db = new BetterSqlite3(dbPath, { timeout: 150 });
    else throw Object.assign(new Error('BETTER_SQLITE3_UNAVAILABLE'), {code:'MODULE_UNAVAILABLE'});
    this.exec('PRAGMA foreign_keys=ON; PRAGMA journal_mode=WAL; PRAGMA synchronous=FULL; PRAGMA busy_timeout=150;');
  }
  exec(sql) { return this.db.exec(sql); }
  prepare(sql) { return this.db.prepare(sql); }
  run(stmt, ...args) { return stmt.run(...args); }
  get(stmt, ...args) { return stmt.get(...args); }
  all(stmt, ...args) { return stmt.all(...args); }
  close() { this.db.close(); }
  tx(work) { this.exec('BEGIN IMMEDIATE'); try { const x = work(); this.exec('COMMIT'); return x; } catch(e) { try{this.exec('ROLLBACK');}catch{} throw e; } }
  async backupTo(dest) { if (this.driverId === 'node:sqlite') { const pages = await nodeBackup(this.db, dest); return {ok:true,pages}; } const r = await this.db.backup(dest); return {ok:true,pages:r.totalPages,remainingPages:r.remainingPages}; }
}

async function gateA(driverId) {
  const out = {driverId, executable:false, requirements:{}, errors:[]};
  if (driverId === 'better-sqlite3' && !BetterSqlite3) { out.unavailable = betterImportError; return out; }
  const dir = fs.mkdtempSync(path.join(tmpRoot, `gate-a-${driverId.replace(/[:]/g,'-')}-`)); const dbp = path.join(dir,'gate-a.sqlite'); let db;
  try {
    db = new Adapter(driverId, dbp); out.executable = true;
    db.exec('CREATE TABLE t(id INTEGER PRIMARY KEY, value TEXT UNIQUE); CREATE VIRTUAL TABLE f USING fts5(value, tokenize="unicode61");');
    const ins = db.prepare('INSERT INTO t(value) VALUES (?)'); const get = db.prepare('SELECT value FROM t WHERE id=?');
    db.tx(() => { db.run(ins,'alpha'); db.run(db.prepare('INSERT INTO f(value) VALUES (?)'),'مرحبا cybersecurity'); });
    out.requirements.transactions = true; out.requirements.preparedStatements = db.get(get,1)?.value === 'alpha';
    out.requirements.fts5 = Number(db.get(db.prepare("SELECT count(*) AS n FROM f WHERE f MATCH 'cybersecurity'")).n) === 1;
    try { db.run(ins,'alpha'); out.errorSemantics = {caught:false}; } catch(e) { out.errorSemantics = {caught:true,name:e?.name,code:e?.code || null,message:String(e?.message || e)}; }
    out.requirements.errorSemantics = out.errorSemantics.caught;
    const backupPath = path.join(dir,'backup.sqlite'); const t0 = performance.now(); out.backup = {...await db.backupTo(backupPath), ms: performance.now()-t0, exists:fs.existsSync(backupPath)}; out.requirements.backup = out.backup.ok && out.backup.exists;
    const lock1 = new Adapter(driverId, path.join(dir,'lock.sqlite')); const lock2 = new Adapter(driverId, path.join(dir,'lock.sqlite')); lock1.exec('CREATE TABLE IF NOT EXISTS l(id INTEGER PRIMARY KEY, v TEXT);'); let busy = false; let lockError = null;
    try { lock1.exec('BEGIN IMMEDIATE'); lock1.exec("INSERT INTO l(v) VALUES ('held')"); try { lock2.exec("INSERT INTO l(v) VALUES ('blocked')"); } catch(e) { busy=true; lockError={name:e?.name,code:e?.code||null,message:String(e?.message||e)}; } } finally { try{lock1.exec('ROLLBACK');}catch{} lock2.close(); lock1.close(); }
    out.windowsFileLock = {busyObserved:busy,error:lockError}; out.requirements.walLocking = busy;
    db.exec('CREATE TABLE bench(id INTEGER PRIMARY KEY, value TEXT); CREATE VIRTUAL TABLE bench_fts USING fts5(value);'); const bIns = db.prepare('INSERT INTO bench(value) VALUES (?)'); const fIns = db.prepare('INSERT INTO bench_fts(value) VALUES (?)');
    const b0 = performance.now(); db.tx(() => { for(let i=0;i<10000;i++){ const v=`row-${i}-cybersecurity`; db.run(bIns,v); db.run(fIns,v); } }); const insertMs=performance.now()-b0;
    const point = db.prepare('SELECT value FROM bench WHERE id=?'); const r0=performance.now(); for(let i=1;i<=1000;i++) db.get(point,i); const readsMs=performance.now()-r0;
    const f0=performance.now(); const count=Number(db.get(db.prepare("SELECT count(*) AS n FROM bench_fts WHERE bench_fts MATCH 'cybersecurity'")).n); const ftsMs=performance.now()-f0;
    out.benchmark={rows:10000,transactionInsertAndFtsMs:insertMs,preparedPointReads1000Ms:readsMs,ftsCountQueryMs:ftsMs,ftsCount:count}; out.requirements.benchmark = count===10000;
    db.close(); db = null; const reopen = new Adapter(driverId,dbp); out.cleanReopen = reopen.get(reopen.prepare('SELECT value FROM t WHERE id=1'))?.value==='alpha'; reopen.close(); out.requirements.cleanReopen = out.cleanReopen;
    out.pass = Object.values(out.requirements).every(Boolean);
  } catch(e) { out.errors.push({name:e?.name,code:e?.code||null,message:String(e?.message||e),stack:String(e?.stack||'')}); out.pass=false; try{db?.close();}catch{} }
  return out;
}

function makeDoc(title, body='baseline') { return {id:'ps05-doc',revision:'r1',title,blocks:[{id:'p1',type:'paragraph',html:body}]}; }
async function gateC(driverId) {
  const out={driverId, requirements:{}}; if(driverId==='better-sqlite3'&&!BetterSqlite3){out.pass=false;out.unavailable=betterImportError;return out;}
  const dir=fs.mkdtempSync(path.join(tmpRoot,`gate-c-${driverId.replace(/[:]/g,'-')}-`)); const dbp=path.join(dir,'persistence.sqlite'); let db;
  const open=()=>new Adapter(driverId,dbp);
  const schema=`CREATE TABLE IF NOT EXISTS docs(id TEXT PRIMARY KEY,current_rev TEXT NOT NULL); CREATE TABLE IF NOT EXISTS revs(doc_id TEXT,rev TEXT,parent TEXT,content TEXT,digest TEXT,PRIMARY KEY(doc_id,rev)); CREATE TABLE IF NOT EXISTS drafts(doc_id TEXT PRIMARY KEY,base_rev TEXT,content TEXT,digest TEXT,dirty INTEGER); CREATE TABLE IF NOT EXISTS recovery(id TEXT PRIMARY KEY,doc_id TEXT,base_rev TEXT,content TEXT,digest TEXT);`;
  const readCurrent=d=>d.get(d.prepare('SELECT current_rev FROM docs WHERE id=?'),'ps05-doc')?.current_rev;
  const revCount=d=>Number(d.get(d.prepare('SELECT count(*) AS n FROM revs WHERE doc_id=?'),'ps05-doc')?.n||0);
  const save=(d,doc,expected,{failBeforeCommit=false}={})=>{ const payload=canonicalJson(doc), digest=sha256(payload); const before=revCount(d); try{return d.tx(()=>{const cur=readCurrent(d);if(cur!==expected)throw Object.assign(new Error(`STALE_BASE:${expected}->${cur}`),{code:'STALE_BASE'});const rev=`r${before+1}`;d.run(d.prepare('INSERT INTO revs VALUES (?,?,?,?,?)'),'ps05-doc',rev,cur,payload,digest);if(failBeforeCommit)throw Object.assign(new Error('INJECTED_BEFORE_COMMIT_FAILURE'),{code:'INJECTED_BEFORE_COMMIT_FAILURE'});d.run(d.prepare('UPDATE docs SET current_rev=? WHERE id=?'),rev,'ps05-doc');return {ok:true,revision:rev,digest};});}catch(e){return {ok:false,code:e?.code||null,message:String(e?.message||e)}}};
  try {
    db=open(); db.exec(schema); const base=makeDoc('baseline'); const baseRaw=canonicalJson(base); db.tx(()=>{db.run(db.prepare('INSERT INTO docs VALUES (?,?)'),'ps05-doc','r1');db.run(db.prepare('INSERT INTO revs VALUES (?,?,?,?,?)'),'ps05-doc','r1',null,baseRaw,sha256(baseRaw));});
    let dirty=true; const explicit=makeDoc('explicit save','saved'); const s=save(db,explicit,'r1'); if(s.ok) dirty=false; out.explicitSave={receipt:s,dirtyAfter:dirty,current:readCurrent(db),revisions:revCount(db)}; out.requirements.explicitSaveSuccessAfterCommit=s.ok&&readCurrent(db)==='r2'&&!dirty;
    const staleBefore=revCount(db); const stale=save(db,makeDoc('stale','bad'),'r1'); out.staleBase={receipt:stale,before:staleBefore,after:revCount(db),current:readCurrent(db)}; out.requirements.staleBaseRejectedAtomically=!stale.ok&&stale.code==='STALE_BASE'&&staleBefore===revCount(db)&&readCurrent(db)==='r2';
    dirty=true; const failBefore=revCount(db); const fail=save(db,makeDoc('failure','dirty'),'r2',{failBeforeCommit:true}); out.failure={receipt:fail,before:failBefore,after:revCount(db),current:readCurrent(db),dirtyAfterFailure:dirty}; out.requirements.failedSaveKeepsDirtyAndRevision=!fail.ok&&failBefore===revCount(db)&&readCurrent(db)==='r2'&&dirty;
    const draft=makeDoc('autosave','draft only'); const draw=canonicalJson(draft); db.run(db.prepare('INSERT OR REPLACE INTO drafts VALUES (?,?,?,?,1)'),'ps05-doc','r2',draw,sha256(draw)); out.autosave={committedRevision:readCurrent(db),revisions:revCount(db),dirty:true}; out.requirements.autosaveIsNotSave=readCurrent(db)==='r2'&&revCount(db)===2;
    db.run(db.prepare('INSERT INTO recovery VALUES (?,?,?,?,?)'),'rec-1','ps05-doc','r2',draw,sha256(draw)); db.close(); db=null;
    db=open(); db.exec(schema); const recovery=db.get(db.prepare('SELECT * FROM recovery WHERE id=?'),'rec-1'); out.recoveryAfterRestart={found:!!recovery,current:readCurrent(db)}; out.requirements.recoverySurvivesRestart=!!recovery&&readCurrent(db)==='r2';
    const restored=JSON.parse(recovery.content); const restoredSave=save(db,restored,'r2'); const row=db.get(db.prepare('SELECT content,digest FROM revs WHERE doc_id=? AND rev=?'),'ps05-doc',restoredSave.revision||''); out.restoreThenSave={receipt:restoredSave,current:readCurrent(db)}; out.requirements.restoredRecoveryLaterSavesNewRevision=restoredSave.ok&&readCurrent(db)==='r3'; out.requirements.exactReadback=!!row&&row.content===canonicalJson(restored)&&row.digest===sha256(canonicalJson(restored)); db.close(); db=null;
    const corrupt=path.join(dir,'corrupt.sqlite'); fs.writeFileSync(corrupt,'not a database'); try{const c=new Adapter(driverId,corrupt);c.exec('PRAGMA integrity_check');c.close();out.corruptionTruth={detected:false};}catch(e){out.corruptionTruth={detected:true,name:e?.name,code:e?.code||null,message:String(e?.message||e)}} out.requirements.corruptionTruth=out.corruptionTruth.detected;
    out.pass=Object.values(out.requirements).every(Boolean);
  } catch(e){out.error={name:e?.name,code:e?.code||null,message:String(e?.message||e),stack:String(e?.stack||'')};out.pass=false;try{db?.close();}catch{}}
  return out;
}

async function gateE() {
  const staging=path.join(tmpRoot,'gate-e-staging'); fs.mkdirSync(staging,{recursive:true}); const clientOut=path.join(root,'local_runtime_gate_e.json');
  const hostPath=path.join(process.cwd(),'ps05_windows_proof','host.ts'); const clientPath=path.join(process.cwd(),'ps05_windows_proof','client.ts');
  const host=spawn(process.execPath,['--experimental-strip-types',hostPath,staging],{stdio:['ignore','pipe','pipe']}); let ready=null; let stderr='';
  host.stderr.on('data',d=>stderr+=d.toString());
  const line=await new Promise((resolve,reject)=>{let buf='';const timer=setTimeout(()=>reject(new Error('HOST_READY_TIMEOUT')),10000);host.stdout.on('data',d=>{buf+=d.toString();const i=buf.indexOf('\n');if(i>=0){clearTimeout(timer);resolve(buf.slice(0,i));}});host.on('exit',c=>{if(!ready&&c!==0){clearTimeout(timer);reject(new Error(`HOST_EXIT_${c}:${stderr}`));}})});
  ready=JSON.parse(line); const base=`http://${ready.host}:${ready.port}`;
  const client=spawn(process.execPath,['--experimental-strip-types',clientPath,base,clientOut],{stdio:['ignore','pipe','pipe']}); let cstdout='',cstderr=''; client.stdout.on('data',d=>cstdout+=d.toString());client.stderr.on('data',d=>cstderr+=d.toString()); const code=await new Promise(r=>client.on('exit',r)); host.kill();
  const proof=JSON.parse(fs.readFileSync(clientOut,'utf8')); proof.host={...ready,bindAddress:'127.0.0.1',clientExitCode:code,clientStdout:cstdout,clientStderr:cstderr,hostStderr:stderr}; proof.pass=code===0&&proof.loopbackOnly&&!proof.unrestrictedExecutionExposed; json('local_runtime_gate_e.json',proof); return proof;
}

const installLog = path.join(root,'npm-install.log'); const firstCodeFile=path.join(root,'npm-install-first-exit-code.txt'); const secondCodeFile=path.join(root,'npm-install-second-exit-code.txt');
const installReceipts={npmVersion:environment.npm, cleanInstallAttempts:[firstCodeFile,secondCodeFile].map((f,i)=>({attempt:i+1,exitCode:fs.existsSync(f)?Number(fs.readFileSync(f,'utf8').trim()):null})), logPresent:fs.existsSync(installLog), betterSqlite3:{imported:!!BetterSqlite3,resolvedVersion:betterVersion,resolvedMainPath:betterMainPath,importError:betterImportError}, nodeSqlite:{builtIn:true,nodeVersion:process.version}};
json('install_receipts.json',installReceipts);

const drivers=['node:sqlite','better-sqlite3']; const driverMatrix={}; const gateCResults={}; const benchmarks={};
for(const d of drivers){ log('GATE_A_START',d); driverMatrix[d]=await gateA(d); benchmarks[d]=driverMatrix[d].benchmark||null; log('GATE_A_RESULT',d,driverMatrix[d].pass); log('GATE_C_START',d); gateCResults[d]=await gateC(d); log('GATE_C_RESULT',d,gateCResults[d].pass); }
json('driver_matrix.json',{decisionCeiling:'PREFERRED_FOR_CONTROLLER_REVIEW_ONLY__NO_DRIVER_SELECTION__NO_STACK_FREEZE',singleProductionDriverLaw:true,drivers:driverMatrix});
json('save_autosave_recovery.json',{truthInvariant:'AUTOSAVE != EXPLICIT SAVE != RECOVERY',semanticOwner:'StructuredTransactionHistoryRecoveryOwner',providerOwnsSemantics:false,drivers:gateCResults});
json('benchmark.json',{scope:'CEP_SCALE_BOUNDED_10000_ROWS',winnerClaim:false,drivers:benchmarks});
log('GATE_E_START'); let gateEResult; try{gateEResult=await gateE();log('GATE_E_RESULT',gateEResult.pass);}catch(e){gateEResult={pass:false,error:{name:e?.name,message:String(e?.message||e),stack:String(e?.stack||'')}};json('local_runtime_gate_e.json',gateEResult);log('GATE_E_ERROR',gateEResult.error);}

const gateAPass=drivers.every(d=>driverMatrix[d]?.pass); const gateCPass=drivers.every(d=>gateCResults[d]?.pass); const summary={
  mission:sourceBinding.mission, githubSha:environment.githubSha, runner:{os:environment.runnerOS,arch:environment.runnerArch,imageOS:environment.imageOS,imageVersion:environment.imageVersion},
  gateA:gateAPass ? 'PASS' : 'FAIL', gateC:gateCPass ? 'PASS':'FAIL', gateE:gateEResult.pass?'PASS':'FAIL',
  allRequiredDriversMeasured:drivers.every(d=>driverMatrix[d]?.executable), overallPass:gateAPass&&gateCPass&&gateEResult.pass,
  adjudication:'PREFERRED_FOR_CONTROLLER_REVIEW', stackFrozen:false, productionDriverSelected:false,
  nativeDesktopCapabilitiesCertified:false, notes:[]
};
if(!summary.allRequiredDriversMeasured) summary.notes.push('One or more required drivers were unavailable; NOT_MEASURED is not rejection.');
json('summary.json',summary);
const checksumTargets=['environment.json','source_binding.json','driver_matrix.json','save_autosave_recovery.json','local_runtime_gate_e.json','benchmark.json','install_receipts.json','test_output.log','summary.json','npm-install.log','npm-install-first-exit-code.txt','npm-install-second-exit-code.txt'].filter(f=>fs.existsSync(path.join(root,f)));
const checksums={algorithm:'SHA-256',files:{}}; for(const f of checksumTargets) checksums.files[f]=sha256(fs.readFileSync(path.join(root,f))); json('checksums.json',checksums);
log('SUMMARY',summary);
process.exitCode=summary.overallPass?0:1;
