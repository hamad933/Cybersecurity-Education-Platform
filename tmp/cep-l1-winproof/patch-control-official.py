from pathlib import Path
import subprocess

# First apply the existing bounded TCP control/input transport patch.
subprocess.check_call(['python', 'tmp/cep-l1-winproof/patch-control.py'])

# Then restore the exact Microsoft ConPTY sample lifetime for the PTY-side handles:
# close the handles passed to CreatePseudoConsole immediately after the HPCON is created.
p=Path('tmp/cep-l1-winproof/main-fixed.cpp')
s=p.read_text(encoding='utf-8')
old='''  HPCON pc=nullptr; COORD size{(SHORT)std::max<short>(1,a.cols),(SHORT)std::max<short>(1,a.rows)}; HRESULT hr=CreatePseudoConsole(size,inRead,outWrite,0,&pc); if(FAILED(hr)){CloseHandle(inRead);CloseHandle(outWrite);emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"CREATE_PSEUDOCONSOLE_FAILED\\",\\"hresult\\":"+std::to_string((long)hr)+"}");return 4;}\n'''
new='''  HPCON pc=nullptr; COORD size{(SHORT)std::max<short>(1,a.cols),(SHORT)std::max<short>(1,a.rows)}; HRESULT hr=CreatePseudoConsole(size,inRead,outWrite,0,&pc); CloseHandle(inRead);inRead=nullptr;CloseHandle(outWrite);outWrite=nullptr; if(FAILED(hr)){emitErr("{\\"event\\":\\"error\\",\\"code\\":\\"CREATE_PSEUDOCONSOLE_FAILED\\",\\"hresult\\":"+std::to_string((long)hr)+"}");return 4;}\n'''
if old not in s:
    raise SystemExit('CreatePseudoConsole post-patch target missing')
s=s.replace(old,new,1)
old2='''  BOOL ok=CreateProcessW(a.exe.c_str(),mutableCmd.data(),nullptr,nullptr,FALSE,EXTENDED_STARTUPINFO_PRESENT|CREATE_UNICODE_ENVIRONMENT,eb.data(),a.cwd.empty()?nullptr:a.cwd.c_str(),&si.StartupInfo,&pi);\n  CloseHandle(inRead);CloseHandle(outWrite);\n'''
new2='''  BOOL ok=CreateProcessW(a.exe.c_str(),mutableCmd.data(),nullptr,nullptr,FALSE,EXTENDED_STARTUPINFO_PRESENT|CREATE_UNICODE_ENVIRONMENT,eb.data(),a.cwd.empty()?nullptr:a.cwd.c_str(),&si.StartupInfo,&pi);\n'''
if old2 not in s:
    raise SystemExit('CreateProcess delayed-close target missing')
s=s.replace(old2,new2,1)
p.write_text(s,encoding='utf-8')
