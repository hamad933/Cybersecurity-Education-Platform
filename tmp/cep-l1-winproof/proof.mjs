import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import {ConptyTerminalManager} from './conpty-terminal-manager.mjs';
const root=path.resolve(process.argv[2]||'artifact');fs.mkdirSync(root,{recursive:true});
const helper=path.resolve(root,'cep-win-sidecar.exe');const manager=new ConptyTerminalManager({helperPath:helper});const events=[];
for(const ev of ['control','diagnostic','exit','error-event'])manager.on(ev,e=>events.push({kind:ev,...e}));
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
async function until(fn,ms=10000){const end=Date.now()+ms;while(Date.now()<end){const v=fn();if(v)return v;await sleep(100)}throw new Error('TIMEOUT')}
function text(id){return Buffer.from(manager.output(id,0).dataBase64,'base64').toString('utf8')}
async function ready(id){return until(()=>{const s=manager._session(id);return s?.controlHost&&s?.inputHost&&s?.epoch?s:null},10000)}
function rec(vk,sc,uc,kd,cs=0,rc=1){return `\x1b[${vk};${sc};${uc};${kd};${cs};${rc}_`}
const scans={a:30,b:48,c:46,d:32,e:18,f:33,g:34,h:35,i:23,j:36,k:37,l:38,m:50,n:49,o:24,p:25,q:16,r:19,s:31,t:20,u:22,v:47,w:17,x:45,y:21,z:44,'1':2,'2':3,'3':4,'4':5,'5':6,'6':7,'7':8,'8':9,'9':10,'0':11,' ':57};
function encPacketDown(s){let o='';for(const ch of s){const u=ch.charCodeAt(0);if(ch==='\r')o+=rec(13,28,13,1);else o+=rec(231,0,u,1)}return Buffer.from(o,'utf8')}
function encPacketPair(s){let o='';for(const ch of s){const u=ch.charCodeAt(0);if(ch==='\r')o+=rec(13,28,13,1)+rec(13,28,0,0);else o+=rec(231,0,u,1)+rec(231,0,0,0)}return Buffer.from(o,'utf8')}
function encPhysicalDown(s){let o='';for(const ch of s){if(ch==='\r'){o+=rec(13,28,13,1);continue}const lc=ch.toLowerCase(),vk=/[a-z]/.test(lc)?lc.toUpperCase().charCodeAt(0):ch.charCodeAt(0),sc=scans[lc]||0;o+=rec(vk,sc,ch.charCodeAt(0),1)}return Buffer.from(o,'utf8')}
const systemRoot=process.env.SystemRoot||'C:\\Windows',cmd=path.join(systemRoot,'System32','cmd.exe');
const variants=[['packet-down',encPacketDown],['packet-pair',encPacketPair],['physical-down',encPhysicalDown]];const results=[];
for(const [name,encode] of variants){const id='diag-'+name;manager.open({sessionId:id,label:name,executable:cmd,args:['/D','/Q'],cols:90,rows:25});const s=await ready(id);await sleep(400);const startup=text(id);const payload=encode(`echo CEP_${name.replaceAll('-','_').toUpperCase()}\r`);await manager.write(id,payload);await sleep(2500);const out=text(id);const iw=events.filter(e=>e.sessionId===id&&e.event?.event==='input-write').map(e=>e.event);results.push({name,epoch:s.epoch,requested9001:startup.includes('\x1b[?9001h'),payloadBytes:payload.length,inputWrites:iw,output:out,matched:out.includes('CEP_'+name.replaceAll('-','_').toUpperCase())});try{await manager.close(id)}catch{}await sleep(300)}
const pass=results.some(r=>r.matched);fs.writeFileSync(path.join(root,'terminal-input-diagnostic.json'),JSON.stringify({pass,platform:process.platform,release:os.release(),results,events},null,2));console.log(JSON.stringify({pass,results:results.map(r=>({name:r.name,matched:r.matched,requested9001:r.requested9001,payloadBytes:r.payloadBytes,inputWrites:r.inputWrites,tail:r.output.slice(-1200)}))},null,2));if(!pass)process.exit(1);
