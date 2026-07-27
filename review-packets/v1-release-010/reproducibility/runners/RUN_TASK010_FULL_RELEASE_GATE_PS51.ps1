param([Parameter(Mandatory = $true)][string]$RepoPath)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path -LiteralPath $RepoPath).Path
. (Join-Path $PSScriptRoot 'TASK010.Common.ps1')

$Packet = Join-Path $RepoPath 'review-packets\v1-release-010'
$Logs = Join-Path $Packet 'local-verification\full-release'
[void](New-Item -ItemType Directory -Path $Logs -Force)
$results = New-Object System.Collections.ArrayList

function Add-FullResult {
    param([string]$Name, $Run, [bool]$Required = $true, [string]$Detail = '')
    $status = if ($Run.ExitCode -eq 0) { 'PASS' } elseif ($Run.TimedOut) { 'INCOMPLETE_TIMEOUT' } else { if ($Required) { 'FAIL' } else { 'BLOCKED' } }
    [void]$results.Add((New-Task010Result -Name $Name -Status $status -LogPath $Run.LogPath -ExitCode $Run.ExitCode -DurationSeconds $Run.DurationSeconds -Detail $Detail))
    Write-Host "[$status] $Name"
    return $status
}

function Run-Dev {
    param([string]$Name, [string[]]$Arguments, [int]$Timeout = 1200, [bool]$Required = $true)
    $run = Invoke-Task010Compose -Arguments $Arguments -RepoPath $RepoPath -LogPath (Join-Path $Logs ($Name + '.log')) -TimeoutSeconds $Timeout
    [void](Add-FullResult -Name $Name -Run $run -Required $Required)
    return $run
}

$testPrefix = Get-Task010TestExecPrefix
# Exactly one full PHP regression run for Task-010.
[void](Run-Dev -Name 'full_php_regression_single_run' -Arguments ($testPrefix + @('php', 'artisan', 'test', '--compact')) -Timeout 3600)
[void](Run-Dev -Name 'phpstan_full' -Arguments ($testPrefix + @('vendor/bin/phpstan', 'analyse', '--memory-limit=1G', '--no-progress')) -Timeout 1800)
[void](Run-Dev -Name 'pint_full_check' -Arguments ($testPrefix + @('vendor/bin/pint', '--test')) -Timeout 1200)
[void](Run-Dev -Name 'composer_audit' -Arguments ($testPrefix + @('composer', 'audit', '--locked', '--no-interaction')) -Timeout 900)
[void](Run-Dev -Name 'secret_scan' -Arguments ($testPrefix + @('php', 'scripts/secret_scan.php')) -Timeout 900)
[void](Run-Dev -Name 'release_compose_validation' -Arguments ($testPrefix + @('php', 'scripts/validate_release_compose.php')) -Timeout 120)

$mounts = @(
    'run', '--rm',
    '--mount', "type=bind,source=$RepoPath,target=/app",
    '--mount', 'type=volume,source=task010-node-modules,target=/app/node_modules',
    '--mount', 'type=volume,source=task010-npm-cache,target=/root/.npm',
    '-w', '/app',
    'node:24.18.0-bookworm-slim'
)
foreach ($command in @(
    [ordered]@{ Name = 'frontend_full_format_check'; Args = @('npm', 'run', 'format:check'); Timeout = 600 },
    [ordered]@{ Name = 'frontend_full_typecheck'; Args = @('npm', 'run', 'typecheck'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_full_lint'; Args = @('npm', 'run', 'lint'); Timeout = 900 },
    [ordered]@{ Name = 'frontend_full_tests'; Args = @('npm', 'test'); Timeout = 1200 },
    [ordered]@{ Name = 'frontend_full_build'; Args = @('npm', 'run', 'build'); Timeout = 1200 },
    [ordered]@{ Name = 'npm_audit_high'; Args = @('npm', 'audit', '--audit-level=high'); Timeout = 900 }
)) {
    $run = Invoke-Task010Docker -Arguments ($mounts + [string[]]$command.Args) -RepoPath $RepoPath -LogPath (Join-Path $Logs ($command.Name + '.log')) -TimeoutSeconds ([int]$command.Timeout)
    [void](Add-FullResult -Name ([string]$command.Name) -Run $run)
}

# Generate ephemeral release-gate secrets. They are ignored, never printed, and removed by the browser gate.
$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$keyBytes = New-Object byte[] 32
$rng.GetBytes($keyBytes)
$appKey = 'base64:' + [Convert]::ToBase64String($keyBytes)
$passwordBytes = New-Object byte[] 32
$rng.GetBytes($passwordBytes)
$dbPassword = ([BitConverter]::ToString($passwordBytes)).Replace('-', '').ToLowerInvariant()
$rng.Dispose()
$EnvPath = Join-Path $RepoPath '.env.task010.release-gate'
$envText = @(
    'APP_PORT=18081',
    "APP_KEY=$appKey",
    "DB_PASSWORD=$dbPassword",
    'RELEASE_IMAGE=cybersecurity-education-platform:task010-gate',
    ''
) -join "`r`n"
Write-Task010Utf8NoBom -Path $EnvPath -Content $envText

$releaseFiles = @('compose.release.yaml')
$downBefore = Invoke-Task010Compose -Arguments @('down', '-v', '--remove-orphans') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_cleanup_before.log') -TimeoutSeconds 300 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_cleanup_before' -Run $downBefore -Required $false)
$build = Invoke-Task010Compose -Arguments @('build', 'app') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_image_build.log') -TimeoutSeconds 3600 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_image_build' -Run $build)
$up = Invoke-Task010Compose -Arguments @('up', '-d', '--no-build') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_runtime_start.log') -TimeoutSeconds 600 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_runtime_start' -Run $up)
Start-Sleep -Seconds 8
$migrate = Invoke-Task010Compose -Arguments @('exec', '-T', 'app', 'php', 'artisan', 'migrate', '--force', '--seed') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_migrate_seed.log') -TimeoutSeconds 1200 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_migrate_seed' -Run $migrate)
$check = Invoke-Task010Compose -Arguments @('exec', '-T', 'app', 'php', 'artisan', 'platform:release-check', '--json') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_readiness.log') -TimeoutSeconds 300 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_readiness' -Run $check)
$queueSmoke = Invoke-Task010Compose -Arguments @('exec', '-T', 'app', 'php', 'artisan', 'platform:queue-smoke', '--timeout=60', '--poll-ms=250', '--json') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_queue_smoke.log') -TimeoutSeconds 120 -ComposeFiles $releaseFiles -EnvFile $EnvPath
[void](Add-FullResult -Name 'release_queue_smoke' -Run $queueSmoke)
$ps = Invoke-Task010Compose -Arguments @('ps', '--status', 'running') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_services_running.log') -TimeoutSeconds 120 -ComposeFiles $releaseFiles -EnvFile $EnvPath
$psStatus = Add-FullResult -Name 'release_services_running' -Run $ps
if ($psStatus -eq 'PASS') {
    foreach ($service in @('app', 'queue', 'postgres')) {
        if ($ps.StdOut -notmatch "(?m)$service") {
            [void]$results.Add((New-Task010Result -Name "release_service_$service" -Status 'FAIL' -LogPath $ps.LogPath -ExitCode 1 -DurationSeconds 0 -Detail 'Service missing from running Compose set.'))
        }
    }
}

$httpLog = Join-Path $Logs 'release_http_probe.log'
$httpStatus = 'FAIL'
$httpDetail = ''
$httpStart = Get-Date
for ($attempt = 1; $attempt -le 10; $attempt++) {
    try {
        $request = [System.Net.HttpWebRequest]::Create('http://127.0.0.1:18081/health/live')
        $request.Timeout = 5000
        $response = $request.GetResponse()
        $code = [int]$response.StatusCode
        $response.Close()
        if ($code -eq 200) { $httpStatus = 'PASS'; $httpDetail = 'HTTP 200'; break }
        $httpDetail = "HTTP $code"
    } catch {
        $httpDetail = $_.Exception.Message
        Start-Sleep -Seconds 2
    }
}
$httpDuration = ((Get-Date) - $httpStart).TotalSeconds
Write-Task010Utf8NoBom -Path $httpLog -Content ("STATUS=$httpStatus`r`nDETAIL=$httpDetail`r`n")
[void]$results.Add((New-Task010Result -Name 'release_http_loopback' -Status $httpStatus -LogPath $httpLog -ExitCode $(if ($httpStatus -eq 'PASS') { 0 } else { 1 }) -DurationSeconds $httpDuration -Detail $httpDetail))
Write-Host "[$httpStatus] release_http_loopback"

$healthLog = Join-Path $Logs 'release_final_service_health.log'
$healthStart = Get-Date
$healthDeadline = $healthStart.AddSeconds(120)
$healthStatus = 'FAIL'
$healthDetail = 'Final service state was not resolved.'
$healthSnapshots = @()
do {
    $snapshot = Invoke-Task010Compose -Arguments @('ps', '--format', 'json') -RepoPath $RepoPath -LogPath (Join-Path $Logs 'release_final_service_health_snapshot.log') -TimeoutSeconds 60 -ComposeFiles $releaseFiles -EnvFile $EnvPath
    if ($snapshot.ExitCode -eq 0) {
        try {
            $composeJson = $snapshot.StdOut.Trim()
            if ([string]::IsNullOrWhiteSpace($composeJson)) {
                throw 'Docker Compose returned an empty service-state payload.'
            }

            if ($composeJson.StartsWith('[')) {
                $rows = @($composeJson | ConvertFrom-Json)
            }
            else {
                $rows = @()
                foreach ($jsonLine in @($composeJson -split "`r?`n")) {
                    $trimmedLine = $jsonLine.Trim()
                    if ([string]::IsNullOrWhiteSpace($trimmedLine)) {
                        continue
                    }
                    $rows += @($trimmedLine | ConvertFrom-Json)
                }
            }

            if ($rows.Count -eq 0) {
                throw 'Docker Compose returned no service-state rows.'
            }
            $byService = @{}
            foreach ($row in $rows) {
                $serviceProperty = $row.PSObject.Properties['Service']
                $serviceName = if ($null -ne $serviceProperty) { [string]$serviceProperty.Value } else { '' }
                if (-not [string]::IsNullOrWhiteSpace($serviceName)) {
                    $byService[$serviceName] = $row
                }
            }

            $stateLines = @()
            $invalid = @()
            foreach ($service in @('app', 'queue', 'postgres')) {
                if (-not $byService.ContainsKey($service)) {
                    $invalid += "${service}:missing"
                    $stateLines += "$service state=missing health=missing"
                    continue
                }

                $row = $byService[$service]
                $stateProperty = $row.PSObject.Properties['State']
                $healthProperty = $row.PSObject.Properties['Health']
                $state = if ($null -ne $stateProperty) { ([string]$stateProperty.Value).ToLowerInvariant() } else { '' }
                $health = if ($null -ne $healthProperty) { ([string]$healthProperty.Value).ToLowerInvariant() } else { '' }
                if ([string]::IsNullOrWhiteSpace($health)) {
                    $health = 'none'
                }
                $stateLines += "$service state=$state health=$health"

                if ($state -ne 'running') {
                    $invalid += "${service}:$state"
                    continue
                }
                if ($service -in @('app', 'postgres') -and $health -ne 'healthy') {
                    $invalid += "${service}:health=$health"
                    continue
                }
                if ($service -eq 'queue' -and $health -notin @('none', 'disabled')) {
                    $invalid += "${service}:health=$health"
                }
            }

            $stamp = [DateTimeOffset]::UtcNow.ToString('o')
            $healthSnapshots += "$stamp  $($stateLines -join '; ')"
            if ($invalid.Count -eq 0) {
                $healthStatus = 'PASS'
                $healthDetail = $stateLines -join '; '
                break
            }
            $healthDetail = $invalid -join ', '
        }
        catch {
            $healthDetail = 'Unable to parse Docker Compose service state: ' + $_.Exception.Message
        }
    }
    else {
        $healthDetail = "docker compose ps failed with exit code $($snapshot.ExitCode)."
    }

    Start-Sleep -Seconds 3
} while ((Get-Date) -lt $healthDeadline)

$healthDuration = ((Get-Date) - $healthStart).TotalSeconds
$healthContent = @(
    "STATUS=$healthStatus",
    "DETAIL=$healthDetail",
    'EXPECTED=app:running+healthy; queue:running+health-disabled; postgres:running+healthy',
    'SNAPSHOTS:',
    ($healthSnapshots -join "`r`n"),
    ''
) -join "`r`n"
Write-Task010Utf8NoBom -Path $healthLog -Content $healthContent
[void]$results.Add((New-Task010Result -Name 'release_final_service_health' -Status $healthStatus -LogPath $healthLog -ExitCode $(if ($healthStatus -eq 'PASS') { 0 } else { 1 }) -DurationSeconds $healthDuration -Detail $healthDetail))
Write-Host "[$healthStatus] release_final_service_health"

$failed = @($results | Where-Object { $_.status -in @('FAIL', 'INCOMPLETE_TIMEOUT') })
$summary = [ordered]@{
    status = if ($failed.Count -eq 0) { 'PASS' } else { 'FAIL' }
    full_php_regression_runs = 1
    second_full_run_performed = $false
    generated_at_utc = [DateTimeOffset]::UtcNow.ToString('o')
    release_url = 'http://127.0.0.1:18081'
    ephemeral_env_path = $EnvPath
    failed_gates = @($failed | ForEach-Object { $_.name })
    results = @($results)
}
$summaryPath = Join-Path $Packet 'FULL_RELEASE_GATE_SUMMARY.json'
Write-Task010Utf8NoBom -Path $summaryPath -Content ((ConvertTo-Task010Json $summary) + "`r`n")
Write-Host "Full release summary: $summaryPath"
if ($failed.Count -gt 0) {
    throw "Task-010 full release gate failed: $(@($failed | ForEach-Object { $_.name }) -join ', ')"
}
Write-Host 'TASK-010 full release gate: PASS'
