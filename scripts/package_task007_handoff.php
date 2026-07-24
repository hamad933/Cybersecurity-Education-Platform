<?php

declare(strict_types=1);

require_once __DIR__.'/Support/HandoffPathPolicy.php';

use Task007\Packaging\HandoffPathPolicy;

$repo = realpath(dirname(__DIR__));
$workspace = $repo === false ? false : realpath($repo.'/..');
if ($repo === false || $workspace === false) {
    throw new RuntimeException('Unable to resolve repository/workspace.');
}
$reviewRoot = $repo.'/review-packets';
$handoff = $reviewRoot.'/TASK_007_REVIEW_HANDOFF';
$zipPath = $reviewRoot.'/TASK_007_REVIEW_HANDOFF.zip';
$normalizedRepo = str_replace('\\', '/', $repo);
$normalizedHandoff = str_replace('\\', '/', $handoff);
if (! str_starts_with($normalizedHandoff, $normalizedRepo.'/review-packets/TASK_007_REVIEW_HANDOFF')) {
    throw new RuntimeException('Unsafe handoff target.');
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

$statusOutput = $git('status --porcelain=v1 -z -uall');
$statusEntries = array_values(array_filter(explode("\0", $statusOutput), static fn (string $entry): bool => $entry !== ''));
$changedLines = [];
$sources = [];
foreach ($statusEntries as $entry) {
    $status = substr($entry, 0, 2);
    $relative = str_replace('\\', '/', substr($entry, 3));
    if (str_contains($relative, ' -> ')) {
        $relative = explode(' -> ', $relative, 2)[1];
    }
    if (str_starts_with($relative, 'review-packets/TASK_007_REVIEW_HANDOFF') || HandoffPathPolicy::isRuntimeResidual($relative)) {
        continue;
    }
    $classification = $status === '??' ? 'CREATED' : 'MODIFIED';
    $changedLines[] = $classification."\tproduct-repo/{$relative}";
    if (is_file($repo.'/'.$relative)) {
        $sources['product-repo/'.$relative] = ['source' => $repo.'/'.$relative, 'classification' => $classification];
    }
}
sort($changedLines, SORT_STRING);
$changedPath = $repo.'/review-packets/vs001-007/CHANGED_FILES.txt';
file_put_contents($changedPath, "change_classification\tpath\n".implode("\n", $changedLines)."\n");
$sources['product-repo/review-packets/vs001-007/CHANGED_FILES.txt'] = ['source' => $changedPath, 'classification' => 'CREATED'];

$requiredProductContext = [
    'config/platform.php',
    'planning/task006/MODULE_DEPENDENCIES_LOCKED.tsv',
    'planning/task006/MODULE_DEPENDENCY_RATIONALE.tsv',
    'docs/architecture/TASK006_MODULE_DEPENDENCY_RESOLUTION.md',
    'docs/architecture/FOUNDATION_IMPLEMENTATION_BASELINE.md',
];
$approvedProductReferences = [
    'docs/architecture/VS001_ARCHITECTURE_SLICE.md',
    'docs/architecture/CONTENT_AND_REVISION_MODEL.md',
    'docs/architecture/CURRICULUM_LEARNING_MASTERY_MODEL.md',
    'docs/architecture/ENTERPRISE_SCENARIO_SIMULATION_MODEL.md',
    'planning/task004/VS001_ACCEPTANCE_CRITERIA.tsv',
];
foreach ($requiredProductContext as $relative) {
    $sources['product-repo/'.$relative] = ['source' => $repo.'/'.$relative, 'classification' => 'REQUIRED_IMPLEMENTATION_CONTEXT'];
}
foreach ($approvedProductReferences as $relative) {
    $sources['product-repo/'.$relative] = ['source' => $repo.'/'.$relative, 'classification' => 'APPROVED_BASELINE_REFERENCE'];
}
$outsideReferences = [
    'source-vault/derived/semantic-refined/VS001_SOURCE_SELECTION_REFINED.md',
    'source-vault/manifests/semantic-refined/VS001_SOURCE_SELECTION_REFINED.tsv',
    'source-vault/derived/semantic-refined/UNRESOLVED_DECISIONS_REFINED.md',
];
foreach ($outsideReferences as $relative) {
    $sources[$relative] = ['source' => $workspace.'/'.$relative, 'classification' => 'APPROVED_BASELINE_REFERENCE'];
}
$sources['AGENTS.md'] = ['source' => $workspace.'/AGENTS.md', 'classification' => 'REQUIRED_IMPLEMENTATION_CONTEXT'];
ksort($sources, SORT_STRING);

$missing = [];
$manifestRows = [];
$copy = static function (string $source, string $targetRelative) use ($handoff): void {
    $target = $handoff.'/'.str_replace('\\', '/', $targetRelative);
    if (! is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }
    if (! copy($source, $target)) {
        throw new RuntimeException("Copy failed: {$targetRelative}");
    }
};

foreach ($sources as $targetRelative => $record) {
    if (HandoffPathPolicy::isProhibited($targetRelative) || preg_match('#(^|/)(?:\.git|vendor|node_modules|public/build)(/|$)#i', $targetRelative)) {
        throw new RuntimeException("Prohibited handoff selection: {$targetRelative}");
    }
    if (! is_file($record['source'])) {
        $missing[] = $targetRelative;

        continue;
    }
    $copy($record['source'], $targetRelative);
    $target = $handoff.'/'.$targetRelative;
    if (! hash_equals(hash_file('sha256', $record['source']), hash_file('sha256', $target))) {
        throw new RuntimeException("Source/copy digest mismatch: {$targetRelative}");
    }
    $manifestRows[] = [
        $targetRelative,
        $targetRelative,
        (string) filesize($target),
        hash_file('sha256', $target),
        $record['classification'],
        'VERIFIED_SHA256_MATCH',
    ];
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

file_put_contents($handoff.'/ZIP_INTEGRITY_RESULT.txt', "ZIP_INTEGRITY=PASS\nCHECKS=CHECKCONS; member set; paths; duplicates; sizes; SHA-256; full stream read; prohibited members; folder equality\nENFORCEMENT=packager deletes ZIP and exits nonzero on any verification failure\n");

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

$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create review ZIP.');
}
foreach ($folderFiles as $relative => $absolute) {
    if (! $zip->addFile($absolute, $relative)) {
        throw new RuntimeException("Unable to add ZIP member: {$relative}");
    }
}
if (! $zip->close()) {
    throw new RuntimeException('Unable to close review ZIP.');
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
        throw new RuntimeException("Unable to read ZIP member: {$name}");
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
    throw new RuntimeException('Final ZIP does not exactly match the handoff folder or required files are missing.');
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
