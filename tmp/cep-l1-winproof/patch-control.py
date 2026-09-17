from pathlib import Path

cpp=Path('tmp/cep-l1-winproof/main.cpp')
s=cpp.read_text(encoding='utf-8')
s=s.replace('#include <windows.h>','#include <winsock2.h>\n#include <ws2tcpip.h>\n#include <windows.h>',1)
start=s.index('  std::wstring pipe=')
end=s.index('  emitErr("{\\\"event\\\":\\\"ready', start)
new=r'''  WSADATA wsa{}; if(WSAStartup(MAKEWORD(2,2),&wsa)!=0){ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"WSA_STARTUP_FAILED\"}");return 7;}
  SOCKET controlSocket=socket(AF_INET,SOCK_STREAM,IPPROTO_TCP); if(controlSocket==INVALID_SOCKET){WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"CONTROL_SOCKET_CREATE_FAILED\"}");return 8;}
  SOCKET inputSocket=socket(AF_INET,SOCK_STREAM,IPPROTO_TCP); if(inputSocket==INVALID_SOCKET){closesocket(controlSocket);WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"INPUT_SOCKET_CREATE_FAILED\"}");return 8;}
  sockaddr_in controlAddr{}; controlAddr.sin_family=AF_INET; controlAddr.sin_addr.s_addr=htonl(INADDR_LOOPBACK); controlAddr.sin_port=0;
  sockaddr_in inputAddr{}; inputAddr.sin_family=AF_INET; inputAddr.sin_addr.s_addr=htonl(INADDR_LOOPBACK); inputAddr.sin_port=0;
  if(bind(controlSocket,(sockaddr*)&controlAddr,sizeof(controlAddr))==SOCKET_ERROR||listen(controlSocket,4)==SOCKET_ERROR){closesocket(inputSocket);closesocket(controlSocket);WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"CONTROL_SOCKET_BIND_FAILED\"}");return 9;}
  if(bind(inputSocket,(sockaddr*)&inputAddr,sizeof(inputAddr))==SOCKET_ERROR||listen(inputSocket,4)==SOCKET_ERROR){closesocket(inputSocket);closesocket(controlSocket);WSACleanup();ClosePseudoConsole(pc);TerminateProcess(pi.hProcess,1);CloseHandle(pi.hProcess);emitErr("{\"event\":\"error\",\"code\":\"INPUT_SOCKET_BIND_FAILED\"}");return 9;}
  int controlAddrLen=sizeof(controlAddr);getsockname(controlSocket,(sockaddr*)&controlAddr,&controlAddrLen);unsigned controlPort=ntohs(controlAddr.sin_port);
  int inputAddrLen=sizeof(inputAddr);getsockname(inputSocket,(sockaddr*)&inputAddr,&inputAddrLen);unsigned inputPort=ntohs(inputAddr.sin_port);
  std::atomic<bool> closing=false;
  std::thread output([&](){char buf[8192];DWORD got=0,wrote=0;HANDLE stdOut=GetStdHandle(STD_OUTPUT_HANDLE);while(ReadFile(outRead,buf,sizeof(buf),&got,nullptr)&&got){if(!WriteFile(stdOut,buf,got,&wrote,nullptr))break;}});
  std::thread input([&](){while(!closing){SOCKET client=accept(inputSocket,nullptr,nullptr);if(client==INVALID_SOCKET)break;char b[8192];int n=0;DWORD wrote=0;while((n=recv(client,b,sizeof(b),0))>0){BOOL okWrite=WriteFile(inWrite,b,(DWORD)n,&wrote,nullptr);emitErr("{\"event\":\"input-write\",\"bytes\":"+std::to_string(n)+",\"written\":"+std::to_string(wrote)+",\"ok\":"+std::string(okWrite?"true":"false")+"}");if(!okWrite||wrote!=(DWORD)n)break;}shutdown(client,SD_BOTH);closesocket(client);}});
  std::thread control([&](){while(!closing){SOCKET client=accept(controlSocket,nullptr,nullptr);if(client==INVALID_SOCKET)break;char b[4096];int n=recv(client,b,sizeof(b)-1,0);if(n>0){b[n]=0;std::istringstream ss(std::string(b,n));std::string op;ss>>op;if(op=="RESIZE"){int c=0,r=0;ss>>c>>r;COORD z{(SHORT)std::max(1,c),(SHORT)std::max(1,r)};HRESULT rr=ResizePseudoConsole(pc,z);emitErr("{\"event\":\"resize\",\"ok\":"+std::string(SUCCEEDED(rr)?"true":"false")+",\"cols\":"+std::to_string(c)+",\"rows\":"+std::to_string(r)+"}");}else if(op=="CLOSE"){closing=true;TerminateProcess(pi.hProcess,130);}}shutdown(client,SD_BOTH);closesocket(client);}});
'''
s=s[:start]+new+s[end:]
lines=s.splitlines()
for i,l in enumerate(lines):
    if 'emitErr("{\\\"event\\\":\\\"ready' in l:
        lines[i]='  emitErr("{\\\"event\\\":\\\"ready\\\",\\\"providerId\\\":\\\"cep-win32-conpty\\\",\\\"providerVersion\\\":\\\"1.0.0\\\",\\\"providerEpoch\\\":\\\""+jesc(epoch())+"\\\",\\\"sessionId\\\":\\\""+jesc(narrow(a.session))+"\\\",\\\"controlHost\\\":\\\"127.0.0.1\\\",\\\"controlPort\\\":"+std::to_string(controlPort)+",\\\"inputHost\\\":\\\"127.0.0.1\\\",\\\"inputPort\\\":"+std::to_string(inputPort)+",\\\"executable\\\":\\\""+jesc(narrow(a.exe))+"\\\"}");'
        break
s='\n'.join(lines)+'\n'
cleanup_start=s.index('  if(control.joinable()){', s.index('emitErr("{\\\"event\\\":\\\"ready'))
cleanup_end=s.index('  if(output.joinable())', cleanup_start)
s=s[:cleanup_start]+'  closesocket(controlSocket); closesocket(inputSocket); if(control.joinable())control.join(); if(input.joinable())input.join(); WSACleanup();\n'+s[cleanup_end:]
Path('tmp/cep-l1-winproof/main-fixed.cpp').write_text(s,encoding='utf-8')

m=Path('tmp/cep-l1-winproof/conpty-terminal-manager.mjs')
t=m.read_text(encoding='utf-8')
t=t.replace('outputSequence:0,controlHost:null,controlPort:null,exitCode:null','outputSequence:0,controlHost:null,controlPort:null,inputHost:null,inputPort:null,exitCode:null')
t=t.replace('session.controlHost=e.controlHost;session.controlPort=e.controlPort;session.epoch=e.providerEpoch','session.controlHost=e.controlHost;session.controlPort=e.controlPort;session.inputHost=e.inputHost;session.inputPort=e.inputPort;session.epoch=e.providerEpoch')
old="  write(id,data){const s=this._session(id);if(!s.child?.stdin?.writable)throw Error('TERMINAL_STDIN_UNAVAILABLE');const b=Buffer.isBuffer(data)?data:Buffer.from(data);s.child.stdin.write(b);return {ok:true,sessionId:id,bytes:b.length};}"
new="  async write(id,data){const s=this._session(id);if(!s.inputHost||!s.inputPort)throw Error('TERMINAL_INPUT_CHANNEL_NOT_READY');const b=Buffer.isBuffer(data)?data:Buffer.from(data);await new Promise((resolve,reject)=>{const socket=net.createConnection({host:s.inputHost,port:s.inputPort},()=>socket.end(b));socket.once('error',reject);socket.once('close',resolve)});return {ok:true,sessionId:id,bytes:b.length};}"
if old in t:t=t.replace(old,new)
m.write_text(t,encoding='utf-8')
