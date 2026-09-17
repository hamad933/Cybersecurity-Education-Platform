#define UNICODE
#define _UNICODE
#define WIN32_LEAN_AND_MEAN
#include <windows.h>
#include <string>
#include <thread>
#include <atomic>
#include <iostream>
#include <vector>

int wmain(){
  HANDLE inR=nullptr,inW=nullptr,outR=nullptr,outW=nullptr;
  if(!CreatePipe(&inR,&inW,nullptr,0)||!CreatePipe(&outR,&outW,nullptr,0)) return 2;
  HPCON pc=nullptr; COORD size{80,24}; HRESULT hr=CreatePseudoConsole(size,inR,outW,0,&pc);
  CloseHandle(inR); CloseHandle(outW); if(FAILED(hr)) return 3;
  SIZE_T bytes=0; InitializeProcThreadAttributeList(nullptr,1,0,&bytes);
  auto attrs=(PPROC_THREAD_ATTRIBUTE_LIST)HeapAlloc(GetProcessHeap(),0,bytes);
  if(!attrs||!InitializeProcThreadAttributeList(attrs,1,0,&bytes)||!UpdateProcThreadAttribute(attrs,0,PROC_THREAD_ATTRIBUTE_PSEUDOCONSOLE,pc,sizeof(pc),nullptr,nullptr)) return 4;
  STARTUPINFOEXW si{};si.StartupInfo.cb=sizeof(si);si.lpAttributeList=attrs;PROCESS_INFORMATION pi{};
  std::wstring cmd=L"cmd.exe /D /Q"; std::vector<wchar_t> m(cmd.begin(),cmd.end());m.push_back(0);
  if(!CreateProcessW(nullptr,m.data(),nullptr,nullptr,FALSE,EXTENDED_STARTUPINFO_PRESENT,nullptr,nullptr,&si.StartupInfo,&pi)) return 5;
  CloseHandle(pi.hThread);DeleteProcThreadAttributeList(attrs);HeapFree(GetProcessHeap(),0,attrs);
  std::string output; std::thread reader([&](){char b[4096];DWORD n=0;while(ReadFile(outR,b,sizeof(b),&n,nullptr)&&n)output.append(b,b+n);});
  Sleep(750);
  const char* payload="echo CEP_DIRECT_OK\r\nexit /b 0\r\n";DWORD wrote=0;BOOL ok=WriteFile(inW,payload,(DWORD)strlen(payload),&wrote,nullptr);std::cerr<<"WRITE ok="<<ok<<" wrote="<<wrote<<"\n";
  DWORD wait=WaitForSingleObject(pi.hProcess,5000); if(wait!=WAIT_OBJECT_0){TerminateProcess(pi.hProcess,124);WaitForSingleObject(pi.hProcess,2000);} DWORD code=999;GetExitCodeProcess(pi.hProcess,&code);
  ClosePseudoConsole(pc);CloseHandle(inW);CloseHandle(outR);CloseHandle(pi.hProcess);if(reader.joinable())reader.join();
  std::cout<<output<<"\nDIRECT_EXIT="<<code<<"\n";
  return ok&&wrote==strlen(payload)&&output.find("CEP_DIRECT_OK")!=std::string::npos?0:20;
}
