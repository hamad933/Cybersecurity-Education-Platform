Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function ConvertTo-Task010QuotedArgument {
    param([AllowEmptyString()][string]$Value)

    if ($Value.Length -gt 0 -and $Value -notmatch '[\s"]') {
        return $Value
    }

    $builder = New-Object System.Text.StringBuilder
    [void]$builder.Append('"')
    $backslashes = 0
    foreach ($character in $Value.ToCharArray()) {
        if ($character -eq '\') {
            $backslashes++
            continue
        }
        if ($character -eq '"') {
            [void]$builder.Append(('\' * (($backslashes * 2) + 1)))
            [void]$builder.Append('"')
            $backslashes = 0
            continue
        }
        if ($backslashes -gt 0) {
            [void]$builder.Append(('\' * $backslashes))
            $backslashes = 0
        }
        [void]$builder.Append($character)
    }
    if ($backslashes -gt 0) {
        [void]$builder.Append(('\' * ($backslashes * 2)))
    }
    [void]$builder.Append('"')
    return $builder.ToString()
}

function Write-Task010Utf8NoBom {
    param([Parameter(Mandatory = $true)][string]$Path, [Parameter(Mandatory = $true)][string]$Content)
    $parent = Split-Path -Parent $Path
    if ($parent -and -not (Test-Path -LiteralPath $parent -PathType Container)) {
        [void](New-Item -ItemType Directory -Path $parent -Force)
    }
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $encoding)
}

function Invoke-Task010Process {
    param(
        [Parameter(Mandatory = $true)][string]$FilePath,
        [string[]]$Arguments = @(),
        [Parameter(Mandatory = $true)][string]$WorkingDirectory,
        [Parameter(Mandatory = $true)][string]$LogPath,
        [hashtable]$Environment = @{},
        [int]$TimeoutSeconds = 0
    )

    $resolved = (Get-Command $FilePath -ErrorAction Stop).Source
    $argumentLine = (($Arguments | ForEach-Object { ConvertTo-Task010QuotedArgument ([string]$_) }) -join ' ')
    $start = New-Object System.Diagnostics.ProcessStartInfo
    $start.FileName = $resolved
    $start.Arguments = $argumentLine
    $start.WorkingDirectory = $WorkingDirectory
    $start.UseShellExecute = $false
    $start.RedirectStandardOutput = $true
    $start.RedirectStandardError = $true
    $start.CreateNoWindow = $true
    foreach ($entry in $Environment.GetEnumerator()) {
        $start.EnvironmentVariables[[string]$entry.Key] = [string]$entry.Value
    }

    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $start
    $startedAt = [DateTimeOffset]::UtcNow
    if (-not $process.Start()) {
        throw "Unable to start native process: $resolved"
    }
    $stdoutTask = $process.StandardOutput.ReadToEndAsync()
    $stderrTask = $process.StandardError.ReadToEndAsync()
    $timedOut = $false
    if ($TimeoutSeconds -gt 0) {
        if (-not $process.WaitForExit($TimeoutSeconds * 1000)) {
            $timedOut = $true
            try { $process.Kill() } catch {}
            try { $process.WaitForExit() } catch {}
        }
    }
    if (-not $timedOut) {
        $process.WaitForExit()
    }
    try { $stdout = $stdoutTask.Result } catch { $stdout = '' }
    try { $stderr = $stderrTask.Result } catch { $stderr = '' }
    $finishedAt = [DateTimeOffset]::UtcNow
    $exitCode = if ($timedOut) { 124 } else { $process.ExitCode }

    $log = @(
        "COMMAND=$resolved $argumentLine",
        "WORKING_DIRECTORY=$WorkingDirectory",
        "STARTED_UTC=$($startedAt.ToString('o'))",
        "FINISHED_UTC=$($finishedAt.ToString('o'))",
        "DURATION_SECONDS=$([Math]::Round(($finishedAt - $startedAt).TotalSeconds, 3))",
        "TIMED_OUT=$timedOut",
        "EXIT_CODE=$exitCode",
        '--- STDOUT ---',
        $stdout.TrimEnd(),
        '--- STDERR ---',
        $stderr.TrimEnd(),
        ''
    ) -join "`r`n"
    Write-Task010Utf8NoBom -Path $LogPath -Content $log

    return [pscustomobject]@{
        ExitCode = $exitCode
        TimedOut = $timedOut
        StdOut = $stdout
        StdErr = $stderr
        DurationSeconds = [Math]::Round(($finishedAt - $startedAt).TotalSeconds, 3)
        LogPath = $LogPath
        Command = "$resolved $argumentLine"
    }
}

function Invoke-Task010Docker {
    param(
        [Parameter(Mandatory = $true)][string[]]$Arguments,
        [Parameter(Mandatory = $true)][string]$RepoPath,
        [Parameter(Mandatory = $true)][string]$LogPath,
        [int]$TimeoutSeconds = 0,
        [hashtable]$Environment = @{}
    )
    return Invoke-Task010Process -FilePath 'docker.exe' -Arguments $Arguments -WorkingDirectory $RepoPath -LogPath $LogPath -TimeoutSeconds $TimeoutSeconds -Environment $Environment
}

function Invoke-Task010Compose {
    param(
        [Parameter(Mandatory = $true)][string[]]$Arguments,
        [Parameter(Mandatory = $true)][string]$RepoPath,
        [Parameter(Mandatory = $true)][string]$LogPath,
        [int]$TimeoutSeconds = 0,
        [string[]]$ComposeFiles = @('compose.yaml', 'compose.dev.yaml'),
        [string]$EnvFile = '',
        [hashtable]$Environment = @{}
    )
    $dockerArguments = @('compose')
    if ($EnvFile -ne '') {
        $dockerArguments += @('--env-file', $EnvFile)
    }
    foreach ($file in $ComposeFiles) {
        $dockerArguments += @('-f', $file)
    }
    $dockerArguments += $Arguments
    return Invoke-Task010Docker -Arguments $dockerArguments -RepoPath $RepoPath -LogPath $LogPath -TimeoutSeconds $TimeoutSeconds -Environment $Environment
}

function Get-Task010TestExecPrefix {
    return @(
        'exec', '-T',
        '-e', 'APP_ENV=testing',
        '-e', 'APP_PROFILE=test',
        '-e', 'DB_DATABASE=cyber_platform_test',
        '-e', 'DB_HOST=postgres',
        '-e', 'TEST_DATABASE_ALLOWED_CONNECTIONS=pgsql',
        '-e', 'TEST_DATABASE_ALLOWED_HOSTS=postgres',
        'app'
    )
}

function ConvertTo-Task010Json {
    param([Parameter(Mandatory = $true)]$Value)
    return ($Value | ConvertTo-Json -Depth 20)
}

function New-Task010Result {
    param([string]$Name, [string]$Status, [string]$LogPath, [int]$ExitCode, [double]$DurationSeconds, [string]$Detail = '')
    return [ordered]@{
        name = $Name
        status = $Status
        exit_code = $ExitCode
        duration_seconds = $DurationSeconds
        log_path = $LogPath
        detail = $Detail
    }
}

function Get-Task010Sha256 {
    param([Parameter(Mandatory = $true)][string]$Path)
    return (Get-FileHash -Algorithm SHA256 -LiteralPath $Path).Hash.ToLowerInvariant()
}
