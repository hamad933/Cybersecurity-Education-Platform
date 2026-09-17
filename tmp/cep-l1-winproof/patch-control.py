from pathlib import Path
import re

cpp=Path('tmp/cep-l1-winproof/main.cpp')
s=cpp.read_text(encoding='utf-8')
s=s.replace('#include <windows.h>','#include <winsock2.h>\n#include <ws2tcpip.h>\n#include <windows.h>')
start=s.index('  std::wstring pipe=')
end=s.index('  emitErr("{\\\"event\\\":\\\"ready', start)
new=r'''  WSADATA wsa{}; if(WSAStartup(MAKEWORD(2,2),&wsa)!=0){ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"WSA_STARTUP_FAILED\"}");return 7;}
  SOCKET controlSocket=socket(AF_INET,SOCK_STREAM,IPPROTO_TCP); if(controlSocket==INVALID_SOCKET){WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"CONTROL_SOCKET_CREATE_FAILED\"}");return 8;}
  sockaddr_in controlAddr{}; controlAddr.sin_family=AF_INET; controlAddr.sin_addr.s_addr=htonl(INADDR_LOOPBACK); controlAddr.sin_port=0;
  if(bind(controlSocket,(sockaddr*)&controlAddr,sizeof(controlAddr))==SOCKET_ERROR||listen(controlSocket,1)==SOCKET_ERROR){closesocket(controlSocket);WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"CONTROL_SOCKET_BIND_FAILED\"}");return 9;}
  int controlAddrLen=sizeof(controlAddr);getsockname(controlSocket,(sockaddr*)&controlAddr,&controlAddrLen);unsigned controlPort=ntohs(controlAddr.sin_port);
  std::atomic<bool> closing=false;
  std::thread output([&](){char buf[8192];DWORD got=0,wrote=0;HANDLE stdOut=GetStdHandle(STD_OUTPUT_HANDLE);while(ReadFile(outRead,buf,sizeof(buf),&got,nullptr)&&got){if(!WriteFile(stdOut,buf,got,&wrote,nullptr))break;}});
  std::thread input([&](){char buf[4096];DWORD got=0,wrote=0;HANDLE stdIn=GetStdHandle(STD_INPUT_HANDLE);while(!closing&&ReadFile(stdIn,buf,sizeof(buf),&got,nullptr)&&got){if(!WriteFile(inWrite,buf,got,&wrote,nullptr))break;}});
  std::thread control([&](){while(!closing){SOCKET client=accept(controlSocket,nullptr,nullptr);if(client==INVALID_SOCKET)break;char b[4096];int n=recv(client,b,sizeof(b)-1,0);if(n>0){b[n]=0;std::istringstream ss(std::string(b,n));std::string op;ss>>op;if(op=="RESIZE"){int c=0,r=0;ss>>c>>r;COORD z{(SHORT)std::max(1,c),(SHORT)std::max(1,r)};HRESULT rr=ResizePseudoConsole(pc,z);emitErr("{\"event\":\"resize\",\"ok\":"+std::string(SUCCEEDED(rr)?"true":"false")+",\"cols\":"+std::to_string(c)+",\"rows\":"+std::to_string(r)+"}");}else if(op=="CLOSE"){closing=true;TerminateProcess(pi.hProcess,130);}}shutdown(client,SD_BOTH);closesocket(client);}});
'''
s=s[:start]+new+s[end:]
lines=s.splitlines()
for i,l in enumerate(lines):
    if 'emitErr("{\\\"event\\\":\\\"ready' in l:
        lines[i]='  emitErr("{\\\"event\\\":\\\"ready\\\",\\\"providerId\\\":\\\"cep-win32-conpty\\\",\\\"providerVersion\\\":\\\"1.0.0\\\",\\\"providerEpoch\\\":\\\""+jesc(epoch())+"\\\",\\\"sessionId\\\":\\\""+jesc(narrow(a.session))+"\\\",\\\"controlHost\\\":\\\"127.0.0.1\\\",\\\"controlPort\\\":"+std::to_string(controlPort)+",\\\"executable\\\":\\\""+jesc(narrow(a.exe))+"\\\"}");'
        break
s='\n'.join(lines)+'\n'
a=s.index('  if(control.joinable())', s.index('emitErr("{\\\"event\\\":\\\"ready'))
b=s.index('  if(input.joinable())', a)
s=s[:a]+'  closesocket(controlSocket); if(control.joinable())control.join(); WSACleanup();\n'+s[b:]
Path('tmp/cep-l1-winproof/main-fixed.cpp').write_text(s,encoding='utf-8')

m=Path('tmp/cep-l1-winproof/conpty-terminal-manager.mjs')
t=m.read_text(encoding='utf-8')
t=t.replace('controlPipe:null','controlHost:null,controlPort:null')
t=t.replace('session.controlPipe=e.controlPipe','session.controlHost=e.controlHost;session.controlPort=e.controlPort')
t=t.replace("if(!s.controlPipe)throw Error('TERMINAL_CONTROL_PIPE_NOT_READY');await control(s.controlPipe,", "if(!s.controlHost||!s.controlPort)throw Error('TERMINAL_CONTROL_CHANNEL_NOT_READY');await control(s.controlHost,s.controlPort,")
t=t.replace("if(s.controlPipe)await control(s.controlPipe,'CLOSE')", "if(s.controlHost&&s.controlPort)await control(s.controlHost,s.controlPort,'CLOSE')")
start=t.index('function control(')
end=t.index('export class',start)
t=t[:start]+"function control(host,port,line){return new Promise((resolve,reject)=>{const socket=net.createConnection({host,port},()=>socket.end(line+'\\n'));socket.once('error',reject);socket.once('close',()=>resolve(true))})}\n"+t[end:]
m.write_text(t,encoding='utf-8')

p=Path('tmp/cep-l1-winproof/proof.mjs')
u=p.read_text(encoding='utf-8').replace('controlPipe','controlPort')
p.write_text(u,encoding='utf-8')
