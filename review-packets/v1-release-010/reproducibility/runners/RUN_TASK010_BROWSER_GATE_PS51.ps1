param([Parameter(Mandatory = $true)][string]$RepoPath)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
$Output = Join-Path $Packet 'browser-evidence'
$Logs = Join-Path $Packet 'local-verification\browser'
$ResultPath = Join-Path $Output 'BROWSER_GATE_RESULT.json'
[void](New-Item -ItemType Directory -Path $Output -Force)
[void](New-Item -ItemType Directory -Path $Logs -Force)

if (Test-Path -LiteralPath $ResultPath -PathType Leaf) {
    $existing = Get-Content -LiteralPath $ResultPath -Raw | ConvertFrom-Json
    if ($existing.attempted -eq $true) {
        Write-Host "Task-010 browser gate was already attempted once. Preserving status: $($existing.status)"
        exit 0
    }
}

$FullSummaryPath = Join-Path $Packet 'FULL_RELEASE_GATE_SUMMARY.json'
if (-not (Test-Path -LiteralPath $FullSummaryPath -PathType Leaf)) {
    throw 'Full release gate summary is missing. Browser capture cannot start.'
}
$fullSummary = Get-Content -LiteralPath $FullSummaryPath -Raw | ConvertFrom-Json
if ([string]$fullSummary.status -ne 'PASS') {
    throw 'Full release gate did not pass. Browser capture was not attempted.'
}
$EnvPath = [string]$fullSummary.ephemeral_env_path
if (-not (Test-Path -LiteralPath $EnvPath -PathType Leaf)) {
    throw "Ephemeral release environment is missing: $EnvPath"
}

function Find-Task010Executable {
    param([string[]]$CommandNames, [string[]]$CandidatePaths)
    foreach ($name in $CommandNames) {
        $command = Get-Command $name -ErrorAction SilentlyContinue
        if ($null -ne $command -and $command.Source) { return [string]$command.Source }
    }
    foreach ($candidate in $CandidatePaths) {
        if ($candidate -and (Test-Path -LiteralPath $candidate -PathType Leaf)) { return $candidate }
    }
    return $null
}

$nodeCandidates = @(
    (Join-Path $env:ProgramFiles 'nodejs\node.exe'),
    $(if (${env:ProgramFiles(x86)}) { Join-Path ${env:ProgramFiles(x86)} 'nodejs\node.exe' } else { $null }),
    (Join-Path $env:LOCALAPPDATA 'Programs\nodejs\node.exe'),
    (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Links\node.exe')
)
$NodePath = Find-Task010Executable -CommandNames @('node.exe', 'node') -CandidatePaths $nodeCandidates

$browserCandidates = @(
    (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
    (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe'),
    (Join-Path $env:LOCALAPPDATA 'Microsoft\Edge\Application\msedge.exe'),
    (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
    (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
    (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
)
$BrowserPath = Find-Task010Executable -CommandNames @('msedge.exe', 'chrome.exe') -CandidatePaths $browserCandidates

if (-not $NodePath -or -not $BrowserPath) {
    $blocked = [ordered]@{
        status = 'BLOCKED_BROWSER_UNAVAILABLE'
        attempted = $true
        node_found = [bool]$NodePath
        browser_found = [bool]$BrowserPath
        generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
    }
    Write-Task010Utf8NoBom -Path $ResultPath -Content ((ConvertTo-Task010Json $blocked) + "`r`n")
    Write-Host '[BLOCKED_BROWSER_UNAVAILABLE] Task-010 browser gate'
    exit 0
}

$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$secretBytes = New-Object byte[] 30
$rng.GetBytes($secretBytes)
$rng.Dispose()
$BrowserPassword = ([Convert]::ToBase64String($secretBytes) -replace '[^A-Za-z0-9]', '') + 'Aa1!'
$releaseFiles = @('compose.release.yaml')
$ownerRun = Invoke-Task010Compose `
    -Arguments @('exec', '-T', '-e', 'TASK010_BROWSER_PASSWORD', 'app', 'php', 'artisan', 'platform:release-gate-owner') `
    -RepoPath $RepoPath `
    -LogPath (Join-Path $Logs 'release_gate_owner.log') `
    -TimeoutSeconds 180 `
    -ComposeFiles $releaseFiles `
    -EnvFile $EnvPath `
    -Environment @{ TASK010_BROWSER_PASSWORD = $BrowserPassword }
if ($ownerRun.ExitCode -ne 0) {
    throw 'The release-gate owner could not be prepared.'
}

$ProfilePath = Join-Path ([System.IO.Path]::GetTempPath()) ('task010-browser-' + [Guid]::NewGuid().ToString('N'))
[void](New-Item -ItemType Directory -Path $ProfilePath -Force)
$browserArguments = @(
    '--headless=new',
    '--remote-debugging-port=19222',
    '--remote-debugging-address=127.0.0.1',
    '--remote-allow-origins=*',
    "--user-data-dir=$ProfilePath",
    '--disable-gpu',
    '--disable-extensions',
    '--no-first-run',
    '--no-default-browser-check',
    'about:blank'
)
$browserStart = New-Object System.Diagnostics.ProcessStartInfo
$browserStart.FileName = $BrowserPath
$browserStart.Arguments = (($browserArguments | ForEach-Object { ConvertTo-Task010QuotedArgument ([string]$_) }) -join ' ')
$browserStart.UseShellExecute = $false
$browserStart.CreateNoWindow = $true
$browserStart.RedirectStandardOutput = $true
$browserStart.RedirectStandardError = $true
$browserProcess = New-Object System.Diagnostics.Process
$browserProcess.StartInfo = $browserStart
$browserStarted = $false
try {
    if (-not $browserProcess.Start()) { throw 'The local browser process could not start.' }
    $browserStarted = $true
    $ready = $false
    for ($attempt = 1; $attempt -le 40; $attempt++) {
        try {
            $request = [System.Net.HttpWebRequest]::Create('http://127.0.0.1:19222/json/version')
            $request.Timeout = 1000
            $response = $request.GetResponse()
            $response.Close()
            $ready = $true
            break
        } catch {
            Start-Sleep -Milliseconds 250
        }
    }
    if (-not $ready) { throw 'Chrome DevTools endpoint did not become ready.' }

    $captureRun = Invoke-Task010Process `
        -FilePath $NodePath `
        -Arguments @('scripts/capture_task010_browser_evidence.mjs') `
        -WorkingDirectory $RepoPath `
        -LogPath (Join-Path $Logs 'browser_capture.log') `
        -TimeoutSeconds 240 `
        -Environment @{
            TASK010_CDP_ENDPOINT = 'http://127.0.0.1:19222'
            TASK010_RELEASE_URL = 'http://127.0.0.1:18081'
            TASK010_BROWSER_EMAIL = 'task010-browser@example.test'
            TASK010_BROWSER_PASSWORD = $BrowserPassword
            TASK010_BROWSER_OUTPUT = $Output
        }
    if ($captureRun.ExitCode -ne 0 -and -not (Test-Path -LiteralPath $ResultPath -PathType Leaf)) {
        $blocked = [ordered]@{
            status = 'BLOCKED_BROWSER_ATTEMPT_FAILED'
            attempted = $true
            reason = 'Browser evidence capture returned a non-zero exit code. See browser_capture.log.'
            generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
        }
        Write-Task010Utf8NoBom -Path $ResultPath -Content ((ConvertTo-Task010Json $blocked) + "`r`n")
    }
} catch {
    if (-not (Test-Path -LiteralPath $ResultPath -PathType Leaf)) {
        $blocked = [ordered]@{
            status = 'BLOCKED_BROWSER_ATTEMPT_FAILED'
            attempted = $true
            reason = $_.Exception.Message
            generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
        }
        Write-Task010Utf8NoBom -Path $ResultPath -Content ((ConvertTo-Task010Json $blocked) + "`r`n")
    }
} finally {
    $BrowserPassword = $null
    if ($browserStarted -and -not $browserProcess.HasExited) {
        try { $browserProcess.Kill() } catch {}
    }
    try { $browserProcess.Dispose() } catch {}
    Remove-Item -LiteralPath $ProfilePath -Recurse -Force -ErrorAction SilentlyContinue
}

$result = Get-Content -LiteralPath $ResultPath -Raw | ConvertFrom-Json
Write-Host "[$($result.status)] Task-010 browser gate"
if ([string]$result.status -eq 'PASS') {
    Write-Host "Screenshots: $($result.screenshot_count)"
}
exit 0
