from pathlib import Path
import subprocess

subprocess.check_call(['python', 'tmp/cep-l1-winproof/patch-control.py'])
p=Path('tmp/cep-l1-winproof/main-fixed.cpp')
s=p.read_text(encoding='utf-8')
old_pipe='''  HANDLE inRead=nullptr,inWrite=nullptr,outRead=nullptr,outWrite=nullptr; SECURITY_ATTRIBUTES sa{sizeof(sa),nullptr,TRUE};\n  if(!CreatePipe(&inRead,&inWrite,&sa,0)||!CreatePipe(&outRead,&outWrite,&sa,0)){emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"PIPE_CREATE_FAILED\\"}");return 3;}\n  SetHandleInformation(inWrite,HANDLE_FLAG_INHERIT,0);SetHandleInformation(outRead,HANDLE_FLAG_INHERIT,0);\n'''
new_pipe='''  HANDLE inRead=nullptr,inWrite=nullptr,outRead=nullptr,outWrite=nullptr;\n  if(!CreatePipe(&inRead,&inWrite,nullptr,0)||!CreatePipe(&outRead,&outWrite,nullptr,0)){emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"PIPE_CREATE_FAILED\\"}");return 3;}\n'''
if old_pipe not in s: raise SystemExit('CreatePipe target missing')
s=s.replace(old_pipe,new_pipe,1)
old='''  HPCON pc=nullptr; COORD size{(SHORT)std::max<short>(1,a.cols),(SHORT)std::max<short>(1,a.rows)}; HRESULT hr=CreatePseudoConsole(size,inRead,outWrite,0,&pc); if(FAILED(hr)){CloseHandle(inRead);CloseHandle(outWrite);emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"CREATE_PSEUDOCONSOLE_FAILED\\",\\"hresult\\":"+std::to_string((long)hr)+"}");return 4;}\n'''
new='''  HPCON pc=nullptr; COORD size{(SHORT)std::max<short>(1,a.cols),(SHORT)std::max<short>(1,a.rows)}; HRESULT hr=CreatePseudoConsole(size,inRead,outWrite,0,&pc); if(FAILED(hr)){CloseHandle(inRead);CloseHandle(outWrite);emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"CREATE_PSEUDOCONSOLE_FAILED\\",\\"hresult\\":"+std::to_string((long)hr)+"}");return 4;}\n  CloseHandle(inRead); inRead=nullptr; CloseHandle(outWrite); outWrite=nullptr;\n'''
if old not in s: raise SystemExit('CreatePseudoConsole target missing')
s=s.replace(old,new,1)
old2='''  BOOL ok=CreateProcessW(a.exe.c_str(),mutableCmd.data(),nullptr,nullptr,FALSE,EXTENDED_STARTUPINFO_PRESENT|CREATE_UNICODE_ENVIRONMENT,eb.data(),a.cwd.empty()?nullptr:a.cwd.c_str(),&si.StartupInfo,&pi);\n  CloseHandle(inRead);CloseHandle(outWrite);\n'''
new2='''  BOOL ok=CreateProcessW(a.exe.c_str(),mutableCmd.data(),nullptr,nullptr,FALSE,EXTENDED_STARTUPINFO_PRESENT|CREATE_UNICODE_ENVIRONMENT,eb.data(),a.cwd.empty()?nullptr:a.cwd.c_str(),&si.StartupInfo,&pi);\n'''
if old2 not in s: raise SystemExit('CreateProcess delayed close target missing')
s=s.replace(old2,new2,1)
old3='''  WaitForSingleObject(pi.hProcess,INFINITE);DWORD code=0;GetExitCodeProcess(pi.hProcess,&code);closing=true;CloseHandle(inWrite);CloseHandle(outRead);ClosePseudoConsole(pc);CloseHandle(pi.hProcess);\n  closesocket(controlSocket); closesocket(inputSocket); if(control.joinable())control.join(); if(input.joinable())input.join(); WSACleanup();\n  if(output.joinable())output.join();\n'''
new3='''  WaitForSingleObject(pi.hProcess,INFINITE);DWORD code=0;GetExitCodeProcess(pi.hProcess,&code);closing=true;CloseHandle(inWrite);ClosePseudoConsole(pc);CloseHandle(pi.hProcess);\n  closesocket(controlSocket); closesocket(inputSocket); if(control.joinable())control.join(); if(input.joinable())input.join(); WSACleanup();\n  if(output.joinable())output.join(); CloseHandle(outRead);\n'''
if old3 not in s: raise SystemExit('drain target missing')
s=s.replace(old3,new3,1)
p.write_text(s,encoding='utf-8')
