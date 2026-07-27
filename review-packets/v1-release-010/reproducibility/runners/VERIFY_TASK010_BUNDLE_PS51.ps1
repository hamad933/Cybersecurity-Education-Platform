param([string]$BundleRoot = $PSScriptRoot, [string]$RepoPath = '')

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$BundleRoot = (Resolve-Path -LiteralPath $BundleRoot).Path
. (Join-Path $BundleRoot 'SCRIPTS\TASK010.Common.ps1')

$checksum = Join-Path $BundleRoot 'BUNDLE_FILE_SHA256SUMS.tsv'
$sourceManifest = Join-Path $BundleRoot 'BUNDLE_MANIFEST.tsv'
if (-not (Test-Path -LiteralPath $checksum -PathType Leaf) -or -not (Test-Path -LiteralPath $sourceManifest -PathType Leaf)) {
    throw 'Bundle manifests are missing.'
}
$seen = @{}
$rows = Import-Csv -Delimiter "`t" -LiteralPath $checksum
foreach ($row in $rows) {
    $relative = [string]$row.relative_path
    $key = $relative.ToLowerInvariant()
    if ($relative -eq '' -or $relative -eq 'BUNDLE_FILE_SHA256SUMS.tsv' -or $relative -match '(^|/)(\.\.|\.)($|/)' -or $seen.ContainsKey($key)) {
        throw "Unsafe or duplicate bundle member: $relative"
    }
    $seen[$key] = $true
    $path = Join-Path $BundleRoot ($relative -replace '/', '\')
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Missing bundle member: $relative" }
    if ((Get-Item -LiteralPath $path).Length -ne [int64]$row.bytes) { throw "Bundle size mismatch: $relative" }
    if ((Get-Task010Sha256 -Path $path) -ne ([string]$row.sha256).ToLowerInvariant()) { throw "Bundle hash mismatch: $relative" }
}
$sourceRows = Import-Csv -Delimiter "`t" -LiteralPath $sourceManifest
foreach ($row in $sourceRows) {
    $path = Join-Path $BundleRoot ('FILES\' + (([string]$row.relative_path) -replace '/', '\'))
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Missing source patch file: $($row.relative_path)" }
    if ((Get-Task010Sha256 -Path $path) -ne ([string]$row.new_sha256).ToLowerInvariant()) { throw "Source patch hash mismatch: $($row.relative_path)" }
}
Write-Host 'TASK-010 bundle integrity: PASS'
Write-Host "Verified distributed files: $($rows.Count)"
Write-Host "Verified source patch files: $($sourceRows.Count)"
