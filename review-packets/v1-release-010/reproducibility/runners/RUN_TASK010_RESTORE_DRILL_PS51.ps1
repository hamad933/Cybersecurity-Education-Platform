param([Parameter(Mandatory = $true)][string]$RepoPath)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
$Logs = Join-Path $Packet 'local-verification\restore-drill'
[void](New-Item -ItemType Directory -Path $Logs -Force)
$results = New-Object System.Collections.ArrayList
$testPrefix = Get-Task010TestExecPrefix

function Run-RestoreCompose {
    param([string]$Name, [string[]]$Arguments, [int]$Timeout = 1200, [bool]$Required = $true)
    $run = Invoke-Task010Compose -Arguments $Arguments -RepoPath $RepoPath -LogPath (Join-Path $Logs ($Name + '.log')) -TimeoutSeconds $Timeout
    $status = if ($run.ExitCode -eq 0) { 'PASS' } elseif ($run.TimedOut) { 'INCOMPLETE_TIMEOUT' } else { if ($Required) { 'FAIL' } else { 'BLOCKED' } }
    [void]$results.Add((New-Task010Result -Name $Name -Status $status -LogPath $run.LogPath -ExitCode $run.ExitCode -DurationSeconds $run.DurationSeconds))
    Write-Host "[$status] $Name"
    return $run
}

$started = [DateTimeOffset]::UtcNow
$prepareSchema = Run-RestoreCompose -Name 'restore_source_migrate_seed' -Arguments ($testPrefix + @('php', 'artisan', 'migrate:fresh', '--force', '--seed')) -Timeout 1200
if ($prepareSchema.ExitCode -ne 0) { throw 'Restore source database could not be prepared.' }
$prepare = Run-RestoreCompose -Name 'restore_backup_prepare' -Arguments ($testPrefix + @('php', 'artisan', 'platform:restore-drill-prepare')) -Timeout 1200
if ($prepare.ExitCode -ne 0) { throw 'Restore drill backup could not be created.' }
$prepareLine = @($prepare.StdOut -split "`r?`n" | Where-Object { $_.Trim().StartsWith('{') }) | Select-Object -Last 1
if (-not $prepareLine) { throw 'Restore preparation JSON was not emitted.' }
$prepared = $prepareLine | ConvertFrom-Json
if ($prepared.status -ne 'PASS' -or -not $prepared.actor_id -or -not $prepared.archive_path) { throw 'Restore preparation output is incomplete.' }

$dropBefore = Run-RestoreCompose -Name 'restore_database_drop_before' -Arguments @('exec', '-T', 'postgres', 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'cyber_platform', '-d', 'postgres', '-c', 'DROP DATABASE IF EXISTS cyber_platform_restore_drill WITH (FORCE);') -Timeout 180
$create = Run-RestoreCompose -Name 'restore_database_create' -Arguments @('exec', '-T', 'postgres', 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'cyber_platform', '-d', 'postgres', '-c', 'CREATE DATABASE cyber_platform_restore_drill OWNER cyber_platform;') -Timeout 180
if ($dropBefore.ExitCode -ne 0 -or $create.ExitCode -ne 0) { throw 'Isolated restore database could not be created.' }

$restorePrefix = @(
    'exec', '-T',
    '-e', 'APP_ENV=testing',
    '-e', 'APP_PROFILE=test',
    '-e', 'DB_DATABASE=cyber_platform_restore_drill',
    '-e', 'DB_HOST=postgres',
    '-e', 'TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql',
    '-e', 'TEST_DATABASE_ALLOWED_HOSTS=postgres',
    'app'
)
$restoreResult = $null
try {
    $migrate = Run-RestoreCompose -Name 'restore_target_migrate' -Arguments ($restorePrefix + @('php', 'artisan', 'migrate', '--force')) -Timeout 1200
    if ($migrate.ExitCode -ne 0) { throw 'Restore target migration failed.' }
    $apply = Run-RestoreCompose -Name 'restore_apply_and_verify' -Arguments ($restorePrefix + @('php', 'artisan', 'platform:restore-apply', [string]$prepared.archive_path, [string]$prepared.actor_id)) -Timeout 2400
    if ($apply.ExitCode -ne 0) { throw 'Restore apply or verification failed.' }
    $resultLine = @($apply.StdOut -split "`r?`n" | Where-Object { $_.Trim().StartsWith('{') }) | Select-Object -Last 1
    if (-not $resultLine) { throw 'Restore verification JSON was not emitted.' }
    $restoreResult = $resultLine | ConvertFrom-Json
    if ($restoreResult.status -ne 'verified' -or -not $restoreResult.verification.valid) {
        throw 'Restore drill did not produce a verified result.'
    }
} finally {
    [void](Run-RestoreCompose -Name 'restore_database_drop_after' -Arguments @('exec', '-T', 'postgres', 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'cyber_platform', '-d', 'postgres', '-c', 'DROP DATABASE IF EXISTS cyber_platform_restore_drill WITH (FORCE);') -Timeout 180 -Required $false)
}

$failed = @($results | Where-Object { $_.status -in @('FAIL', 'INCOMPLETE_TIMEOUT') })
$summary = [ordered]@{
    status = if ($failed.Count -eq 0 -and $restoreResult.status -eq 'verified') { 'PASS' } else { 'FAIL' }
    generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
    duration_seconds = [Math]::Round(([DateTimeOffset]::UtcNow - $started).TotalSeconds, 3)
    source_database = 'cyber_platform_test'
    restore_database = 'cyber_platform_restore_drill'
    restore_database_removed = $true
    backup_manifest_id = [string]$prepared.backup_manifest_id
    restore_result = $restoreResult
    failed_gates = @($failed | ForEach-Object { $_.name })
    gates = @($results)
    rpo_rto_claim = 'NOT_DECLARED_MEASUREMENT_ONLY'
}
$summaryPath = Join-Path $Packet 'RESTORE_DRILL_RESULT.json'
Write-Task010Utf8NoBom -Path $summaryPath -Content ((ConvertTo-Task010Json $summary) + "`r`n")
Write-Host "Restore drill result: $summaryPath"
if ($summary.status -ne 'PASS') { throw 'Task-010 restore drill failed.' }
Write-Host 'TASK-010 isolated restore drill: PASS'
