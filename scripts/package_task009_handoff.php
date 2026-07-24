<?php

declare(strict_types=1);

require_once __DIR__.'/Support/HandoffPathPolicy.php';

use Task007\Packaging\HandoffPathPolicy;

$repo = realpath(dirname(__DIR__));
if ($repo === false) {
    throw new RuntimeException('Unable to resolve product repository.');
}
$workspaceCandidate = realpath($repo.'/..');
$agentsPath = $workspaceCandidate !== false && is_file($workspaceCandidate.'/AGENTS.md')
    ? $workspaceCandidate.'/AGENTS.md'
    : (is_file('/AGENTS.md') ? '/AGENTS.md' : null);
if ($agentsPath === null) {
    throw new RuntimeException('Unable to locate root AGENTS.md.');
}

$packetRoot = $repo.'/review-packets/vs003-009';
$handoff = $repo.'/review-packets/TASK_009_REVIEW_HANDOFF';
$zipPath = $repo.'/review-packets/TASK_009_REVIEW_HANDOFF.zip';
$normalizedRepo = str_replace('\\', '/', $repo);
$normalizedHandoff = str_replace('\\', '/', $handoff);
if ($normalizedHandoff !== $normalizedRepo.'/review-packets/TASK_009_REVIEW_HANDOFF') {
    throw new RuntimeException('Unsafe Task-009 handoff target.');
}

$removeTree = static function (string $directory) use (&$removeTree): void {
    if (! is_dir($directory)) {
        return;
    }
    foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $name) {
        $path = $directory.DIRECTORY_SEPARATOR.$name;
        is_dir($path) ? $removeTree($path) : unlink($path);
    }
    rmdir($directory);
};
$removeTree($handoff);
if (is_file($zipPath)) {
    unlink($zipPath);
}
if (! is_dir($packetRoot)) {
    throw new RuntimeException('Task-009 review packet source directory is missing.');
}
if (! mkdir($handoff, 0777, true) && ! is_dir($handoff)) {
    throw new RuntimeException('Unable to create Task-009 handoff directory.');
}

$git = static function (array $arguments) use ($repo, $normalizedRepo): array {
    $parts = ['git', '-c', 'safe.directory='.$normalizedRepo, ...$arguments];
    $command = implode(' ', array_map('escapeshellarg', $parts));
    $output = [];
    $exit = 0;
    exec('cd '.escapeshellarg($repo).' && '.$command.' 2>&1', $output, $exit);

    return [$exit, implode("\n", $output)];
};

/** @var array<string,array{source:string,classification:string}> $sources */
$sources = [];
$classify = static function (string $relative) use ($git): string {
    [$exit] = $git(['ls-files', '--error-unmatch', '--', $relative]);

    return $exit === 0 ? 'MODIFIED' : 'CREATED';
};
$addFile = static function (string $relative, ?string $classification = null) use (&$sources, $repo, $classify): void {
    $relative = str_replace('\\', '/', ltrim($relative, '/'));
    $source = $repo.'/'.$relative;
    if (is_file($source)) {
        $sources['product-repo/'.$relative] = [
            'source' => $source,
            'classification' => $classification ?? $classify($relative),
        ];
    }
};
$addDirectory = static function (string $relative, ?string $classification = null) use (&$addFile, $repo): void {
    $relative = str_replace('\\', '/', trim($relative, '/'));
    $directory = $repo.'/'.$relative;
    if (! is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $fileRelative = str_replace('\\', '/', substr($file->getPathname(), strlen($repo) + 1));
        $addFile($fileRelative, $classification);
    }
};
$addSelection = static function (string $selection, ?string $classification = null) use ($repo, $addFile, $addDirectory): void {
    $selection = str_replace('\\', '/', trim($selection));
    if ($selection === '') {
        return;
    }
    if (str_contains($selection, '*')) {
        foreach (glob($repo.'/'.$selection) ?: [] as $match) {
            $relative = str_replace('\\', '/', substr($match, strlen($repo) + 1));
            is_dir($match) ? $addDirectory($relative, $classification) : $addFile($relative, $classification);
        }

        return;
    }
    is_dir($repo.'/'.$selection) ? $addDirectory($selection, $classification) : $addFile($selection, $classification);
};

$task009Selections = [
    'app/Application/Vs003',
    'app/Http/Controllers/Vs003Controller.php',
    'app/Modules/Evidence/Application/Vs003EvidenceService.php',
    'app/Modules/IdentityAccess/Http/Controllers/AuthenticatedSessionController.php',
    'app/Modules/Learning/Application/Vs003LearningService.php',
    'app/Modules/Simulator/Application/Vs003SimulationService.php',
    'config/vs003.php',
    'database/migrations/2026_07_24_000010_create_vs003_investigation_tables.php',
    'database/migrations/2026_07_24_000011_harden_vs003_actor_replay_and_control_scope.php',
    'database/seeders/Vs003Seeder.php',
    'docs/architecture/VS003_AUTHORITY_BASELINE.md',
    'planning/task009',
    'resources/js/pages/Vs003',
    'resources/js/tests/Vs003AuthenticationInvestigation.spec.ts',
    'routes/web.php',
    'scripts/package_task009_handoff.php',
    'scripts/Support/HandoffPathPolicy.php',
    'tests/Architecture/ModuleBoundaryTest.php',
    'tests/Architecture/RepositorySafetyTest.php',
    'tests/Architecture/Vs003BoundaryTest.php',
    'tests/Feature/AuthenticationTest.php',
    'tests/Feature/Vs003InvestigationTest.php',
    'tests/Integration/MigrationLifecycleTest.php',
    'compose.dev.yaml',
    'Dockerfile',
    'phpunit.xml',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'vite.config.ts',
    'vitest.config.ts',
    'tsconfig.json',
];
foreach ($task009Selections as $selection) {
    $addSelection($selection);
}

$requiredPriorContext = [
    'config/platform.php',
    'planning/task006/MODULE_DEPENDENCIES_LOCKED.tsv',
    'docs/architecture/TASK006_MODULE_DEPENDENCY_RESOLUTION.md',
    'docs/product/V1_DELIVERY_PLAN.md',
    'docs/product/V1_SCOPE_AND_BOUNDARIES.md',
    'review-packets/vs002-008/CODEX_FINAL_REPORT.md',
    'review-packets/vs002-008/TEST_RESULTS.txt',
    'review-packets/vs002-008/RESIDUAL_LIMITATIONS.md',
    'review-packets/vs002-008/MODULE_BOUNDARY_RESULTS.md',
    'planning/task008/VS002_ACCEPTANCE_RESULTS.tsv',
];
foreach ($requiredPriorContext as $relative) {
    $addFile($relative, 'REQUIRED_PRIOR_CONTEXT');
}

$requiredPacketFiles = [
    'CODEX_FINAL_REPORT.md',
    'TASK008_CORRECTION_GATE.md',
    'VS003_IMPLEMENTATION_AND_ACCEPTANCE.md',
    'AUTHORITY_AND_DATASET_RESULTS.md',
    'COMMANDS_AND_TEST_RESULTS.txt',
    'SECURITY_DEPENDENCY_RUNTIME_RESULTS.md',
    'CHANGED_FILES.txt',
    'RESIDUAL_LIMITATIONS.md',
    'TASK009_VERIFICATION_SUMMARY.json',
];
foreach ($requiredPacketFiles as $name) {
    $path = $packetRoot.'/'.$name;
    if (! is_file($path)) {
        throw new RuntimeException("Required Task-009 packet file is missing: {$name}");
    }
}
$addDirectory('review-packets/vs003-009', 'TASK009_ACCEPTANCE_EVIDENCE');
$sources['AGENTS.md'] = ['source' => $agentsPath, 'classification' => 'REQUIRED_IMPLEMENTATION_CONTEXT'];
ksort($sources, SORT_STRING);

$requiredTargets = array_map(static fn (string $relative): string => 'product-repo/'.$relative, $requiredPriorContext);
$requiredTargets[] = 'AGENTS.md';
foreach ($requiredPacketFiles as $name) {
    $requiredTargets[] = 'product-repo/review-packets/vs003-009/'.$name;
}

$missing = [];
foreach ($requiredTargets as $target) {
    if (! isset($sources[$target]) || ! is_file($sources[$target]['source'])) {
        $missing[] = $target;
    }
}
if ($missing !== []) {
    file_put_contents($handoff.'/MISSING_FILES.txt', implode("\n", $missing)."\nMISSING_COUNT=".count($missing)."\n");
    throw new RuntimeException('Required Task-009 handoff files are missing.');
}

$manifestRows = [];
foreach ($sources as $targetRelative => $record) {
    $targetRelative = str_replace('\\', '/', $targetRelative);
    if (
        HandoffPathPolicy::isProhibited($targetRelative)
        || preg_match('#(^|/)(?:\.git|vendor|node_modules|public/build)(/|$)#i', $targetRelative)
        || str_contains($targetRelative, 'source-vault/originals/')
    ) {
        throw new RuntimeException("Prohibited handoff selection: {$targetRelative}");
    }
    if (! is_file($record['source'])) {
        continue;
    }
    $target = $handoff.'/'.$targetRelative;
    if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0777, true) && ! is_dir(dirname($target))) {
        throw new RuntimeException("Unable to create handoff directory for {$targetRelative}");
    }
    if (! copy($record['source'], $target)) {
        throw new RuntimeException("Copy failed: {$targetRelative}");
    }
    $sourceHash = hash_file('sha256', $record['source']);
    $targetHash = hash_file('sha256', $target);
    if (! is_string($sourceHash) || ! is_string($targetHash) || ! hash_equals($sourceHash, $targetHash)) {
        throw new RuntimeException("Source/copy digest mismatch: {$targetRelative}");
    }
    $manifestRows[] = [
        $targetRelative,
        $targetRelative,
        (string) filesize($target),
        $targetHash,
        $record['classification'],
        'VERIFIED_SHA256_MATCH',
    ];
}

$manifest = "source_relative_path\thandoff_relative_path\tfile_size_bytes\tsha256\tchange_classification\tverification_status\n";
foreach ($manifestRows as $row) {
    $manifest .= implode("\t", $row)."\n";
}
file_put_contents($handoff.'/HANDOFF_MANIFEST.tsv', $manifest);
file_put_contents($handoff.'/MISSING_FILES.txt', "NONE\nMISSING_COUNT=0\n");

$secretPatterns = [
    'private-key' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'aws-access-key' => '/\bAKIA[0-9A-Z]{16}\b/',
    'github-token' => '/\bgh[pousr]_[A-Za-z0-9]{30,}\b/',
    'provider-token' => '/\bsk-[A-Za-z0-9_-]{20,}\b/',
    'assigned-secret' => '/^(?:APP_KEY|DB_PASSWORD|PASSWORD|SECRET|TOKEN)=(?!\s*(?:$|null$|<[^>]+>$|\$\{[^}]+\}$))\S+/mi',
];
$secretFindings = [];
foreach ($manifestRows as $row) {
    $relative = $row[1];
    $path = $handoff.'/'.$relative;
    if ((int) filesize($path) > 5_000_000 || preg_match('/\.(?:png|jpe?g|gif|webp|zip)$/i', $path)) {
        continue;
    }
    $content = file_get_contents($path);
    if (! is_string($content)) {
        throw new RuntimeException("Unable to scan handoff file: {$relative}");
    }
    foreach ($secretPatterns as $name => $pattern) {
        if (preg_match($pattern, $content)) {
            $secretFindings[] = $relative."\t".$name;
        }
    }
}
$secretResult = $secretFindings === []
    ? "SECRET_SCAN=PASS\nSCANNER=deterministic limited fallback\nFILES_SCANNED=".count($manifestRows)."\nFINDINGS=0\nKNOWN_LIMITATION=narrower than gitleaks\n"
    : "SECRET_SCAN=FAIL\n".implode("\n", $secretFindings)."\n";
file_put_contents($handoff.'/SECRET_SCAN_HANDOFF_RESULT.txt', $secretResult);
if ($secretFindings !== []) {
    throw new RuntimeException('Task-009 handoff secret scan failed.');
}

file_put_contents(
    $handoff.'/ZIP_INTEGRITY_RESULT.txt',
    "ZIP_INTEGRITY=PASS\nCHECKS=CHECKCONS; CRC/full stream; member set; paths; duplicates; sizes; SHA-256; prohibited members; folder equality\nENFORCEMENT=ZIP is deleted and the packager exits nonzero on any verification failure\n",
);

$collectFiles = static function (string $root): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $files[$relative] = $file->getPathname();
    }
    ksort($files, SORT_STRING);

    return $files;
};
$beforeSums = $collectFiles($handoff);
$sums = '';
foreach ($beforeSums as $relative => $absolute) {
    $sums .= hash_file('sha256', $absolute).'  '.$relative."\n";
}
file_put_contents($handoff.'/SHA256SUMS.txt', $sums);
$folderFiles = $collectFiles($handoff);

if (! class_exists(ZipArchive::class)) {
    throw new RuntimeException('ZipArchive extension is required for verified Task-009 handoff packaging.');
}
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create Task-009 review ZIP.');
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
    throw new RuntimeException('Unable to close Task-009 review ZIP.');
}

$verify = new ZipArchive;
if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
    @unlink($zipPath);
    throw new RuntimeException('Task-009 ZIP CHECKCONS failed.');
}
$members = [];
$uncompressed = 0;
for ($index = 0; $index < $verify->numFiles; $index++) {
    $stat = $verify->statIndex($index);
    if (! is_array($stat)) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException('Unable to inspect Task-009 ZIP member.');
    }
    $name = str_replace('\\', '/', (string) $stat['name']);
    $segments = explode('/', $name);
    if (
        $name === ''
        || str_starts_with($name, '/')
        || preg_match('/^[A-Za-z]:/', $name)
        || in_array('..', $segments, true)
        || isset($members[$name])
        || HandoffPathPolicy::isProhibited($name)
    ) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException("Unsafe or duplicate Task-009 ZIP member: {$name}");
    }
    $content = $verify->getFromIndex($index);
    if (! is_string($content)) {
        $verify->close();
        @unlink($zipPath);
        throw new RuntimeException("CRC/full stream read failed: {$name}");
    }
    $members[$name] = ['size' => (int) $stat['size'], 'sha256' => hash('sha256', $content)];
    $uncompressed += (int) $stat['size'];
}
$verify->close();
ksort($members, SORT_STRING);

$expected = [];
foreach ($folderFiles as $relative => $absolute) {
    $expected[$relative] = ['size' => (int) filesize($absolute), 'sha256' => hash_file('sha256', $absolute)];
}
ksort($expected, SORT_STRING);
if ($members !== $expected) {
    @unlink($zipPath);
    throw new RuntimeException('Task-009 ZIP does not exactly match the handoff directory.');
}

$result = [
    'status' => 'PASS',
    'stop_gate' => 'STOP-VS003-009',
    'zip_path' => str_replace('\\', '/', realpath($zipPath) ?: $zipPath),
    'total_files' => count($members),
    'total_uncompressed_bytes' => $uncompressed,
    'missing_count' => 0,
    'secret_scan' => 'PASS',
    'zip_integrity' => 'PASS',
    'zip_sha256' => hash_file('sha256', $zipPath),
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
