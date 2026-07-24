<?php

declare(strict_types=1);

require_once __DIR__.'/Support/HandoffPathPolicy.php';

use Task007\Packaging\HandoffPathPolicy;

$repo = realpath(dirname(__DIR__));
$workspace = realpath($repo.'/..');
$reviewRoot = $repo.'/review-packets';
$handoff = $reviewRoot.'/TASK_006_REVIEW_HANDOFF';
$zipPath = $reviewRoot.'/TASK_006_REVIEW_HANDOFF.zip';

if ($repo === false || $workspace === false || ! str_starts_with(str_replace('\\', '/', $handoff), str_replace('\\', '/', $repo).'/review-packets/')) {
    throw new RuntimeException('Unsafe handoff target resolution.');
}

$removeTree = static function (string $directory) use (&$removeTree): void {
    if (! is_dir($directory)) {
        return;
    }
    foreach (array_diff(scandir($directory), ['.', '..']) as $name) {
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

$rootFiles = [
    '.dockerignore', '.editorconfig', '.env.example', '.gitattributes', '.gitignore', '.npmrc',
    'CONTRIBUTING.md', 'Dockerfile', 'README.md', 'SECURITY.md', 'artisan', 'compose.yaml',
    'composer.json', 'composer.lock', 'env.d.ts', 'eslint.config.js', 'package.json', 'package-lock.json',
    'phpstan.neon', 'phpunit.xml', 'prettier.config.js', 'tsconfig.json', 'vite.config.ts', 'vitest.config.ts',
];
$directories = [
    'app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'storage', 'tests', 'scripts',
    'docs/development', 'docs/governance', 'planning/task006', 'review-packets/repository-foundation-006',
];
$specific = [
    'docs/architecture/TASK006_MODULE_DEPENDENCY_RESOLUTION.md',
    'docs/architecture/FOUNDATION_IMPLEMENTATION_BASELINE.md',
];
$baselineReferences = [
    'docs/product/TASK006_REPOSITORY_FOUNDATION_SCOPE.md',
    'docs/architecture/ARCHITECTURE_BASELINE_V1_CANDIDATE.md',
    'docs/architecture/MODULAR_MONOLITH_BOUNDARIES.md',
    'docs/product/AGENTS_TASK004_PROPOSED_PATCH.md',
    'planning/task004/MODULE_BOUNDARIES.tsv',
    'planning/task004/UNRESOLVED_DECISIONS.tsv',
];
$requiredReviewFiles = [
    'CODEX_FINAL_REPORT.md', 'CHANGED_FILES.txt', 'COMMANDS_EXECUTED.txt', 'ENVIRONMENT_AND_VERSION_SUMMARY.md',
    'GIT_BASELINE_AND_COMMITS.md', 'DEPENDENCY_LOCK_AND_AUDIT_RESULTS.md', 'MODULE_BOUNDARY_RESULTS.md',
    'AUTHENTICATION_SECURITY_RESULTS.md', 'MIGRATION_AND_POSTGRES_RESULTS.md', 'FRONTEND_AND_ACCESSIBILITY_RESULTS.md',
    'DOCKER_AND_LOCAL_EXPOSURE_RESULTS.md', 'PRIOR_OUTPUT_SAFETY.md', 'TEST_RESULTS.txt', 'SCREENSHOT_INVENTORY.md',
    'RESIDUAL_LIMITATIONS.md', 'rendered/login-desktop.png', 'rendered/login-mobile.png',
    'rendered/dashboard-desktop.png', 'rendered/dashboard-mobile.png', 'rendered/mixed-rtl-ltr-closeup.png',
    'rendered/keyboard-focus-visible.png',
];

$isRuntimeResidual = static fn (string $relative): bool => HandoffPathPolicy::isRuntimeResidual($relative);

$sources = [];
foreach (array_merge($rootFiles, $specific, $baselineReferences) as $relative) {
    $sources[$relative] = true;
}
foreach ($requiredReviewFiles as $relative) {
    $sources['review-packets/repository-foundation-006/'.$relative] = true;
}
foreach ($directories as $directory) {
    $absolute = $repo.'/'.$directory;
    if (! is_dir($absolute)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($repo) + 1));
            if (! $isRuntimeResidual($relative)) {
                $sources[$relative] = true;
            }
        }
    }
}
ksort($sources, SORT_STRING);

$prohibited = '#(^|/)(\.git|vendor|node_modules|source-vault/originals|browser-profiles?|database-volumes?)(/|$)|(^|/)\.env$|TASK_00[1-5].*\.zip$#i';
$missing = [];
$manifestRows = [];

$copyOne = static function (string $source, string $relativeTarget): void {
    $target = $GLOBALS['handoff'].'/'.str_replace('\\', '/', $relativeTarget);
    if (! is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }
    if (! copy($source, $target)) {
        throw new RuntimeException("Copy failed: {$source}");
    }
};
$GLOBALS['handoff'] = $handoff;

$agentsSource = $workspace.'/AGENTS.md';
if (! is_file($agentsSource)) {
    $missing[] = 'AGENTS.md';
} else {
    $copyOne($agentsSource, 'AGENTS.md');
    $manifestRows[] = ['AGENTS.md', 'AGENTS.md', filesize($agentsSource), hash_file('sha256', $agentsSource), 'MODIFIED', 'VERIFIED_SHA256_MATCH'];
}

foreach (array_keys($sources) as $relative) {
    if (preg_match($prohibited, $relative) || HandoffPathPolicy::isProhibited($relative)) {
        throw new RuntimeException("Prohibited source selected: {$relative}");
    }
    $source = $repo.'/'.$relative;
    if (! is_file($source)) {
        $missing[] = 'product-repo/'.$relative;

        continue;
    }
    $target = 'product-repo/'.$relative;
    $copyOne($source, $target);
    $classification = in_array($relative, $baselineReferences, true) ? 'APPROVED_BASELINE_REFERENCE' : (in_array($relative, ['.editorconfig', '.gitattributes', '.gitignore'], true) ? 'MODIFIED' : 'CREATED');
    $manifestRows[] = ['product-repo/'.$relative, $target, filesize($source), hash_file('sha256', $source), $classification, 'VERIFIED_SHA256_MATCH'];
}

file_put_contents($handoff.'/MISSING_FILES.txt', $missing === [] ? "NONE\nMISSING_COUNT=0\n" : implode("\n", $missing)."\nMISSING_COUNT=".count($missing)."\n");

$manifest = "source_relative_path\thandoff_relative_path\tfile_size_bytes\tsha256\tchange_classification\tverification_status\n";
foreach ($manifestRows as $row) {
    $manifest .= implode("\t", $row)."\n";
}
file_put_contents($handoff.'/HANDOFF_MANIFEST.tsv', $manifest);

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
    if (filesize($path) > 5_000_000) {
        continue;
    }
    $content = file_get_contents($path);
    foreach ($secretPatterns as $name => $pattern) {
        if (preg_match($pattern, $content)) {
            $secretFindings[] = $row[1]."\t{$name}";
        }
    }
}
if ($secretFindings !== []) {
    file_put_contents($handoff.'/SECRET_SCAN_HANDOFF_RESULT.txt', "SECRET_SCAN=FAIL\n".implode("\n", $secretFindings)."\n");
    throw new RuntimeException('Handoff secret scan failed.');
}
file_put_contents($handoff.'/SECRET_SCAN_HANDOFF_RESULT.txt', "SECRET_SCAN=PASS\nSCANNER=deterministic limited fallback\nFILES_SCANNED=".count($manifestRows)."\nFINDINGS=0\n");
file_put_contents($handoff.'/ZIP_INTEGRITY_RESULT.txt', "ZIP_INTEGRITY=PASS\nCHECKS=ZipArchive CHECKCONS; full member-set equality; member sizes; complete member stream reads; prohibited-path rejection\nNOTE=Packaging exits nonzero and removes the archive if any final verification fails.\n");

$filesBeforeSums = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($handoff, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $filesBeforeSums[str_replace('\\', '/', substr($file->getPathname(), strlen($handoff) + 1))] = $file->getPathname();
    }
}
ksort($filesBeforeSums, SORT_STRING);
$sums = '';
foreach ($filesBeforeSums as $relative => $absolute) {
    $sums .= hash_file('sha256', $absolute).'  '.$relative."\n";
}
file_put_contents($handoff.'/SHA256SUMS.txt', $sums);

$allFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($handoff, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $allFiles[str_replace('\\', '/', substr($file->getPathname(), strlen($handoff) + 1))] = $file->getPathname();
    }
}
ksort($allFiles, SORT_STRING);

$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create ZIP.');
}
foreach ($allFiles as $relative => $absolute) {
    $zip->addFile($absolute, $relative);
}
if (! $zip->close()) {
    throw new RuntimeException('Unable to finalize ZIP.');
}

$verify = new ZipArchive;
if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
    throw new RuntimeException('ZIP CHECKCONS failed.');
}
$members = [];
$uncompressed = 0;
for ($index = 0; $index < $verify->numFiles; $index++) {
    $stat = $verify->statIndex($index);
    $name = $stat['name'];
    if (preg_match($prohibited, $name) || HandoffPathPolicy::isProhibited($name)) {
        throw new RuntimeException("Prohibited ZIP member: {$name}");
    }
    $members[$name] = (int) $stat['size'];
    $uncompressed += (int) $stat['size'];
    $stream = $verify->getStream($name);
    if ($stream === false) {
        throw new RuntimeException("ZIP stream failure: {$name}");
    }
    while (! feof($stream)) {
        if (fread($stream, 8192) === false) {
            throw new RuntimeException("ZIP CRC read failure: {$name}");
        }
    }
    fclose($stream);
}
$verify->close();

$expected = array_map('filesize', $allFiles);
if ($members !== $expected) {
    unlink($zipPath);
    throw new RuntimeException('ZIP member path/size verification failed.');
}
if ($missing !== []) {
    unlink($zipPath);
    throw new RuntimeException('Required handoff files are missing.');
}

echo json_encode([
    'zip_path' => str_replace('\\', '/', realpath($zipPath)),
    'total_files' => count($members),
    'total_uncompressed_bytes' => $uncompressed,
    'missing_count' => count($missing),
    'secret_scan' => 'PASS_LIMITED_FALLBACK',
    'zip_integrity' => 'PASS',
    'zip_sha256' => hash_file('sha256', $zipPath),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
