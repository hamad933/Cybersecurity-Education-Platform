param(
    [Parameter(Mandatory = $true)][string]$RepoPath,
    [string]$FailureMessage = 'Task-010 execution failed.'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Continue'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$WorkspaceRoot = Split-Path -Parent $RepoPath
$Stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$Stage = Join-Path ([System.IO.Path]::GetTempPath()) "TASK010_FAILURE_CONTEXT_$Stamp"
$Zip = Join-Path $WorkspaceRoot "TASK010_FAILURE_CONTEXT_$Stamp.zip"
$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
[void](New-Item -ItemType Directory -Path $Stage -Force)

try {
    Write-Task010Utf8NoBom -Path (Join-Path $Stage 'FAILURE.txt') -Content ("MESSAGE=$FailureMessage`r`nCOLLECTED_UTC=$([DateTimeOffset]::UtcNow.ToString('o'))`r`n")

    foreach ($probe in @(
        [ordered]@{ Name = 'git_status.txt'; File = 'git.exe'; Args = @('status', '--short') },
        [ordered]@{ Name = 'git_diff_stat.txt'; File = 'git.exe'; Args = @('diff', '--stat') },
        [ordered]@{ Name = 'git_diff_cached_stat.txt'; File = 'git.exe'; Args = @('diff', '--cached', '--stat') }
    )) {
        try {
            [void](Invoke-Task010Process -FilePath ([string]$probe.File) -Arguments ([string[]]$probe.Args) -WorkingDirectory $RepoPath -LogPath (Join-Path $Stage ([string]$probe.Name)) -TimeoutSeconds 120)
        } catch {}
    }

    try {
        [void](Invoke-Task010Compose -Arguments @('ps', '-a') -RepoPath $RepoPath -LogPath (Join-Path $Stage 'development_compose_ps.log') -TimeoutSeconds 120)
        [void](Invoke-Task010Compose -Arguments @('logs', '--no-color', '--tail', '300', 'app', 'postgres') -RepoPath $RepoPath -LogPath (Join-Path $Stage 'development_compose_logs.log') -TimeoutSeconds 180)
    } catch {}

    $fullSummary = Join-Path $Packet 'FULL_RELEASE_GATE_SUMMARY.json'
    if (Test-Path -LiteralPath $fullSummary -PathType Leaf) {
        try {
            $full = Get-Content -LiteralPath $fullSummary -Raw | ConvertFrom-Json
            $envPath = [string]$full.ephemeral_env_path
            if ($envPath -and (Test-Path -LiteralPath $envPath -PathType Leaf)) {
                [void](Invoke-Task010Compose -Arguments @('ps', '-a') -RepoPath $RepoPath -LogPath (Join-Path $Stage 'release_compose_ps.log') -TimeoutSeconds 120 -ComposeFiles @('compose.release.yaml') -EnvFile $envPath)
                [void](Invoke-Task010Compose -Arguments @('logs', '--no-color', '--tail', '300', 'app', 'queue', 'postgres') -RepoPath $RepoPath -LogPath (Join-Path $Stage 'release_compose_logs.log') -TimeoutSeconds 180 -ComposeFiles @('compose.release.yaml') -EnvFile $envPath)
            }
        } catch {}
    }

    if (Test-Path -LiteralPath $Packet -PathType Container) {
        $target = Join-Path $Stage 'review-packet-results'
        [void](New-Item -ItemType Directory -Path $target -Force)
        Get-ChildItem -LiteralPath $Packet -Recurse -File -ErrorAction SilentlyContinue |
            Where-Object {
                $_.Length -le 5000000 -and
                $_.FullName -notmatch '\\browser-profile(\\|$)' -and
                $_.Name -notmatch '\.(zip|png)$'
            } |
            ForEach-Object {
                $relative = $_.FullName.Substring($Packet.Length).TrimStart('\')
                $destination = Join-Path $target $relative
                [void](New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force)
                Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
            }
    }

    $manifest = New-Object System.Collections.Generic.List[string]
    $manifest.Add("relative_path`tbytes`tsha256")
    Get-ChildItem -LiteralPath $Stage -Recurse -File |
        Sort-Object FullName |
        ForEach-Object {
            $relative = $_.FullName.Substring($Stage.Length).TrimStart('\').Replace('\', '/')
            $manifest.Add("$relative`t$($_.Length)`t$(Get-Task010Sha256 -Path $_.FullName)")
        }
    Write-Task010Utf8NoBom -Path (Join-Path $Stage 'FAILURE_CONTEXT_MANIFEST.tsv') -Content (($manifest -join "`r`n") + "`r`n")

    Compress-Archive -Path (Join-Path $Stage '*') -DestinationPath $Zip -Force
    Write-Host "Task-010 failure context: $Zip"
} finally {
    Remove-Item -LiteralPath $Stage -Recurse -Force -ErrorAction SilentlyContinue
}
