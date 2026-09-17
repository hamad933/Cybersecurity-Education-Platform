from pathlib import Path

cpp=Path('tmp/cep-l1-winproof/main.cpp')
s=cpp.read_text(encoding='utf-8')
start=s.index('  std::wstring pipe=')
end=s.index('  emitErr("{\\\"event\\\":\\\"ready', start)
new=r'''  wchar_t tempPath[MAX_PATH]{}; GetTempPathW(MAX_PATH,tempPath);
  std::wstring controlFile=std::wstring(tempPath)+L"cep-conpty-"+std::to_wstring(GetCurrentProcessId())+L"-"+std::to_wstring(GetTickCount64())+L".ctl";
  DeleteFileW(controlFile.c_str());
  std::atomic<bool> closing=false;
  std::thread output([&](){char buf[8192];DWORD got=0,wrote=0;HANDLE stdOut=GetStdHandle(STD_OUTPUT_HANDLE);while(ReadFile(outRead,buf,sizeof(buf),&got,nullptr)&&got){if(!WriteFile(stdOut,buf,got,&wrote,nullptr))break;}});
  std::thread input([&](){char buf[4096];DWORD got=0,wrote=0;HANDLE stdIn=GetStdHandle(STD_INPUT_HANDLE);while(!closing&&ReadFile(stdIn,buf,sizeof(buf),&got,nullptr)&&got){if(!WriteFile(inWrite,buf,got,&wrote,nullptr))break;}});
  std::thread control([&](){while(!closing){HANDLE h=CreateFileW(controlFile.c_str(),GENERIC_READ,FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_SHARE_DELETE,nullptr,OPEN_EXISTING,FILE_ATTRIBUTE_NORMAL,nullptr);if(h!=INVALID_HANDLE_VALUE){char b[4096];DWORD n=0;BOOL readOk=ReadFile(h,b,sizeof(b)-1,&n,nullptr);CloseHandle(h);DeleteFileW(controlFile.c_str());if(readOk&&n){b[n]=0;std::istringstream ss(std::string(b,n));std::string op;ss>>op;if(op=="RESIZE"){int c=0,r=0;ss>>c>>r;COORD z{(SHORT)std::max(1,c),(SHORT)std::max(1,r)};HRESULT rr=ResizePseudoConsole(pc,z);emitErr("{\"event\":\"resize\",\"ok\":"+std::string(SUCCEEDED(rr)?"true":"false")+",\"cols\":"+std::to_string(c)+",\"rows\":"+std::to_string(r)+"}");}else if(op=="CLOSE"){closing=true;TerminateProcess(pi.hProcess,130);}}}else{Sleep(25);}}});
'''
s=s[:start]+new+s[end:]
s=s.replace('\\\"controlPipe\\\":\\\""+jesc(narrow(pipe))+"\\\"','\\\"controlFile\\\":\\\""+jesc(narrow(controlFile))+"\\\"')
a=s.index('  if(control.joinable())', s.index('emitErr("{\\\"event\\\":\\\"ready'))
b=s.index('  if(input.joinable())', a)
s=s[:a]+'  if(control.joinable())control.join(); DeleteFileW(controlFile.c_str());\n'+s[b:]
Path('tmp/cep-l1-winproof/main-fixed.cpp').write_text(s,encoding='utf-8')

m=Path('tmp/cep-l1-winproof/conpty-terminal-manager.mjs')
t=m.read_text(encoding='utf-8')
t=t.replace("import net from 'node:net';", "import {writeFile, access} from 'node:fs/promises';")
old="function control(pipe,line){return new Promise((resolve,reject)=>{const socket=net.createConnection(pipe,()=>{socket.end(line+'\\n')});socket.once('error',reject);socket.once('close',()=>resolve(true))})}"
new="async function control(file,line){await writeFile(file,line+'\\n','utf8');const end=Date.now()+5000;while(Date.now()<end){try{await access(file);await new Promise(r=>setTimeout(r,25));}catch{return true}}throw Error('TERMINAL_CONTROL_TIMEOUT')}"
t=t.replace(old,new).replace('controlPipe:null','controlFile:null').replace('session.controlPipe=e.controlPipe','session.controlFile=e.controlFile').replace('s.controlPipe','s.controlFile').replace('control(s.controlPipe','control(s.controlFile')
m.write_text(t,encoding='utf-8')

p=Path('tmp/cep-l1-winproof/proof.mjs')
u=p.read_text(encoding='utf-8').replace('controlPipe','controlFile')
p.write_text(u,encoding='utf-8')
