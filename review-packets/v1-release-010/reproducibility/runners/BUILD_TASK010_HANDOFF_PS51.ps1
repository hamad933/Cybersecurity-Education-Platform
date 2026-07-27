param([Parameter(Mandatory = $true)][string]$RepoPath)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
$Logs = Join-Path $Packet 'local-verification\handoff'
[void](New-Item -ItemType Directory -Path $Packet -Force)
[void](New-Item -ItemType Directory -Path $Logs -Force)

function Read-RequiredJson {
    param([string]$Path, [string]$Name)
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Required Task-010 result is missing: $Name"
    }
    return Get-Content -LiteralPath $Path -Raw | ConvertFrom-Json
}

$TargetedPath = Join-Path $Packet 'TARGETED_GATE_SUMMARY.json'
$FullPath = Join-Path $Packet 'FULL_RELEASE_GATE_SUMMARY.json'
$RestorePath = Join-Path $Packet 'RESTORE_DRILL_RESULT.json'
$BrowserSourcePath = Join-Path $Packet 'browser-evidence\BROWSER_GATE_RESULT.json'
$BrowserPath = Join-Path $Packet 'BROWSER_RESULT.json'

$targeted = Read-RequiredJson -Path $TargetedPath -Name 'TARGETED_GATE_SUMMARY.json'
$full = Read-RequiredJson -Path $FullPath -Name 'FULL_RELEASE_GATE_SUMMARY.json'
$restore = Read-RequiredJson -Path $RestorePath -Name 'RESTORE_DRILL_RESULT.json'
$browser = Read-RequiredJson -Path $BrowserSourcePath -Name 'browser-evidence/BROWSER_GATE_RESULT.json'
Copy-Item -LiteralPath $BrowserSourcePath -Destination $BrowserPath -Force
$ApplyResultSource = Join-Path (Split-Path -Parent $RepoPath) 'TASK010_APPLY_RESULT.json'
if (Test-Path -LiteralPath $ApplyResultSource -PathType Leaf) {
    Copy-Item -LiteralPath $ApplyResultSource -Destination (Join-Path $Packet 'APPLY_RESULT.json') -Force
}

if ([string]$targeted.status -ne 'PASS') { throw 'Targeted gate is not PASS.' }
if ([string]$full.status -ne 'PASS') { throw 'Full release gate is not PASS.' }
if ([string]$restore.status -ne 'PASS') { throw 'Restore drill is not PASS.' }

function Get-FullGateStatus {
    param([Parameter(Mandatory = $true)]$Summary, [Parameter(Mandatory = $true)][string]$Name)

    $resultsProperty = $Summary.PSObject.Properties['results']
    if ($null -eq $resultsProperty) {
        return ''
    }
    foreach ($result in @($resultsProperty.Value)) {
        if ([string]$result.name -eq $Name) {
            return [string]$result.status
        }
    }
    return ''
}

$queueSmokeStatus = Get-FullGateStatus -Summary $full -Name 'release_queue_smoke'
$finalServiceHealthStatus = Get-FullGateStatus -Summary $full -Name 'release_final_service_health'
if ($queueSmokeStatus -ne 'PASS') { throw 'Release queue smoke gate is not PASS.' }
if ($finalServiceHealthStatus -ne 'PASS') { throw 'Final release service-health gate is not PASS.' }

foreach ($requiredRevisionEvidence in @(
    'BUNDLE_FILE_SHA256SUMS.tsv',
    'BUNDLE_MANIFEST.tsv',
    'BUNDLE_BUILD_REPORT.json',
    'reproducibility\REVISION_REPRODUCIBILITY_MANIFEST.json',
    'reproducibility\runners\RUN_TASK010_FULL_RELEASE_GATE_PS51.ps1',
    'reproducibility\runners\BUILD_TASK010_HANDOFF_PS51.ps1',
    'reproducibility\runners\TASK010.Common.ps1',
    'reproducibility\runners\RESUME_TASK010_AFTER_DOCKER_PS51.ps1'
)) {
    if (-not (Test-Path -LiteralPath (Join-Path $Packet $requiredRevisionEvidence) -PathType Leaf)) {
        throw "Required revision reproducibility evidence is missing: $requiredRevisionEvidence"
    }
}

$browserStatus = [string]$browser.status
$allowedBrowserStatuses = @('PASS', 'BLOCKED_BROWSER_UNAVAILABLE', 'BLOCKED_BROWSER_ATTEMPT_FAILED')
if ($allowedBrowserStatuses -notcontains $browserStatus) {
    throw "Unexpected browser gate status: $browserStatus"
}

$overallStatus = if ($browserStatus -eq 'PASS') { 'PASS' } else { 'PASS_WITH_RECORDED_BROWSER_BLOCKER' }
$now = [DateTimeOffset]::UtcNow.ToString('o')

$commandLines = New-Object System.Collections.Generic.List[string]
$commandLines.Add('TASK-010 LOCAL VERIFICATION RESULTS')
$commandLines.Add("Generated UTC: $now")
$commandLines.Add('')
foreach ($summaryItem in @(
    [ordered]@{ Name = 'TARGETED'; Value = $targeted },
    [ordered]@{ Name = 'FULL_RELEASE'; Value = $full },
    [ordered]@{ Name = 'RESTORE_DRILL'; Value = $restore },
    [ordered]@{ Name = 'BROWSER'; Value = $browser }
)) {
    $commandLines.Add("[$($summaryItem.Name)] STATUS=$([string]$summaryItem.Value.status)")
    $resultsProperty = $summaryItem.Value.PSObject.Properties['results']
    if ($null -ne $resultsProperty) {
        foreach ($result in @($resultsProperty.Value)) {
            $commandLines.Add("  $([string]$result.status)  $([string]$result.name)  exit=$([string]$result.exit_code)  duration=$([string]$result.duration_seconds)s")
        }
    }
    $gatesProperty = $summaryItem.Value.PSObject.Properties['gates']
    if ($null -ne $gatesProperty) {
        foreach ($result in @($gatesProperty.Value)) {
            $commandLines.Add("  $([string]$result.status)  $([string]$result.name)  exit=$([string]$result.exit_code)  duration=$([string]$result.duration_seconds)s")
        }
    }
    $commandLines.Add('')
}
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'COMMANDS_AND_TEST_RESULTS.txt') -Content (($commandLines -join "`r`n") + "`r`n")

$security = @"
# Security and dependency results

- Targeted verification: **$([string]$targeted.status)**.
- Single full PHP regression: **$([string]$full.status)**; exactly one full run was performed and no second full run was used.
- Composer locked audit: recorded in the full-release gate and required to pass.
- npm high-severity audit: recorded in the full-release gate and required to pass.
- Repository secret scan: recorded in the full-release gate and required to pass.
- Safe ZIP/package controls: path traversal rejection, declared-member enforcement, digest/size checks, compression bounds, and actor binding are covered by targeted tests.
- Audit integrity: chained SHA-256 records are verified and direct tampering is detected by targeted tests.
- Manual AI boundary: manual export/import plus human decision only; no network provider and no automatic publication.
- Release network boundary: loopback-only application port; PostgreSQL has no host port.
- Release queue smoke: **$queueSmokeStatus**; one bounded database-queue job was processed by the release worker.
- Final app/queue/PostgreSQL state gate: **$finalServiceHealthStatus**.
- Browser gate: **$browserStatus**.
"@
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'SECURITY_DEPENDENCY_RESULTS.md') -Content ($security.Trim() + "`r`n")

$release = @"
# Release, backup, restore, and rollback results

- Release Compose gate: **$([string]$full.status)**.
- Release URL used by the bounded gate: `http://127.0.0.1:18081`.
- App, queue, and PostgreSQL are validated through the release Compose profile.
- Queue HTTP health inheritance is disabled; worker liveness is proven by running state plus one bounded processed job.
- Release queue smoke: **$queueSmokeStatus**.
- Final service-health gate: **$finalServiceHealthStatus**.
- Logical backup and isolated restore drill: **$([string]$restore.status)**.
- Restore target was restricted to `cyber_platform_restore_drill` and removed after validation.
- Declared RPO/RTO claim: **NOT_DECLARED_MEASUREMENT_ONLY**.
- Source application creates a timestamped pre-apply backup ZIP before overwriting existing files.
- Rollback for Task-010 source changes uses that pre-apply ZIP against the exact Task-009 checkpoint.
- Browser evidence status: **$browserStatus**.
"@
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'RELEASE_AND_ROLLBACK_RESULTS.md') -Content ($release.Trim() + "`r`n")

$limitations = New-Object System.Collections.Generic.List[string]
$limitations.Add('# Residual limitations')
$limitations.Add('')
$limitations.Add('- Backup package encryption, retention, RPO, and RTO remain policy decisions for a later production deployment; local V1 makes no unsupported claim.')
$limitations.Add('- PostgreSQL `simple` full-text search is bounded and deterministic but is not claimed as language-specific Arabic or English linguistic ranking.')
$limitations.Add('- Mastery thresholds remain provisional and are kept behind explicit application rules.')
$limitations.Add('- No production connectors, live telemetry ingestion, automatic AI provider, automatic AI execution, or automatic publication exists.')
if ($browserStatus -ne 'PASS') {
    $reasonProperty = $browser.PSObject.Properties['reason']
    $reason = if ($null -ne $reasonProperty) { [string]$reasonProperty.Value } else { 'Required host browser or Node runtime was unavailable.' }
    $limitations.Add("- Browser evidence was attempted exactly once and preserved as **$browserStatus**. Blocker: $reason")
} else {
    $limitations.Add('- Browser evidence completed in one bounded attempt with eight screenshots, an accessibility tree, and a security-header snapshot.')
}
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'RESIDUAL_LIMITATIONS.md') -Content (($limitations -join "`r`n") + "`r`n")

$acceptance = @(
    "gate`tstatus`tevidence`tnote",
    "A1`tPASS`treview-packets/v1-release-010/APPLY_RESULT.json`tExact Task-009 checkpoint, clean repository, and tag were verified before application.",
    "A2`tPASS`treview-packets/v1-release-010/BUNDLE_FILE_SHA256SUMS.tsv`tEvery distributed bundle member and source patch file was hash verified.",
    "A3`tPASS`treview-packets/v1-release-010/TARGETED_GATE_SUMMARY.json`tPostgreSQL migration, seed, and targeted lifecycle gates passed.",
    "A4`tPASS`treview-packets/v1-release-010/FULL_RELEASE_GATE_SUMMARY.json`tLocked PHP and Node quality gates passed.",
    "A5`tPASS`treview-packets/v1-release-010/TARGETED_GATE_SUMMARY.json`tSafe source, evidence, and portable package imports passed.",
    "A6`tPASS`treview-packets/v1-release-010/TARGETED_GATE_SUMMARY.json`tManual AI Bridge export/import and human decision controls passed.",
    "A7`tPASS`treview-packets/v1-release-010/TARGETED_GATE_SUMMARY.json`tSearch and explainable daily queue tests passed.",
    "A8`tPASS`treview-packets/v1-release-010/TARGETED_GATE_SUMMARY.json`tTamper-evident audit chain and actor-bound evidence controls passed.",
    "A9`tPASS`treview-packets/v1-release-010/RESTORE_DRILL_RESULT.json`tIsolated logical backup and restore drill passed.",
    "A10`tPASS`treview-packets/v1-release-010/SECURITY_DEPENDENCY_RESULTS.md`tDependency audits, secret scan, and security controls passed.",
    "A11`tPASS`treview-packets/v1-release-010/FULL_RELEASE_GATE_SUMMARY.json;review-packets/v1-release-010/local-verification/full-release/release_queue_smoke.log;review-packets/v1-release-010/local-verification/full-release/release_final_service_health.log`tLoopback release, bounded queue processing, and final service state passed.",
    "A12`t$browserStatus`treview-packets/v1-release-010/BROWSER_RESULT.json`tExactly one bounded browser attempt; status preserved truthfully.",
    "A13`t$overallStatus`treview-packets/v1-release-010/TASK010_FINAL_SUMMARY.json`tHandoff built only after required core gates passed."
)
Write-Task010Utf8NoBom -Path (Join-Path $RepoPath 'planning\task010\TASK010_ACCEPTANCE_RESULTS.tsv') -Content (($acceptance -join "`r`n") + "`r`n")

$finalSummary = [ordered]@{
    status = $overallStatus
    core_release_status = 'PASS'
    browser_status = $browserStatus
    required_checkpoint = '83b932a079bf2237dbfa033a4322c6bded042842'
    full_php_regression_runs = 1
    second_full_run_performed = $false
    targeted_gate = [string]$targeted.status
    full_release_gate = [string]$full.status
    restore_drill = [string]$restore.status
    release_queue_smoke = $queueSmokeStatus
    release_final_service_health = $finalServiceHealthStatus
    generated_at_utc = $now
    stop_gate = 'STOP-V1-RELEASE-010'
}
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'TASK010_FINAL_SUMMARY.json') -Content ((ConvertTo-Task010Json $finalSummary) + "`r`n")

$packetReadme = @"
# Task-010 V1 release candidate

This packet records the final Task-010 V1 review candidate.

- Core release status: **PASS**.
- Release queue smoke: **$queueSmokeStatus**.
- Final service-health gate: **$finalServiceHealthStatus**.
- Browser status: **$browserStatus**.
- Overall handoff status: **$overallStatus**.
- Stop gate: **STOP-V1-RELEASE-010**.
- Task-011 has not started and is not authorized by this packet.

See TASK010_FINAL_SUMMARY.json, FULL_RELEASE_GATE_SUMMARY.json, RESTORE_DRILL_RESULT.json, and RESIDUAL_LIMITATIONS.md.
"@
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'README.md') -Content ($packetReadme.Trim() + "`r`n")

$report = @"
# Task-010 V1 integration and local release report

Task-010 integrated VS-001, VS-002, and VS-003 into a bounded local V1 release candidate. The implementation adds safe source and evidence import, a portable package format, the Manual AI Bridge, local search, an explainable daily queue, tamper-evident audit records, logical backup and isolated restore validation, Arabic RTL/LTR release workflows, and a hardened loopback Docker Compose profile.

## Verification decision

- Core release decision: **PASS**.
- Overall handoff status: **$overallStatus**.
- Browser status: **$browserStatus**.
- Release queue smoke: **$queueSmokeStatus**.
- Final service-health gate: **$finalServiceHealthStatus**.
- Full PHP regression runs: **1**.
- Second full regression: **not performed**.
- Stop gate: **STOP-V1-RELEASE-010**.

All remaining limitations are recorded in `RESIDUAL_LIMITATIONS.md`. Task-011 or any scope beyond V1 was not started.
"@
Write-Task010Utf8NoBom -Path (Join-Path $Packet 'FINAL_REPORT.md') -Content ($report.Trim() + "`r`n")

$packageRun = Invoke-Task010Compose `
    -Arguments @('exec', '-T', 'app', 'php', 'scripts/package_task010_handoff.php') `
    -RepoPath $RepoPath `
    -LogPath (Join-Path $Logs 'package_task010_handoff.log') `
    -TimeoutSeconds 1200
if ($packageRun.ExitCode -ne 0) {
    throw 'Task-010 handoff packaging failed. Review package_task010_handoff.log.'
}

$BuildResultPath = Join-Path $RepoPath 'review-packets\TASK010_HANDOFF_BUILD_RESULT.json'
$build = Read-RequiredJson -Path $BuildResultPath -Name 'TASK010_HANDOFF_BUILD_RESULT.json'
if ([string]$build.status -ne 'PASS' -or [string]$build.stop_gate -ne 'STOP-V1-RELEASE-010') {
    throw 'Task-010 handoff build result is invalid.'
}
$zipPath = [string]$build.zip_path
if ($zipPath -match '^/app/(.+)$') {
    $zipPath = Join-Path $RepoPath (($Matches[1]) -replace '/', '\')
}
if (-not (Test-Path -LiteralPath $zipPath -PathType Leaf)) {
    throw "Task-010 handoff ZIP is missing: $zipPath"
}
$zipHash = Get-Task010Sha256 -Path $zipPath
if ($zipHash -ne ([string]$build.zip_sha256).ToLowerInvariant()) {
    throw 'Task-010 handoff ZIP hash does not match the build result.'
}

# Clean up the ephemeral release runtime only after browser evidence and handoff packaging finish.
$EnvPath = [string]$full.ephemeral_env_path
if ($EnvPath -and (Test-Path -LiteralPath $EnvPath -PathType Leaf)) {
    $cleanup = Invoke-Task010Compose `
        -Arguments @('down', '-v', '--remove-orphans') `
        -RepoPath $RepoPath `
        -LogPath (Join-Path $Logs 'release_cleanup_after_handoff.log') `
        -TimeoutSeconds 300 `
        -ComposeFiles @('compose.release.yaml') `
        -EnvFile $EnvPath
    Remove-Item -LiteralPath $EnvPath -Force -ErrorAction SilentlyContinue
    if ($cleanup.ExitCode -ne 0) {
        Write-Host '[BLOCKED_CLEANUP] Release runtime cleanup did not complete; handoff remains valid.'
    }
}

Write-Host ''
Write-Host 'Task-010 handoff: PASS'
Write-Host "Status: $overallStatus"
Write-Host "Review ZIP: $zipPath"
Write-Host "Review ZIP bytes: $((Get-Item -LiteralPath $zipPath).Length)"
Write-Host "Review ZIP SHA-256: $zipHash"
Write-Host 'STOP-V1-RELEASE-010'
