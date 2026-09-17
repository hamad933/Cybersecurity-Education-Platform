#define WIN32_LEAN_AND_MEAN
#include <windows.h>
#include <string>
#include <vector>

static bool writeAll(HANDLE h,const char* p,DWORD n){while(n){DWORD w=0;if(!WriteFile(h,p,n,&w,nullptr)||w==0)return false;p+=w;n-=w;}return true;}
static std::string narrowAscii(const std::wstring& w){std::string s;for(wchar_t c:w){if(c>=0&&c<=0x7f)s.push_back((char)c);else s.push_back('?');}return s;}
int main(){
  HANDLE in=GetStdHandle(STD_INPUT_HANDLE), out=GetStdHandle(STD_OUTPUT_HANDLE);
  const char ready[]="CEP_PROBE_READY\r\n";
  if(in==INVALID_HANDLE_VALUE||out==INVALID_HANDLE_VALUE||!writeAll(out,ready,(DWORD)sizeof(ready)-1))return 10;
  std::wstring chars; std::vector<std::string> records;
  while(chars.find(L"cepdone") == std::wstring::npos && chars.size()<8192){
    INPUT_RECORD rec{}; DWORD n=0;
    if(!ReadConsoleInputW(in,&rec,1,&n)||n!=1)return 11;
    if(rec.EventType!=KEY_EVENT)continue;
    const KEY_EVENT_RECORD& k=rec.Event.KeyEvent;
    records.push_back(std::to_string(k.wVirtualKeyCode)+":"+std::to_string(k.wVirtualScanCode)+":"+std::to_string((unsigned)k.uChar.UnicodeChar)+":"+(k.bKeyDown?"D":"U"));
    if(k.bKeyDown && k.uChar.UnicodeChar)chars.append(k.wRepeatCount?k.wRepeatCount:1,k.uChar.UnicodeChar);
  }
  const std::string text=narrowAscii(chars);
  std::string recordText;for(const auto& r:records){if(!recordText.empty())recordText.push_back(',');recordText+=r;}
  const std::string body="CEP_PROBE_RX_BEGIN\r\n"+text+"\r\nCEP_PROBE_RECORDS="+recordText+"\r\nCEP_PROBE_RX_END\r\n";
  if(!writeAll(out,body.data(),(DWORD)body.size()))return 12;
  return chars.find(L"cepdone")!=std::wstring::npos?0:13;
}
