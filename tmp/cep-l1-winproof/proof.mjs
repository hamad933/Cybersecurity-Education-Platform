import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { createHash } from 'node:crypto';
import { ConptyTerminalManager } from './conpty-terminal-manager.mjs';

const root = path.resolve(process.argv[2] || 'artifact');
fs.mkdirSync(root,{recursive:true});
const helper=path.resolve(root,'cep-win-sidecar.exe');
const manager=new ConptyTerminalManager({helperPath:helper});
const cases=[];const events=[];
manager.on('control',e=>events.push({kind:'control',...e}));manager.on('diagnostic',e=>events.push({kind:'diagnostic',...e}));manager.on('exit',e=>events.push({kind:'exit',...e}));manager.on('error-event',e=>events.push({kind:'error-event',...e}));
const record=(id,pass,details={})=>{cases.push({id,status:pass?'PASS':'FAIL',...details}); if(!pass) process.exitCode=1;};
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
async function until(fn,ms=30000,debug=null){const end=Date.now()+ms;while(Date.now()<end){const v=fn();if(v)return v;await sleep(100)}throw new Error('TIMEOUT'+(debug?':'+JSON.stringify(debug()):''));}
function text(id){return Buffer.from(manager.output(id,0).dataBase64,'base64').toString('utf8');}
async function awaitExit(id,ms=30000){return until(()=>{const s=manager.session(id);return s && ['completed','error'].includes(s.state)?s:null},ms,()=>manager.session(id))}
async function awaitReady(id,ms=30000){return until(()=>{const s=manager.session(id);if(s&&['completed','error'].includes(s.state))throw new Error('READY_ABORTED:'+JSON.stringify(s));const raw=manager._session(id);return raw?.controlHost&&raw?.controlPort&&raw?.epoch?s:null},ms,()=>({session:manager.session(id),controlHost:manager._session(id)?.controlHost,controlPort:manager._session(id)?.controlPort,events:events.slice(-20)}))}

record('ENV-WINDOWS',process.platform==='win32',{platform:process.platform,release:os.release(),arch:os.arch(),node:process.version});
record('PROVIDER-AVAILABLE',manager.available(),{descriptor:manager.descriptor()});
const systemRoot=process.env.SystemRoot||'C:\\Windows';const ps=path.join(systemRoot,'System32','WindowsPowerShell','v1.0','powershell.exe');const cmd=path.join(systemRoot,'System32','cmd.exe');const whoami=path.join(systemRoot,'System32','whoami.exe');const cwd=path.resolve(root,'cwd-proof');fs.mkdirSync(cwd,{recursive:true});

async function powershellProof(){
 const sessionId='cep-proof-powershell'; manager.open({sessionId,label:'PowerShell',executable:ps,args:['-NoLogo','-NoProfile'],cwd,env:{CEP_PROOF_ENV:'CEP_ENV_OK'},cols:100,rows:28});
 const ready=await awaitReady(sessionId); record('TERM-PS-READY',!!ready,{epoch:ready.epoch,sessionId});
 await manager.resize(sessionId,132,42); await until(()=>manager._session(sessionId).lastResize,10000,()=>manager.session(sessionId)); record('TERM-RESIZE',manager._session(sessionId).lastResize?.ok===true,{resize:manager._session(sessionId).lastResize});
 manager.write(sessionId,Buffer.from("Write-Output 'CEP_PS_OK'; Write-Output $env:CEP_PROOF_ENV; Write-Output (Get-Location).Path; exit 7\r\n",'utf8'));
 const done=await awaitExit(sessionId); const out=text(sessionId);record('TERM-PS-RAW-IO',out.includes('CEP_PS_OK')&&out.includes('CEP_ENV_OK')&&out.toLowerCase().includes(cwd.toLowerCase()),{exitCode:done.exitCode,output:out.slice(-4000)});record('TERM-PS-EXIT-CODE',done.exitCode===7,{exitCode:done.exitCode});
 const epoch1=ready.epoch; await manager.restart(sessionId); const ready2=await awaitReady(sessionId); manager.write(sessionId,Buffer.from("Write-Output 'CEP_RESTART_OK'; exit 0\r\n",'utf8')); const done2=await awaitExit(sessionId); const out2=text(sessionId);record('TERM-RESTART-STABLE-ID',ready2.id===sessionId&&ready2.restartCount===1&&ready2.epoch!==epoch1&&out2.includes('CEP_RESTART_OK')&&done2.exitCode===0,{sessionId:ready2.id,restartCount:ready2.restartCount,oldEpoch:epoch1,newEpoch:ready2.epoch});
}
async function cmdProof(){const id='cep-proof-cmd';manager.open({sessionId:id,label:'cmd.exe',executable:cmd,args:['/Q'],cols:90,rows:25});await awaitReady(id);manager.write(id,Buffer.from('echo CEP_CMD_OK & exit /b 5\r\n','utf8'));const done=await awaitExit(id);const out=text(id);record('TERM-CMD-PROFILE',out.includes('CEP_CMD_OK')&&done.exitCode===5,{exitCode:done.exitCode,output:out.slice(-2000)});}
async function arbitraryProof(){const id='cep-proof-arbitrary';manager.open({sessionId:id,label:'whoami.exe',executable:whoami,args:[],cols:80,rows:24});const done=await awaitExit(id);const out=text(id);record('TERM-ARBITRARY-EXECUTABLE',done.exitCode===0&&out.trim().length>0,{exitCode:done.exitCode,output:out.slice(-1000)});}
async function failureProof(){const id='cep-proof-missing';manager.open({sessionId:id,label:'missing',executable:path.join(root,'definitely-missing.exe'),args:[]});const done=await awaitExit(id);record('TERM-ERROR-TRUTH',done.state==='error'&&done.error?.code==='CREATE_PROCESS_FAILED',{state:done.state,error:done.error,exitCode:done.exitCode});}
try{await powershellProof();await cmdProof();await arbitraryProof();await failureProof();}catch(e){record('HARNESS-UNCAUGHT',false,{error:String(e?.stack||e),events:events.slice(-30)});}
const descriptor=manager.descriptor();record('NO-ALLOWLISTS',descriptor.commandAllowlist===false&&descriptor.executableAllowlist===false,{descriptor});
const environment={utc:new Date().toISOString(),platform:process.platform,release:os.release(),version:os.version?.()||null,arch:os.arch(),node:process.version,runnerImage:process.env.ImageOS||null,runnerName:process.env.RUNNER_NAME||null};
fs.writeFileSync(path.join(root,'terminal-test-results.json'),JSON.stringify({mission:'MISSION_WINDOWS_NATIVE_PLATFORM_TERMINAL_CONVERGENCE',classification:'MANAGED_WINDOWS_CONPTY_EVIDENCE_ONLY__NOT_INTERACTIVE_PLATFORM_ACCEPTANCE',cases,events},null,2));fs.writeFileSync(path.join(root,'environment.json'),JSON.stringify(environment,null,2));fs.writeFileSync(path.join(root,'terminal-provider-descriptor.json'),JSON.stringify(descriptor,null,2));
const files=fs.readdirSync(root).filter(n=>fs.statSync(path.join(root,n)).isFile()).sort();const hashes=files.map(n=>`${createHash('sha256').update(fs.readFileSync(path.join(root,n))).digest('hex')}  ${n}`).join('\n')+'\n';fs.writeFileSync(path.join(root,'sha256.txt'),hashes);console.log(JSON.stringify({pass:cases.every(c=>c.status==='PASS'),cases,events:events.slice(-30)},null,2));if(cases.some(c=>c.status!=='PASS'))process.exit(1);
