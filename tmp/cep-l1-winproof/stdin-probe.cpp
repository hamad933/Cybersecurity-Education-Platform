#define WIN32_LEAN_AND_MEAN
#include <windows.h>
#include <string>

static bool writeAll(HANDLE h,const char* p,DWORD n){while(n){DWORD w=0;if(!WriteFile(h,p,n,&w,nullptr)||w==0)return false;p+=w;n-=w;}return true;}
int main(){
  HANDLE in=GetStdHandle(STD_INPUT_HANDLE), out=GetStdHandle(STD_OUTPUT_HANDLE);
  if(in==INVALID_HANDLE_VALUE||out==INVALID_HANDLE_VALUE)return 10;
  DWORD originalMode=0;if(!GetConsoleMode(in,&originalMode))return 14;
  if(!SetConsoleMode(in,0))return 15;
  const std::string ready="CEP_PROBE_READY modeBefore="+std::to_string(originalMode)+" modeAfter=0\r\n";
  if(!writeAll(out,ready.data(),(DWORD)ready.size()))return 16;
  std::string all;char buf[1024];
  while(all.find("cepdone") == std::string::npos && all.size()<8192){DWORD n=0;if(!ReadFile(in,buf,sizeof(buf),&n,nullptr))return 11;if(n==0)continue;all.append(buf,buf+n);}
  const std::string body="CEP_PROBE_RX_BEGIN\r\n"+all+"\r\nCEP_PROBE_RX_END\r\n";
  writeAll(out,body.data(),(DWORD)body.size());SetConsoleMode(in,originalMode);
  return all.find("cepdone")!=std::string::npos?0:13;
}
