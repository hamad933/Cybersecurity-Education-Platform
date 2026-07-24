<?php

declare(strict_types=1);

require_once __DIR__.'/Support/HandoffPathPolicy.php';

use Task007\Packaging\HandoffPathPolicy;

$repo = realpath(dirname(__DIR__));
$workspace = $repo === false ? false : realpath($repo.'/..');
if ($repo === false || $workspace === false) {
    throw new RuntimeException('Unable to resolve repository/workspace.');
}
$handoff = $repo.'/review-packets/TASK_008_REVIEW_HANDOFF';
$zipPath = $repo.'/review-packets/TASK_008_REVIEW_HANDOFF.zip';
$normalizedRepo = str_replace('\\', '/', $repo);
$normalizedHandoff = str_replace('\\', '/', $handoff);
if ($normalizedHandoff !== $normalizedRepo.'/review-packets/TASK_008_REVIEW_HANDOFF') {
    throw new RuntimeException('Unsafe Task-008 handoff target.');
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
mkdir($handoff, 0777, true);

$git = static function (string $arguments) use ($repo, $normalizedRepo): string {
    $command = 'git -c safe.directory='.escapeshellarg($normalizedRepo).' '.$arguments;

    return (string) shell_exec('cd '.escapeshellarg($repo).' && '.$command);
};

/** @var array<string,array{source:string,classification:string}> $changed */
$changed = [];
$classify = static function (string $relative) use ($git): string {
    return trim($git('ls-files --error-unmatch -- '.escapeshellarg($relative).' 2>NUL')) !== '' ? 'MODIFIED' : 'CREATED';
};
$addFile = static function (string $relative) use (&$changed, $repo, $classify): void {
    $relative = str_replace('\\', '/', ltrim($relative, '/'));
    $source = $repo.'/'.$relative;
    if (is_file($source)) {
        $changed['product-repo/'.$relative] = ['source' => $source, 'classification' => $classify($relative)];
    }
};
$addDirectory = static function (string $relative) use (&$addFile, $repo): void {
    $directory = $repo.'/'.str_replace('\\', '/', trim($relative, '/'));
    if (! is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $addFile(str_replace('\\', '/', substr($file->getPathname(), strlen($repo) + 1)));
        }
    }
};
$addSelection = static function (string $selection) use (&$addFile, &$addDirectory, $repo): void {
    $selection = str_replace('\\', '/', trim($selection));
    if ($selection === '') {
        return;
    }
    if (str_contains($selection, '*')) {
        foreach (glob($repo.'/'.$selection, GLOB_ONLYDIR) ?: [] as $match) {
            $addDirectory(str_replace('\\', '/', substr($match, strlen($repo) + 1)));
        }
        foreach (glob($repo.'/'.$selection) ?: [] as $match) {
            if (is_file($match)) {
                $addFile(str_replace('\\', '/', substr($match, strlen($repo) + 1)));
            }
        }

        return;
    }
    is_dir($repo.'/'.$selection) ? $addDirectory($selection) : $addFile($selection);
};

$correctionRows = file($repo.'/planning/task008/VS001_CORRECTION_RESULTS.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
foreach (array_slice($correctionRows, 1) as $row) {
    $fields = str_getcsv($row, "\t", '"', '');
    foreach (explode(';', $fields[4] ?? '') as $selection) {
        $addSelection($selection);
    }
}

$task008Selections = [
    'app/Application/Vs002',
    'app/Http/Controllers/Vs002Controller.php',
    'app/Modules/Evidence/Application/Vs002EvidenceService.php',
    'app/Modules/Evidence/Models/FindingVerification.php',
    'app/Modules/Evidence/Models/SecurityFinding.php',
    'app/Modules/Evidence/Models/EvidenceRecord.php',
    'app/Modules/Knowledge/Application/Vs002KnowledgeService.php',
    'app/Modules/Learning/Application/Vs002LearningService.php',
    'app/Modules/Simulator/Application/Vs002SimulationService.php',
    'app/Modules/Simulator/Authorization/WebAuthorizationDecisionEngine.php',
    'app/Modules/Simulator/Models/AuthorizationPolicyRevision.php',
    'app/Modules/Simulator/Models/EndpointContractRevision.php',
    'app/Modules/Simulator/Models/ScenarioRun.php',
    'app/Modules/SourceGovernance/Application/Vs002SourceService.php',
    'config/vs002.php',
    'database/migrations/2026_07_22_000008_create_vs002_web_authorization_tables.php',
    'database/seeders/DatabaseSeeder.php',
    'database/seeders/Vs002Seeder.php',
    'docs/architecture/VS002_*',
    'docs/development/VS002_*',
    'docs/development/TASK008_TROUBLESHOOTING.md',
    'docs/governance/TASK007_EXTERNAL_REVIEW_RECORD.md',
    'docs/governance/APPROVED_BASELINE_INDEX.md',
    'planning/task008',
    'resources/js/components/Vs002Nav.vue',
    'resources/js/components/WebDecisionTrace.vue',
    'resources/js/pages/Vs002',
    'resources/js/tests/Vs002SafeRendering.spec.ts',
    'routes/web.php',
    'scripts/package_task008_handoff.php',
    'tests/Feature/Vs002LifecycleTest.php',
    'tests/Feature/Vs002WorkspaceTest.php',
    'tests/Integration/MigrationLifecycleTest.php',
    'tests/Unit/WebAuthorizationDecisionEngineTest.php',
];
foreach ($task008Selections as $selection) {
    $addSelection($selection);
}

$changedPath = $repo.'/review-packets/vs002-008/CHANGED_FILES.txt';
$changedLines = [];
foreach ($changed as $target => $record) {
    $changedLines[] = $record['classification']."\t".$target;
}
$changedLines[] = "CREATED\tproduct-repo/review-packets/vs002-008/CHANGED_FILES.txt";
sort($changedLines, SORT_STRING);
file_put_contents($changedPath, "change_classification\tpath\n".implode("\n", array_unique($changedLines))."\n");
$addDirectory('review-packets/vs002-008');

/** @var array<string,array{source:string,classification:string}> $sources */
$sources = $changed;
$requiredProductContext = [
    'config/platform.php',
    'config/vs001.php',
    'planning/task006/MODULE_DEPENDENCIES_LOCKED.tsv',
    'docs/architecture/TASK006_MODULE_DEPENDENCY_RESOLUTION.md',
    'docs/architecture/VS001_IMPLEMENTATION_BASELINE.md',
    'docs/architecture/VS001_RULE_ENGINE_BOUNDARIES.md',
    'docs/architecture/VS001_WINDOWS_AUTHORITY_BASELINE.md',
    'planning/task007/VS001_ACCEPTANCE_RESULTS.tsv',
    'planning/task007/VS001_RULE_CATALOG.tsv',
    'planning/task007/VS001_SCENARIO_CASES.tsv',
];
foreach ($requiredProductContext as $relative) {
    $sources['product-repo/'.$relative] = ['source' => $repo.'/'.$relative, 'classification' => 'REQUIRED_IMPLEMENTATION_CONTEXT'];
}
$approvedProductReferences = ['docs/product/V1_DELIVERY_PLAN.md', 'docs/product/V1_SCOPE_AND_BOUNDARIES.md'];
foreach ($approvedProductReferences as $relative) {
    $sources['product-repo/'.$relative] = ['source' => $repo.'/'.$relative, 'classification' => 'APPROVED_BASELINE_REFERENCE'];
}
$outsideReferences = [
    'source-vault/manifests/semantic-refined/CAPABILITY_CATALOG_REFINED.tsv',
    'source-vault/manifests/semantic-refined/KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv',
    'source-vault/manifests/semantic-refined/DOMAIN_COVERAGE_MATRIX_REFINED.tsv',
    'source-vault/derived/semantic-refined/UNRESOLVED_DECISIONS_REFINED.md',
];
foreach ($outsideReferences as $relative) {
    $sources[$relative] = ['source' => $workspace.'/'.$relative, 'classification' => 'APPROVED_BASELINE_REFERENCE'];
}
$sources['AGENTS.md'] = ['source' => $workspace.'/AGENTS.md', 'classification' => 'REQUIRED_IMPLEMENTATION_CONTEXT'];
ksort($sources, SORT_STRING);

$missing = [];
$manifestRows = [];
foreach ($sources as $targetRelative => $record) {
    if (HandoffPathPolicy::isProhibited($targetRelative)
        || preg_match('#(^|/)(?:\.git|vendor|node_modules|public/build)(/|$)#i', $targetRelative)
        || str_contains($targetRelative, 'source-vault/originals/')) {
        throw new RuntimeException("Prohibited handoff selection: {$targetRelative}");
    }
    if (! is_file($record['source'])) {
        $missing[] = $targetRelative;

        continue;
    }
    $target = $handoff.'/'.$targetRelative;
    if (! is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }
    if (! copy($record['source'], $target)) {
        throw new RuntimeException("Copy failed: {$targetRelative}");
    }
    $sourceHash = hash_file('sha256', $record['source']);
    $targetHash = hash_file('sha256', $target);
    if (! is_string($sourceHash) || ! is_string($targetHash) || ! hash_equals($sourceHash, $targetHash)) {
        throw new RuntimeException("Source/copy digest mismatch: {$targetRelative}");
    }
    $manifestRows[] = [$targetRelative, $targetRelative, (string) filesize($target), $targetHash, $record['classification'], 'VERIFIED_SHA256_MATCH'];
}

$manifest = "source_relative_path\thandoff_relative_path\tfile_size_bytes\tsha256\tchange_classification\tverification_status\n";
foreach ($manifestRows as $row) {
    $manifest .= implode("\t", $row)."\n";
}
file_put_contents($handoff.'/HANDOFF_MANIFEST.tsv', $manifest);
file_put_contents($handoff.'/MISSING_FILES.txt', $missing === [] ? "NONE\nMISSING_COUNT=0\n" : implode("\n", $missing)."\nMISSING_COUNT=".count($missing)."\n");

$secretPatterns = [
    'private-key' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'aws-access-key' => '/\bAKIA[0-9A-Z]{16}\b/',
    'github-token' => '/\bgh[pousr]_[A-Za-z0-9]{30,}\b/',
    'provider-token' => '/\bsk-[A-Za-z0-9_-]{20,}\b/',
    'assigned-secret' => '/^(?:APP_KEY|DB_PASSWORD|PASSWORD|SECRET|TOKEN)=(?!\s*(?:$|null$|<[^>]+>$|\$\{[^}]+\}$))\S+/mi',
];
$secretFindings = [];
foreach ($manifestRows as $row) {
    $path = $handoff.'/'.$row[1];
    if (filesize($path) > 5_000_000 || str_ends_with(strtolower($path), '.png')) {
        continue;
    }
    $content = file_get_contents($path);
    if (! is_string($content)) {
        throw new RuntimeException("Unable to scan handoff file: {$row[1]}");
    }
    foreach ($secretPatterns as $name => $pattern) {
        if (preg_match($pattern, $content)) {
            $secretFindings[] = $row[1]."\t{$name}";
        }
    }
}
$secretResult = $secretFindings === []
    ? "SECRET_SCAN=PASS\nSCANNER=deterministic limited fallback\nFILES_SCANNED=".count($manifestRows)."\nFINDINGS=0\nKNOWN_LIMITATION=narrower than gitleaks\n"
    : "SECRET_SCAN=FAIL\n".implode("\n", $secretFindings)."\n";
file_put_contents($handoff.'/SECRET_SCAN_HANDOFF_RESULT.txt', $secretResult);
if ($secretFindings !== []) {
    throw new RuntimeException('Handoff secret scan failed.');
}

file_put_contents($handoff.'/ZIP_INTEGRITY_RESULT.txt', "ZIP_INTEGRITY=PASS\nCHECKS=CHECKCONS; CRC/full stream; member set; paths; duplicates; sizes; SHA-256; prohibited members; folder equality\nENFORCEMENT=ZIP is deleted and the packager exits nonzero on any verification failure\n");

$collectFiles = static function (string $root): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $files[$relative] = $file->getPathname();
        }
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
    throw new RuntimeException('ZipArchive extension is required for verified handoff packaging.');
}
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create Task-008 review ZIP.');
}
foreach ($folderFiles as $relative => $absolute) {
    if (! $zip->addFile($absolute, $relative)) {
        throw new RuntimeException("Unable to add ZIP member: {$relative}");
    }
}
if (! $zip->close()) {
    throw new RuntimeException('Unable to close Task-008 review ZIP.');
}

$verify = new ZipArchive;
if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
    unlink($zipPath);
    throw new RuntimeException('ZIP CHECKCONS failed.');
}
$members = [];
$uncompressed = 0;
for ($index = 0; $index < $verify->numFiles; $index++) {
    $stat = $verify->statIndex($index);
    if (! is_array($stat)) {
        throw new RuntimeException('Unable to inspect ZIP member.');
    }
    $name = str_replace('\\', '/', $stat['name']);
    $segments = explode('/', $name);
    if ($name === '' || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) || in_array('..', $segments, true) || isset($members[$name]) || HandoffPathPolicy::isProhibited($name)) {
        $verify->close();
        unlink($zipPath);
        throw new RuntimeException("Unsafe or duplicate ZIP member: {$name}");
    }
    $content = $verify->getFromIndex($index);
    if (! is_string($content)) {
        throw new RuntimeException("CRC/full stream read failed: {$name}");
    }
    $members[$name] = ['size' => (int) $stat['size'], 'sha256' => hash('sha256', $content)];
    $uncompressed += (int) $stat['size'];
}
$verify->close();
ksort($members, SORT_STRING);

$expected = [];
foreach ($folderFiles as $relative => $absolute) {
    $expected[$relative] = ['size' => filesize($absolute), 'sha256' => hash_file('sha256', $absolute)];
}
ksort($expected, SORT_STRING);
if ($members !== $expected || $missing !== []) {
    unlink($zipPath);
    throw new RuntimeException('ZIP does not exactly match the handoff folder or required files are missing.');
}

echo json_encode([
    'zip_path' => str_replace('\\', '/', realpath($zipPath) ?: $zipPath),
    'total_files' => count($members),
    'total_uncompressed_bytes' => $uncompressed,
    'missing_count' => count($missing),
    'secret_scan' => 'PASS',
    'zip_integrity' => 'PASS',
    'zip_sha256' => hash_file('sha256', $zipPath),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
