param(
    [Parameter(Mandatory = $true)]
    [string] $RepoPath,

    [Parameter(Mandatory = $true)]
    [string] $BundleRoot,

    [int] $DockerWaitSeconds = 300
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
$BundleRoot = (Resolve-Path -LiteralPath $BundleRoot).Path
$Common = Join-Path $BundleRoot 'SCRIPTS\TASK010.Common.ps1'

if (-not (Test-Path -LiteralPath $Common -PathType Leaf)) {
    throw "TASK-010 common script was not found: $Common"
}

. $Common

$ExpectedHead = '83b932a079bf2237dbfa033a4322c6bded042842'
$WorkspaceRoot = Split-Path -Parent $RepoPath
$RunLogs = Join-Path $WorkspaceRoot 'TASK010_RUNNER_LOGS'
[void](New-Item -ItemType Directory -Path $RunLogs -Force)

function Invoke-ResumeProcess {
    param(
        [Parameter(Mandatory = $true)][string] $Name,
        [Parameter(Mandatory = $true)][string] $FilePath,
        [Parameter(Mandatory = $true)][string[]] $Arguments,
        [int] $TimeoutSeconds = 300
    )

    return Invoke-Task010Process `
        -FilePath $FilePath `
        -Arguments $Arguments `
        -WorkingDirectory $RepoPath `
        -LogPath (Join-Path $RunLogs ($Name + '.log')) `
        -TimeoutSeconds $TimeoutSeconds
}

function Invoke-RemainingTask010Step {
    param(
        [Parameter(Mandatory = $true)][string] $Name,
        [Parameter(Mandatory = $true)][string] $ScriptPath,
        [int] $TimeoutSeconds = 7200
    )

    if (-not (Test-Path -LiteralPath $ScriptPath -PathType Leaf)) {
        throw "Required TASK-010 script was not found: $ScriptPath"
    }

    Write-Host ''
    Write-Host "Running $Name"

    $Arguments = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', $ScriptPath,
        '-RepoPath', $RepoPath
    )

    $Run = Invoke-ResumeProcess `
        -Name ('resume_' + $Name) `
        -FilePath 'powershell.exe' `
        -Arguments $Arguments `
        -TimeoutSeconds $TimeoutSeconds

    $OutputLines = @($Run.StdOut -split "`r?`n") | Where-Object { $_ -ne '' }
    foreach ($Line in ($OutputLines | Select-Object -Last 40)) {
        Write-Host $Line
    }

    if ($Run.ExitCode -ne 0) {
        $ErrorLines = @($Run.StdErr -split "`r?`n") | Where-Object { $_ -ne '' }
        foreach ($Line in ($ErrorLines | Select-Object -Last 20)) {
            Write-Host $Line
        }

        throw "$Name stopped with exit code $($Run.ExitCode)."
    }
}

function Test-DockerEngine {
    $Probe = Invoke-ResumeProcess `
        -Name 'resume_docker_engine_probe' `
        -FilePath 'docker.exe' `
        -Arguments @('info', '--format', '{{.ServerVersion}}') `
        -TimeoutSeconds 30

    return ($Probe.ExitCode -eq 0 -and -not [string]::IsNullOrWhiteSpace($Probe.StdOut))
}

function Start-DockerDesktopIfRequired {
    if (Test-DockerEngine) {
        Write-Host 'Docker engine: READY'
        return
    }

    Write-Host 'Docker engine is not ready. Starting Docker Desktop once.'

    $Candidates = @(
        (Join-Path $env:ProgramFiles 'Docker\Docker\Docker Desktop.exe'),
        (Join-Path $env:LOCALAPPDATA 'Docker\Docker Desktop.exe')
    ) | Where-Object { $_ -and (Test-Path -LiteralPath $_ -PathType Leaf) }

    $DockerDesktop = $Candidates | Select-Object -First 1

    if (-not $DockerDesktop) {
        throw 'Docker Desktop executable was not found. Start Docker Desktop manually, wait until it reports Engine running, and rerun this script.'
    }

    $Existing = Get-Process -Name 'Docker Desktop' -ErrorAction SilentlyContinue
    if (-not $Existing) {
        Start-Process -FilePath $DockerDesktop | Out-Null
    }

    $Deadline = (Get-Date).AddSeconds($DockerWaitSeconds)
    do {
        Start-Sleep -Seconds 5
        if (Test-DockerEngine) {
            Write-Host 'Docker engine: READY'
            return
        }
        Write-Host 'Waiting for Docker Desktop engine...'
    } while ((Get-Date) -lt $Deadline)

    throw "Docker Desktop did not become ready within $DockerWaitSeconds seconds."
}

function Collect-FailureContext {
    param([string] $Message)

    try {
        $Collector = Join-Path $BundleRoot 'SCRIPTS\COLLECT_TASK010_FAILURE_CONTEXT_PS51.ps1'
        if (-not (Test-Path -LiteralPath $Collector -PathType Leaf)) {
            return
        }

        $Arguments = @(
            '-NoProfile',
            '-ExecutionPolicy', 'Bypass',
            '-File', $Collector,
            '-RepoPath', $RepoPath,
            '-FailureMessage', $Message
        )

        [void](Invoke-ResumeProcess `
            -Name 'resume_failure_context' `
            -FilePath 'powershell.exe' `
            -Arguments $Arguments `
            -TimeoutSeconds 900)
    }
    catch {
        Write-Host 'Failure-context collection was attempted but did not complete.'
    }
}

try {
    $HeadRun = Invoke-ResumeProcess `
        -Name 'resume_git_head' `
        -FilePath 'git.exe' `
        -Arguments @('rev-parse', 'HEAD') `
        -TimeoutSeconds 60

    if ($HeadRun.ExitCode -ne 0) {
        throw 'Unable to read the repository HEAD.'
    }

    $CurrentHead = $HeadRun.StdOut.Trim()
    if ($CurrentHead -ne $ExpectedHead) {
        throw "Unexpected repository HEAD. Expected $ExpectedHead but found $CurrentHead."
    }

    $RequiredAppliedFiles = @(
        'app\Http\Controllers\ReleaseController.php',
        'database\migrations\2026_07_25_000012_create_v1_integration_release_tables.php',
        'scripts\package_task010_handoff.php',
        'review-packets\v1-release-010\README.md'
    )

    foreach ($RelativePath in $RequiredAppliedFiles) {
        $AbsolutePath = Join-Path $RepoPath $RelativePath
        if (-not (Test-Path -LiteralPath $AbsolutePath -PathType Leaf)) {
            throw "TASK-010 source patch is incomplete; missing file: $RelativePath"
        }
    }

    $ApplyResult = Join-Path $WorkspaceRoot 'TASK010_APPLY_RESULT.json'
    if (-not (Test-Path -LiteralPath $ApplyResult -PathType Leaf)) {
        throw "TASK-010 apply result was not found: $ApplyResult"
    }

    Write-Host 'TASK-010 source application state: VERIFIED'
    Write-Host 'The source patch will not be reapplied.'

    Start-DockerDesktopIfRequired

    Invoke-RemainingTask010Step `
        -Name 'targeted_verification' `
        -ScriptPath (Join-Path $BundleRoot 'SCRIPTS\VERIFY_TASK010_TARGETED_PS51.ps1') `
        -TimeoutSeconds 10800

    Invoke-RemainingTask010Step `
        -Name 'restore_drill' `
        -ScriptPath (Join-Path $BundleRoot 'SCRIPTS\RUN_TASK010_RESTORE_DRILL_PS51.ps1') `
        -TimeoutSeconds 7200

    Invoke-RemainingTask010Step `
        -Name 'full_release_gate' `
        -ScriptPath (Join-Path $BundleRoot 'SCRIPTS\RUN_TASK010_FULL_RELEASE_GATE_PS51.ps1') `
        -TimeoutSeconds 18000

    Invoke-RemainingTask010Step `
        -Name 'browser_gate' `
        -ScriptPath (Join-Path $BundleRoot 'SCRIPTS\RUN_TASK010_BROWSER_GATE_PS51.ps1') `
        -TimeoutSeconds 1800

    Invoke-RemainingTask010Step `
        -Name 'build_handoff' `
        -ScriptPath (Join-Path $BundleRoot 'SCRIPTS\BUILD_TASK010_HANDOFF_PS51.ps1') `
        -TimeoutSeconds 3600

    Write-Host ''
    Write-Host 'TASK-010 resume sequence: COMPLETE'
    Write-Host 'STOP-V1-RELEASE-010'
}
catch {
    $Message = $_.Exception.Message
    Write-Host ''
    Write-Host "TASK-010 resume stopped: $Message"
    Collect-FailureContext -Message $Message
    throw
}
