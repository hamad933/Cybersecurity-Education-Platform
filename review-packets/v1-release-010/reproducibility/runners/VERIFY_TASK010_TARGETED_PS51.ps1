param([Parameter(Mandatory = $true)][string]$RepoPath)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
$Logs = Join-Path $Packet 'local-verification\targeted'
[void](New-Item -ItemType Directory -Path $Logs -Force)
$results = New-Object System.Collections.ArrayList

function Add-GateResult {
    param([string]$Name, $ProcessResult, [bool]$Required = $true, [string]$Detail = '')
    $status = if ($ProcessResult.ExitCode -eq 0) { 'PASS' } elseif ($ProcessResult.TimedOut) { 'INCOMPLETE_TIMEOUT' } else { if ($Required) { 'FAIL' } else { 'BLOCKED' } }
    [void]$results.Add((New-Task010Result -Name $Name -Status $status -LogPath $ProcessResult.LogPath -ExitCode $ProcessResult.ExitCode -DurationSeconds $ProcessResult.DurationSeconds -Detail $Detail))
    Write-Host "[$status] $Name"
    return $status
}

function Run-ComposeGate {
    param([string]$Name, [string[]]$Arguments, [int]$Timeout = 600, [bool]$Required = $true)
    $log = Join-Path $Logs ($Name + '.log')
    $run = Invoke-Task010Compose -Arguments $Arguments -RepoPath $RepoPath -LogPath $log -TimeoutSeconds $Timeout
    $status = Add-GateResult -Name $Name -ProcessResult $run -Required $Required
    return [pscustomobject]@{ Run = $run; Status = $status }
}

# Existing Task-009 development runtime is preferred; one bounded build fallback is allowed.
$start = Run-ComposeGate -Name 'runtime_start_no_build' -Arguments @('up', '-d', '--no-build') -Timeout 180
if ($start.Status -ne 'PASS') {
    $build = Run-ComposeGate -Name 'runtime_build_single_fallback' -Arguments @('build', 'app') -Timeout 1800
    if ($build.Status -ne 'PASS') { throw 'Docker development image could not be reused or built once.' }
    $retry = Run-ComposeGate -Name 'runtime_start_after_build' -Arguments @('up', '-d', '--no-build') -Timeout 180
    if ($retry.Status -ne 'PASS') { throw 'Docker development runtime could not start.' }
}

$appPhp = Run-ComposeGate -Name 'runtime_app_php' -Arguments @('exec', '-T', 'app', 'php', '-v') -Timeout 60
$postgres = Run-ComposeGate -Name 'runtime_postgres' -Arguments @('exec', '-T', 'postgres', 'pg_isready', '-U', 'cyber_platform', '-d', 'cyber_platform') -Timeout 60
if ($appPhp.Status -ne 'PASS' -or $postgres.Status -ne 'PASS') { throw 'The app or PostgreSQL runtime is not healthy.' }

$vendorProbe = Run-ComposeGate -Name 'vendor_ready_probe' -Arguments @('exec', '-T', 'app', 'sh', '-lc', 'test -f vendor/autoload.php') -Timeout 60 -Required $false
if ($vendorProbe.Status -ne 'PASS') {
    $vendorInstall = Run-ComposeGate -Name 'vendor_locked_install' -Arguments @('run', '--rm', '--no-deps', '--user', 'root', 'app', 'sh', '-lc', 'composer install --no-interaction --no-progress --prefer-dist && chown -R www-data:www-data /app/vendor') -Timeout 1800
    if ($vendorInstall.Status -ne 'PASS') { throw 'Locked Composer dependencies could not be installed.' }
}

$formatApply = Run-ComposeGate -Name 'php_format_apply_once' -Arguments @('exec', '-T', '--user', 'root', 'app', 'vendor/bin/pint', '--parallel') -Timeout 1200
if ($formatApply.Status -ne 'PASS') { throw 'PHP source formatting could not be normalized once.' }

$storage = Run-ComposeGate -Name 'storage_permissions' -Arguments @('exec', '-T', '--user', 'root', 'app', 'sh', '-lc', 'mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/private && touch storage/logs/laravel.log && chown -R www-data:www-data storage && chmod -R ug+rwX storage') -Timeout 120
if ($storage.Status -ne 'PASS') { throw 'Laravel storage permissions could not be prepared.' }

$dropDb = Run-ComposeGate -Name 'isolated_test_database_drop' -Arguments @('exec', '-T', 'postgres', 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'cyber_platform', '-d', 'postgres', '-c', 'DROP DATABASE IF EXISTS cyber_platform_test WITH (FORCE);') -Timeout 120
$createDb = Run-ComposeGate -Name 'isolated_test_database_create' -Arguments @('exec', '-T', 'postgres', 'psql', '-v', 'ON_ERROR_STOP=1', '-U', 'cyber_platform', '-d', 'postgres', '-c', 'CREATE DATABASE cyber_platform_test OWNER cyber_platform;') -Timeout 120
if ($dropDb.Status -ne 'PASS' -or $createDb.Status -ne 'PASS') { throw 'The isolated PostgreSQL test database could not be prepared.' }

$testPrefix = Get-Task010TestExecPrefix
$migrate = Run-ComposeGate -Name 'testing_migrate_fresh_seed' -Arguments ($testPrefix + @('php', 'artisan', 'migrate:fresh', '--force', '--seed')) -Timeout 600
if ($migrate.Status -ne 'PASS') { throw 'Task-010 schema migration or seed failed.' }

$phpGroups = @(
    [ordered]@{ Name = 'php_package_security'; Files = @('tests/Unit/PackagePathGuardTest.php', 'tests/Integration/PortablePackageSecurityTest.php') },
    [ordered]@{ Name = 'php_task010_import'; Files = @('tests/Integration/Task010ImportSecurityTest.php') },
    [ordered]@{ Name = 'php_audit_chain'; Files = @('tests/Integration/AuditIntegrityTest.php') },
    [ordered]@{ Name = 'php_manual_ai'; Files = @('tests/Integration/ManualAiBridgeTest.php') },
    [ordered]@{ Name = 'php_search_queue'; Files = @('tests/Integration/SearchQueueTest.php') },
    [ordered]@{ Name = 'php_backup_restore'; Files = @('tests/Integration/BackupRestoreTest.php') },
    [ordered]@{ Name = 'php_release_center'; Files = @('tests/Feature/Task010ReleaseCenterTest.php') },
    [ordered]@{ Name = 'php_task010_boundaries'; Files = @('tests/Architecture/Task010BoundaryTest.php', 'tests/Architecture/ModuleBoundaryTest.php') },
    [ordered]@{ Name = 'php_affected_auth'; Files = @('tests/Feature/AuthenticationTest.php') },
    [ordered]@{ Name = 'php_affected_vertical_slices'; Files = @('tests/Feature/Vs001LifecycleTest.php', 'tests/Feature/Vs002LifecycleTest.php', 'tests/Feature/Vs003InvestigationTest.php') },
    [ordered]@{ Name = 'php_migration_lifecycle'; Files = @('tests/Integration/MigrationLifecycleTest.php') }
)
foreach ($group in $phpGroups) {
    [string[]]$testArguments = $testPrefix + @('php', 'artisan', 'test') + [string[]]$group.Files + @('--compact')
    [void](Run-ComposeGate -Name ([string]$group.Name) -Arguments $testArguments -Timeout 1200)
}
[void](Run-ComposeGate -Name 'php_format_check' -Arguments ($testPrefix + @('vendor/bin/pint', '--test')) -Timeout 900)
[void](Run-ComposeGate -Name 'release_compose_static_validation' -Arguments ($testPrefix + @('php', 'scripts/validate_release_compose.php')) -Timeout 120)

# Locked frontend dependencies and gates in an isolated named volume.
$nodeInspect = Invoke-Task010Docker -Arguments @('image', 'inspect', 'node:24.18.0-bookworm-slim') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'node_image_probe.log') -TimeoutSeconds 60
if ($nodeInspect.ExitCode -ne 0) {
    $nodePull = Invoke-Task010Docker -Arguments @('pull', 'node:24.18.0-bookworm-slim') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'node_image_pull.log') -TimeoutSeconds 900
    [void](Add-GateResult -Name 'node_image_pull_single_attempt' -ProcessResult $nodePull -Required $true)
}
$volume1 = Invoke-Task010Docker -Arguments @('volume', 'create', 'task010-node-modules') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'node_modules_volume.log') -TimeoutSeconds 60
$volume2 = Invoke-Task010Docker -Arguments @('volume', 'create', 'task010-npm-cache') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'npm_cache_volume.log') -TimeoutSeconds 60
[void](Add-GateResult -Name 'node_modules_volume' -ProcessResult $volume1)
[void](Add-GateResult -Name 'npm_cache_volume' -ProcessResult $volume2)

$mounts = @(
    'run', '--rm',
    '--mount', "type=bind,source=$RepoPath,target=/app",
    '--mount', 'type=volume,source=task010-node-modules,target=/app/node_modules',
    '--mount', 'type=volume,source=task010-npm-cache,target=/root/.npm',
    '-w', '/app',
    'node:24.18.0-bookworm-slim'
)
$nodeCommands = @(
    [ordered]@{ Name = 'frontend_locked_dependencies'; Args = @('npm', 'ci', '--ignore-scripts', '--no-audit', '--no-fund'); Timeout = 1800 },
    [ordered]@{ Name = 'frontend_format_apply_once'; Args = @('npm', 'run', 'format'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_format_check'; Args = @('npm', 'run', 'format:check'); Timeout = 600 },
    [ordered]@{ Name = 'frontend_typecheck'; Args = @('npm', 'run', 'typecheck'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_lint'; Args = @('npm', 'run', 'lint'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_task010_tests'; Args = @('npm', 'test', '--', 'resources/js/tests/Task010ReleaseCenter.spec.ts'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_build'; Args = @('npm', 'run', 'build'); Timeout = 1200 }
)
foreach ($command in $nodeCommands) {
    $run = Invoke-Task010Docker -Arguments ($mounts + [string[]]$command.Args) -RepoPath $RepoPath -LogPath (Join-Path $Logs ($command.Name + '.log')) -TimeoutSeconds ([int]$command.Timeout)
    [void](Add-GateResult -Name ([string]$command.Name) -ProcessResult $run)
}

$failed = @($results | Where-Object { $_.status -in @('FAIL', 'INCOMPLETE_TIMEOUT') })
$summary = [ordered]@{
    status = if ($failed.Count -eq 0) { 'PASS' } else { 'FAIL' }
    generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
    required_checkpoint = '83b932a079bf2237dbfa033a4322c6bded042842'
    gate_count = $results.Count
    failed_gates = @($failed | ForEach-Object { $_.name })
    results = @($results)
}
$summaryPath = Join-Path $Packet 'TARGETED_GATE_SUMMARY.json'
Write-Task010Utf8NoBom -Path $summaryPath -Content ((ConvertTo-Task010Json $summary) + "`r`n")
Write-Host "Targeted summary: $summaryPath"
if ($failed.Count -gt 0) {
    throw "Task-010 targeted verification failed: $(@($failed | ForEach-Object { $_.name }) -join ', ')"
}
Write-Host 'TASK-010 targeted verification: PASS'
