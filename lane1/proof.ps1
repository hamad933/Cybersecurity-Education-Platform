param(
  [Parameter(Mandatory=$true)][string]$HelperPath,
  [Parameter(Mandatory=$true)][string]$OutDir
)
$ErrorActionPreference = 'Stop'
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$results = [System.Collections.Generic.List[object]]::new()
function Add-Case([string]$id,[string]$status,[object]$detail) {
  $results.Add([ordered]@{id=$id;status=$status;detail=$detail})
  if ($status -eq 'FAIL') { Write-Host "FAIL $id" -ForegroundColor Red } else { Write-Host "$status $id" }
}
function B64([string]$s) { [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($s)) }
function UnB64([string]$s) { if (-not $s) { return '' }; [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($s)) }
$psi = [Diagnostics.ProcessStartInfo]::new()
$psi.FileName = (Resolve-Path $HelperPath).Path
$psi.UseShellExecute = $false
$psi.RedirectStandardInput = $true
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError = $true
$psi.CreateNoWindow = $true
$p = [Diagnostics.Process]::new(); $p.StartInfo = $psi
if (-not $p.Start()) { throw 'HELPER_START_FAILED' }
$seq = 0
function Send([string]$cap,[string]$action,[hashtable]$payload=@{}) {
  $script:seq++
  $obj = [ordered]@{requestId="carrier-$($script:seq)";capability=$cap;action=$action;payload=$payload}
  $line = $obj | ConvertTo-Json -Compress -Depth 20
  $p.StandardInput.WriteLine($line); $p.StandardInput.Flush()
  $replyLine = $p.StandardOutput.ReadLine()
  if (-not $replyLine) { throw "HELPER_NO_REPLY:$cap/$action STDERR=$($p.StandardError.ReadToEnd())" }
  return ($replyLine | ConvertFrom-Json -Depth 20)
}
function Read-Term([string]$sid,[int]$after=0) { Send 'terminal' 'read' @{sessionId=$sid;after=$after} }
function Wait-Term([string]$sid,[scriptblock]$predicate,[int]$max=30) {
  $after = 0; $all = ''; $last = $null
  for($i=0;$i -lt $max;$i++) {
    Start-Sleep -Milliseconds 100
    $last = Read-Term $sid $after
    foreach($c in @($last.chunks)) { $after = [Math]::Max($after,[int]$c.sequence); $all += UnB64 ([string]$c.dataBase64) }
    if (& $predicate $last $all) { return [ordered]@{reply=$last;text=$all;after=$after} }
  }
  return [ordered]@{reply=$last;text=$all;after=$after}
}
try {
  $provider = Send 'provider' 'describe' @{}
  Add-Case 'CAR-WIN-01-provider-descriptor' $(if($provider.ok -and $provider.descriptor.platform -eq 'windows' -and $provider.descriptor.conptyAvailable){'PASS'}else{'FAIL'}) $provider.descriptor

  $platform = Send 'platform' 'describe' @{}
  Add-Case 'CAR-WIN-02-platform-descriptor-truth' $(if($platform.ok -and $null -ne $platform.descriptor.capabilities.detachedWindow -and $null -ne $platform.descriptor.capabilities.inputDirection){'PASS'}else{'FAIL'}) $platform.descriptor

  $dir = Send 'input-direction' 'read' @{}
  Add-Case 'CAR-INP-01-layout-observer-noninteractive' $(if($dir.ok -and @('rtl','ltr','unknown') -contains [string]$dir.direction){'PASS'}else{'FAIL'}) ([ordered]@{direction=$dir.direction;rawHint=$dir.rawHint;source=$dir.source})

  $cmdPath = "$env:SystemRoot\System32\cmd.exe"
  $openCmd = Send 'terminal' 'open' @{executable=$cmdPath;args=@();cwd=$env:RUNNER_TEMP;env=@{CEP_LANE1_TEST='cmd'};cols=100;rows=30}
  if (-not $openCmd.ok) { throw "CMD_OPEN_FAILED:$($openCmd.code):$($openCmd.message)" }
  $cmdSid = [string]$openCmd.session.sessionId
  $null = Send 'terminal' 'input' @{sessionId=$cmdSid;dataBase64=(B64 "echo CEP_CMD_OK`r`n")}
  $cmdRead = Wait-Term $cmdSid { param($r,$text) $text -match 'CEP_CMD_OK' }
  Add-Case 'CAR-TERM-01-cmd-raw-io' $(if($cmdRead.text -match 'CEP_CMD_OK'){'PASS'}else{'FAIL'}) ([ordered]@{sessionId=$cmdSid;text=$cmdRead.text})

  $resize = Send 'terminal' 'resize' @{sessionId=$cmdSid;cols=132;rows=43}
  Add-Case 'CAR-TERM-02-resize' $(if($resize.ok -and [int]$resize.cols -eq 132 -and [int]$resize.rows -eq 43){'PASS'}else{'FAIL'}) $resize

  $restart = Send 'terminal' 'restart' @{sessionId=$cmdSid}
  $null = Send 'terminal' 'input' @{sessionId=$cmdSid;dataBase64=(B64 "echo CEP_RESTART_OK`r`n")}
  $restartRead = Wait-Term $cmdSid { param($r,$text) $text -match 'CEP_RESTART_OK' }
  Add-Case 'CAR-TERM-03-restart-stable-session-id' $(if($restart.ok -and $restart.sessionId -eq $cmdSid -and $restartRead.text -match 'CEP_RESTART_OK'){'PASS'}else{'FAIL'}) ([ordered]@{sessionId=$cmdSid;restart=$restart;text=$restartRead.text})

  $psPath = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
  $openPs = Send 'terminal' 'open' @{executable=$psPath;args=@('-NoLogo','-NoProfile');cwd=$env:RUNNER_TEMP;env=@{CEP_LANE1_TEST='powershell'};cols=100;rows=30}
  if (-not $openPs.ok) { throw "POWERSHELL_OPEN_FAILED:$($openPs.code):$($openPs.message)" }
  $psSid = [string]$openPs.session.sessionId
  $null = Send 'terminal' 'input' @{sessionId=$psSid;dataBase64=(B64 "Write-Output CEP_PS_OK; exit`r`n")}
  $psRead = Wait-Term $psSid { param($r,$text) ($text -match 'CEP_PS_OK') -and ([string]$r.lifecycle.state -eq 'exited') }
  Add-Case 'CAR-TERM-04-powershell-profile' $(if($psRead.text -match 'CEP_PS_OK'){'PASS'}else{'FAIL'}) ([ordered]@{sessionId=$psSid;text=$psRead.text;lifecycle=$psRead.reply.lifecycle})

  $who = "$env:SystemRoot\System32\whoami.exe"
  $openWho = Send 'terminal' 'open' @{executable=$who;args=@();cwd=$env:RUNNER_TEMP;env=@{CEP_LANE1_TEST='arbitrary'};cols=100;rows=30}
  if (-not $openWho.ok) { throw "WHOAMI_OPEN_FAILED:$($openWho.code):$($openWho.message)" }
  $whoSid = [string]$openWho.session.sessionId
  $whoRead = Wait-Term $whoSid { param($r,$text) [string]$r.lifecycle.state -eq 'exited' }
  Add-Case 'CAR-TERM-05-arbitrary-extra-executable-profile' $(if($whoRead.text.Trim().Length -gt 0){'PASS'}else{'FAIL'}) ([ordered]@{sessionId=$whoSid;text=$whoRead.text;lifecycle=$whoRead.reply.lifecycle})

  $exitOpen = Send 'terminal' 'open' @{executable=$cmdPath;args=@('/d','/c','exit','7');cwd=$env:RUNNER_TEMP;env=@{};cols=80;rows=24}
  if (-not $exitOpen.ok) { throw "EXIT7_OPEN_FAILED:$($exitOpen.code):$($exitOpen.message)" }
  $exitSid = [string]$exitOpen.session.sessionId
  $exitRead = Wait-Term $exitSid { param($r,$text) [string]$r.lifecycle.state -eq 'exited' }
  Add-Case 'CAR-TERM-06-exit-code' $(if([int]$exitRead.reply.lifecycle.exitCode -eq 7){'PASS'}else{'FAIL'}) $exitRead.reply.lifecycle

  $bad = Send 'terminal' 'open' @{executable='Z:\CEP\definitely-missing\nope.exe';args=@();cwd=$env:RUNNER_TEMP;env=@{};cols=80;rows=24}
  Add-Case 'CAR-TERM-07-error-truth' $(if((-not $bad.ok) -and [string]$bad.code -eq 'CONPTY_OPEN_FAILED'){'PASS'}else{'FAIL'}) $bad

  $ssh = Get-Command ssh.exe -ErrorAction SilentlyContinue
  if ($ssh) {
    $sshOpen = Send 'terminal' 'open' @{executable=$ssh.Source;args=@('-V');cwd=$env:RUNNER_TEMP;env=@{};cols=80;rows=24}
    if ($sshOpen.ok) {
      $sshSid=[string]$sshOpen.session.sessionId
      $sshRead=Wait-Term $sshSid { param($r,$text) [string]$r.lifecycle.state -eq 'exited' }
      Add-Case 'CAR-TERM-08-system-ssh-profile' $(if($sshRead.text -match 'OpenSSH'){'PASS'}else{'FAIL'}) ([ordered]@{sessionId=$sshSid;text=$sshRead.text;lifecycle=$sshRead.reply.lifecycle})
    } else { Add-Case 'CAR-TERM-08-system-ssh-profile' 'FAIL' $sshOpen }
  } else { Add-Case 'CAR-TERM-08-system-ssh-profile' 'UNAVAILABLE_SYSTEM_SSH' @{reason='ssh.exe not installed on managed carrier'} }

  foreach($sid in @($cmdSid,$psSid,$whoSid,$exitSid,$sshSid) | Where-Object { $_ }) {
    try { $null = Send 'terminal' 'close' @{sessionId=$sid} } catch {}
  }
} catch {
  Add-Case 'CAR-HARNESS-UNCAUGHT' 'FAIL' @{error=$_.Exception.Message;stack=$_.ScriptStackTrace}
} finally {
  try { $null = Send 'provider' 'shutdown' @{} } catch {}
  try { if (-not $p.HasExited) { $p.Kill($true) } } catch {}
  try { $p.WaitForExit(5000) | Out-Null } catch {}
}
$failed=@($results | Where-Object {$_.status -eq 'FAIL'}).Count
$interactive=@()
foreach($id in @('WIN-01','WIN-02','WIN-03','WIN-04','WIN-05','WIN-06','WIN-07','WIN-08','WIN-09','WIN-10','WIN-11','WIN-12','WIN-13','INP-01','INP-02','INP-03','INP-04','INP-05','INP-06','INP-07','INP-08','INP-09')) {
  $interactive += [ordered]@{id=$id;status='NOT_RUN_PENDING_OWNER_INTERACTIVE_PROOF';reason='Managed carrier is noninteractive; no HWND/topmost/focus/layout-switch acceptance claim.'}
}
$report=[ordered]@{
  schemaVersion=1
  classification='MANAGED_WINDOWS_NONINTERACTIVE_CARRIER__CANDIDATE_ONLY__NOT_NATIVE_INTERACTIVE_ACCEPTANCE'
  runner=[ordered]@{os=[Environment]::OSVersion.VersionString;arch=[Runtime.InteropServices.RuntimeInformation]::OSArchitecture.ToString();powershell=$PSVersionTable.PSVersion.ToString();computer=$env:COMPUTERNAME;githubRunId=$env:GITHUB_RUN_ID;githubSha=$env:GITHUB_SHA}
  status=$(if($failed -eq 0){'PASS'}else{'FAIL'})
  pass=@($results|Where-Object {$_.status -eq 'PASS'}).Count
  fail=$failed
  noninteractiveCases=$results
  interactiveCases=$interactive
}
$report | ConvertTo-Json -Depth 30 | Set-Content -Encoding utf8 (Join-Path $OutDir 'managed-windows-proof.json')
if($failed -ne 0){exit 1}
