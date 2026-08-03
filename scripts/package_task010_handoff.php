<?php

declare(strict_types=1);

$repo = realpath(dirname(__DIR__));
if ($repo === false) {
    throw new RuntimeException('Unable to resolve product repository.');
}
$packet = $repo.'/review-packets/v1-release-010';
$handoff = $repo.'/review-packets/TASK_010_REVIEW_HANDOFF';
$zipPath = $repo.'/review-packets/TASK_010_REVIEW_HANDOFF.zip';
$agents = is_file(dirname($repo).'/AGENTS.md') ? dirname($repo).'/AGENTS.md' : (is_file('/AGENTS.md') ? '/AGENTS.md' : null);
if ($agents === null) {
    throw new RuntimeException('Root AGENTS.md is required for the handoff.');
}
if (! is_dir($packet)) {
    throw new RuntimeException('Task-010 review packet is missing.');
}

$evidenceTables = [
    ['path' => $repo.'/planning/task010/TASK010_TRACEABILITY.tsv', 'name' => 'TASK010_TRACEABILITY.tsv', 'column' => 3],
    ['path' => $repo.'/planning/task010/TASK010_ACCEPTANCE_RESULTS.tsv', 'name' => 'TASK010_ACCEPTANCE_RESULTS.tsv', 'column' => 2],
];
$checkedEvidence = [];
$evidenceErrors = [];
foreach ($evidenceTables as $table) {
    if (! is_file($table['path'])) {
        $evidenceErrors[] = $table['name'].':missing_table';

        continue;
    }

    $lines = file($table['path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (! is_array($lines) || count($lines) < 2) {
        $evidenceErrors[] = $table['name'].':empty_table';

        continue;
    }

    foreach (array_slice($lines, 1) as $lineNumber => $line) {
        $columns = str_getcsv($line, "\t");
        if (! isset($columns[$table['column']])) {
            $evidenceErrors[] = $table['name'].':line_'.($lineNumber + 2).':missing_evidence_column';

            continue;
        }

        foreach (array_filter(array_map('trim', explode(';', (string) $columns[$table['column']]))) as $reference) {
            $normalized = str_replace('\\', '/', $reference);
            if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized) || in_array('..', explode('/', $normalized), true)) {
                $evidenceErrors[] = $table['name'].':line_'.($lineNumber + 2).':unsafe_path:'.$normalized;

                continue;
            }

            $absolute = $repo.'/'.$normalized;
            if (! is_file($absolute)) {
                $evidenceErrors[] = $table['name'].':line_'.($lineNumber + 2).':missing:'.$normalized;

                continue;
            }

            $checkedEvidence[$normalized] = true;
        }
    }
}

$evidenceValidation = [
    'status' => $evidenceErrors === [] ? 'PASS' : 'FAIL',
    'tables' => array_column($evidenceTables, 'name'),
    'checked_reference_count' => count($checkedEvidence),
    'errors' => $evidenceErrors,
];
file_put_contents(
    $packet.'/EVIDENCE_PATH_VALIDATION_RESULT.json',
    json_encode($evidenceValidation, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
);
if ($evidenceErrors !== []) {
    throw new RuntimeException('Task-010 evidence-path validation failed: '.implode('; ', $evidenceErrors));
}

$requiredEvidence = [
    'TASK010_FINAL_SUMMARY.json',
    'TARGETED_GATE_SUMMARY.json',
    'FULL_RELEASE_GATE_SUMMARY.json',
    'RESTORE_DRILL_RESULT.json',
    'BROWSER_RESULT.json',
    'COMMANDS_AND_TEST_RESULTS.txt',
    'SECURITY_DEPENDENCY_RESULTS.md',
    'RELEASE_AND_ROLLBACK_RESULTS.md',
    'RESIDUAL_LIMITATIONS.md',
    'BUNDLE_FILE_SHA256SUMS.tsv',
    'BUNDLE_MANIFEST.tsv',
    'BUNDLE_BUILD_REPORT.json',
    'EVIDENCE_PATH_VALIDATION_RESULT.json',
    'reproducibility/REVISION_REPRODUCIBILITY_MANIFEST.json',
    'reproducibility/runners/RUN_TASK010_FULL_RELEASE_GATE_PS51.ps1',
    'reproducibility/runners/BUILD_TASK010_HANDOFF_PS51.ps1',
    'reproducibility/runners/TASK010.Common.ps1',
    'reproducibility/runners/RESUME_TASK010_AFTER_DOCKER_PS51.ps1',
];
foreach ($requiredEvidence as $name) {
    if (! is_file($packet.'/'.$name)) {
        throw new RuntimeException("Required Task-010 evidence is missing: {$name}");
    }
}
$summary = json_decode((string) file_get_contents($packet.'/TASK010_FINAL_SUMMARY.json'), true, 64, JSON_THROW_ON_ERROR);
if (($summary['stop_gate'] ?? null) !== 'STOP-V1-RELEASE-010') {
    throw new RuntimeException('Task-010 final summary does not contain the exact stop gate.');
}

$remove = static function (string $path) use (&$remove): void {
    if (! is_dir($path)) {
        return;
    }
    foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
        $child = $path.DIRECTORY_SEPARATOR.$entry;
        is_dir($child) ? $remove($child) : unlink($child);
    }
    rmdir($path);
};
$remove($handoff);
@unlink($zipPath);
if (! mkdir($handoff, 0777, true) && ! is_dir($handoff)) {
    throw new RuntimeException('Unable to create Task-010 handoff directory.');
}

/** @var array<string,string> $files */
$files = ['AGENTS.md' => $agents];
$addFile = static function (string $relative, string $targetPrefix = 'product-repo') use (&$files, $repo): void {
    $relative = str_replace('\\', '/', trim($relative, '/'));
    $source = $repo.'/'.$relative;
    if (is_file($source)) {
        $files[$targetPrefix.'/'.$relative] = $source;
    }
};
$addDir = static function (string $relative, string $targetPrefix = 'product-repo') use (&$files, $repo): void {
    $relative = str_replace('\\', '/', trim($relative, '/'));
    $root = $repo.'/'.$relative;
    if (! is_dir($root)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $child = str_replace('\\', '/', substr($file->getPathname(), strlen($repo) + 1));
        $files[$targetPrefix.'/'.$child] = $file->getPathname();
    }
};

foreach ([
    '.env.release.example', '.gitignore', 'Dockerfile', 'compose.yaml', 'compose.dev.yaml', 'compose.release.yaml',
    'package.json', 'package-lock.json', 'composer.json', 'composer.lock', 'phpstan.neon', 'phpunit.xml',
    'eslint.config.js', 'tsconfig.json', 'vite.config.ts', 'vitest.config.ts',
    'bootstrap/app.php', 'bootstrap/providers.php', 'config/platform.php', 'config/queue.php', 'routes/web.php', 'routes/console.php',
    'database/migrations/2026_07_25_000012_create_v1_integration_release_tables.php',
    'database/seeders/DatabaseSeeder.php', 'database/seeders/Task010Seeder.php',
    'app/Providers/AppServiceProvider.php', 'app/Http/Controllers/ReleaseController.php', 'app/Http/Middleware/SecurityHeaders.php',
    'app/Modules/Knowledge/Publication/LessonRevisionWorkflow.php',
    'resources/js/pages/Dashboard.vue', 'resources/js/components/StateLabel.vue', 'resources/js/pages/Release/Center.vue',
    'resources/js/tests/Task010ReleaseCenter.spec.ts',
    'scripts/package_task010_handoff.php', 'scripts/validate_release_compose.php', 'scripts/secret_scan.php',
    'scripts/capture_task010_browser_evidence.mjs',
    'tests/Architecture/ModuleBoundaryTest.php', 'tests/Architecture/Task010BoundaryTest.php',
    'tests/Unit/PackagePathGuardTest.php', 'tests/Integration/Task010ImportSecurityTest.php',
    'tests/Integration/AuditIntegrityTest.php', 'tests/Integration/PortablePackageSecurityTest.php',
    'tests/Integration/ManualAiBridgeTest.php', 'tests/Integration/SearchQueueTest.php',
    'tests/Integration/BackupRestoreTest.php', 'tests/Feature/Task010ReleaseCenterTest.php',
    'app/Modules/IdentityAccess/Console/PrepareReleaseGateOwnerCommand.php',
    'app/Modules/IdentityAccess/Console/PrepareRestoreDrillCommand.php',
    'app/Modules/IdentityAccess/Providers/IdentityAccessServiceProvider.php',
    'app/Modules/Enterprise/Application/EnterpriseBaselineService.php',
    'app/Modules/Simulator/Application/Vs003SimulationService.php',
] as $relative) {
    $addFile($relative);
}
foreach ([
    'app/Modules/ManualAiBridge',
    'app/Modules/Platform/Audit',
    'app/Modules/Platform/Backup',
    'app/Modules/Platform/Blobs',
    'app/Modules/Platform/Console',
    'app/Modules/Platform/Packages',
    'app/Modules/Platform/Processing',
    'app/Modules/Platform/Providers',
    'app/Modules/Platform/Queue',
    'app/Modules/Platform/Release',
    'app/Modules/Platform/Search',
    'app/Modules/Platform/Support',
    'app/Modules/SourceGovernance/Application',
    'app/Modules/SourceGovernance/Models',
    'app/Modules/Evidence/Application',
    'app/Modules/Evidence/Models',
    'app/Modules/Learning/Application',
    'planning/task010',
    'docs/architecture',
    'docs/development',
    'docs/governance',
    'review-packets/v1-release-010',
] as $relative) {
    $addDir($relative);
}
foreach ([
    'review-packets/vs003-009/CODEX_FINAL_REPORT.md',
    'review-packets/vs003-009/TASK009_VERIFICATION_SUMMARY.json',
    'review-packets/vs003-009/RESIDUAL_LIMITATIONS.md',
    'review-packets/TASK009_HANDOFF_BUILD_RESULT.json',
] as $relative) {
    $addFile($relative);
}
ksort($files, SORT_STRING);

$prohibited = static function (string $relative): bool {
    $isEnvironmentFile = preg_match('/(^|\/)\.env(?:\.|$)/i', $relative) === 1
        && preg_match('/\.example$/i', $relative) !== 1;

    return preg_match('#(^|/)(?:\.git|vendor|node_modules|public/build|storage/framework|storage/logs)(/|$)#i', $relative) === 1
        || $isEnvironmentFile
        || preg_match('/\.(?:key|pem|p12|pfx)$/i', $relative) === 1;
};
$manifestRows = [];
foreach ($files as $relative => $source) {
    $relative = str_replace('\\', '/', $relative);
    if ($prohibited($relative)) {
        throw new RuntimeException("Prohibited handoff member selected: {$relative}");
    }
    $target = $handoff.'/'.$relative;
    if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0777, true) && ! is_dir(dirname($target))) {
        throw new RuntimeException("Unable to create handoff path: {$relative}");
    }
    if (! copy($source, $target)) {
        throw new RuntimeException("Unable to copy handoff member: {$relative}");
    }
    $sourceHash = hash_file('sha256', $source);
    $targetHash = hash_file('sha256', $target);
    if (! is_string($sourceHash) || ! is_string($targetHash) || ! hash_equals($sourceHash, $targetHash)) {
        throw new RuntimeException("Handoff digest mismatch: {$relative}");
    }
    $manifestRows[] = [$relative, (string) filesize($target), $targetHash];
}
$manifest = "relative_path\tbytes\tsha256\n";
foreach ($manifestRows as $row) {
    $manifest .= implode("\t", $row)."\n";
}
file_put_contents($handoff.'/HANDOFF_MANIFEST.tsv', $manifest);

$patterns = [
    '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    '/\bAKIA[0-9A-Z]{16}\b/',
    '/\bgh[pousr]_[A-Za-z0-9]{30,}\b/',
    '/\bsk-[A-Za-z0-9_-]{20,}\b/',
    '/^(?:APP_KEY|DB_PASSWORD|PASSWORD|SECRET|TOKEN)=(?!\s*(?:$|null$|<[^>]+>$|\$\{[^}]+\}$))\S+/mi',
];
$findings = [];
foreach ($manifestRows as [$relative]) {
    $path = $handoff.'/'.$relative;
    if (filesize($path) > 5_000_000 || preg_match('/\.(?:png|jpe?g|gif|webp|zip)$/i', $path)) {
        continue;
    }
    $content = file_get_contents($path);
    foreach ($patterns as $pattern) {
        if (is_string($content) && preg_match($pattern, $content)) {
            $findings[] = $relative;
            break;
        }
    }
}
file_put_contents($handoff.'/SECRET_SCAN_HANDOFF_RESULT.txt', $findings === [] ? "SECRET_SCAN=PASS\nFINDINGS=0\n" : "SECRET_SCAN=FAIL\n".implode("\n", $findings)."\n");
if ($findings !== []) {
    throw new RuntimeException('Handoff secret scan failed.');
}

$collect = static function (string $root): array {
    $result = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $result[$relative] = $file->getPathname();
        }
    }
    ksort($result, SORT_STRING);

    return $result;
};
file_put_contents(
    $handoff.'/ZIP_INTEGRITY_RESULT.txt',
    "ZIP_INTEGRITY=PASS\nCHECKS=CHECKCONS; CRC/full stream; member set; paths; duplicates; sizes; SHA-256; prohibited members; folder equality\nENFORCEMENT=ZIP is deleted and the packager exits nonzero on any verification failure\n",
);

$beforeSums = $collect($handoff);
$sums = '';
foreach ($beforeSums as $relative => $absolute) {
    $sums .= hash_file('sha256', $absolute).'  '.$relative."\n";
}
file_put_contents($handoff.'/SHA256SUMS.txt', $sums);
$folderFiles = $collect($handoff);

if (! class_exists(ZipArchive::class)) {
    throw new RuntimeException('ZipArchive is required for Task-010 handoff packaging.');
}
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create Task-010 handoff ZIP.');
}
foreach ($folderFiles as $relative => $absolute) {
    if (! $zip->addFile($absolute, $relative)) {
        $zip->close();
        @unlink($zipPath);
        throw new RuntimeException("Unable to add ZIP member: {$relative}");
    }
}
if (! $zip->close()) {
    @unlink($zipPath);
    throw new RuntimeException('Unable to close Task-010 handoff ZIP.');
}

$verify = new ZipArchive;
if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
    @unlink($zipPath);
    throw new RuntimeException('Task-010 handoff ZIP CHECKCONS failed.');
}
$members = [];
for ($index = 0; $index < $verify->numFiles; $index++) {
    $stat = $verify->statIndex($index);
    if (! is_array($stat)) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException('Unreadable ZIP member.');
    }
    $name = str_replace('\\', '/', (string) $stat['name']);
    if ($name === '' || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) || in_array('..', explode('/', $name), true) || isset($members[mb_strtolower($name)]) || $prohibited($name)) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException("Unsafe or duplicate ZIP member: {$name}");
    }
    $bytes = $verify->getFromIndex($index);
    if (! is_string($bytes) || strlen($bytes) !== (int) $stat['size']) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException("ZIP CRC/size verification failed: {$name}");
    }
    $members[mb_strtolower($name)] = ['name' => $name, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
}
$verify->close();
if (count($members) !== count($folderFiles)) {
    @unlink($zipPath);
    throw new RuntimeException('Task-010 handoff ZIP member set differs from folder.');
}
foreach ($folderFiles as $relative => $absolute) {
    $entry = $members[mb_strtolower($relative)] ?? null;
    if ($entry === null || $entry['name'] !== $relative || $entry['bytes'] !== filesize($absolute) || ! hash_equals($entry['sha256'], hash_file('sha256', $absolute))) {
        @unlink($zipPath);
        throw new RuntimeException("Task-010 handoff ZIP member mismatch: {$relative}");
    }
}

$result = [
    'status' => 'PASS',
    'stop_gate' => 'STOP-V1-RELEASE-010',
    'zip_path' => $zipPath,
    'total_files' => count($members),
    'zip_bytes' => filesize($zipPath),
    'zip_sha256' => hash_file('sha256', $zipPath),
];
file_put_contents($repo.'/review-packets/TASK010_HANDOFF_BUILD_RESULT.json', json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
