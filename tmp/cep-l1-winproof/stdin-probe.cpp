#define WIN32_LEAN_AND_MEAN
#include <windows.h>
#include <string>

static bool writeAll(HANDLE h,const char* p,DWORD n){while(n){DWORD w=0;if(!WriteFile(h,p,n,&w,nullptr)||w==0)return false;p+=w;n-=w;}return true;}
int main(){
  HANDLE in=GetStdHandle(STD_INPUT_HANDLE), out=GetStdHandle(STD_OUTPUT_HANDLE);
  const char ready[]="CEP_PROBE_READY\r\n";
  if(in==INVALID_HANDLE_VALUE||out==INVALID_HANDLE_VALUE||!writeAll(out,ready,(DWORD)sizeof(ready)-1))return 10;
  std::string all; char buf[1024];
  while(all.find("cepdone") == std::string::npos && all.size()<8192){DWORD n=0;if(!ReadFile(in,buf,sizeof(buf),&n,nullptr)||n==0)return 11;all.append(buf,buf+n);}
  const char prefix[]="CEP_PROBE_RX_BEGIN\r\n"; const char suffix[]="\r\nCEP_PROBE_RX_END\r\n";
  if(!writeAll(out,prefix,(DWORD)sizeof(prefix)-1)||!writeAll(out,all.data(),(DWORD)all.size())||!writeAll(out,suffix,(DWORD)sizeof(suffix)-1))return 12;
  return all.find("cepdone")!=std::string::npos?0:13;
}
