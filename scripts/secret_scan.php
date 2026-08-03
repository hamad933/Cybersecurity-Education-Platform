<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$safeRoot = str_replace('\\', '/', $root);
$stderrRedirect = PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null';

$git = static function (string $arguments) use ($root, $safeRoot): string {
    $command = 'git -c safe.directory='.escapeshellarg($safeRoot).' '.$arguments;

    return (string) shell_exec('cd '.escapeshellarg($root).' && '.$command);
};

$patterns = [
    'private-key' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'aws-access-key' => '/\bAKIA[0-9A-Z]{16}\b/',
    'github-token' => '/\bgh[pousr]_[A-Za-z0-9]{30,}\b/',
    'provider-token' => '/\bsk-[A-Za-z0-9_-]{20,}\b/',
    'assigned-secret' => '/^(?:APP_KEY|DB_PASSWORD|PASSWORD|SECRET|TOKEN)=(?!\s*(?:$|null$|<[^>]+>$|\$\{[^}]+\}$))\S+/mi',
];
$excludedPrefixes = ['vendor/', 'node_modules/', 'public/build/', 'storage/', 'review-packets/TASK_006_REVIEW_HANDOFF/'];
$files = preg_split('/\R/', trim($git('ls-files --cached --others --exclude-standard')), -1, PREG_SPLIT_NO_EMPTY);
$findings = [];
$scanned = 0;
foreach ($files as $relative) {
    $relative = str_replace('\\', '/', $relative);
    if (array_filter($excludedPrefixes, fn (string $prefix): bool => str_starts_with($relative, $prefix))) {
        continue;
    }
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (! is_file($path) || filesize($path) > 5_000_000) {
        continue;
    }
    $content = file_get_contents($path);
    $scanned++;
    foreach ($patterns as $name => $pattern) {
        if (preg_match($pattern, $content)) {
            $findings[] = "WORKTREE\t{$relative}\t{$name}";
        }
    }
}

$historyPatterns = [
    'private-key' => '-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----',
    'aws-access-key' => 'AKIA[0-9A-Z]{16}',
    'github-token' => 'gh[pousr]_[A-Za-z0-9]{30,}',
    'provider-token' => 'sk-[A-Za-z0-9_-]{20,}',
    'assigned-secret' => '(APP_KEY|DB_PASSWORD|PASSWORD|SECRET|TOKEN)=[^<${[:space:]]',
];
$commits = preg_split('/\R/', trim($git('rev-list --all')), -1, PREG_SPLIT_NO_EMPTY);
foreach ($commits as $commit) {
    foreach ($historyPatterns as $name => $pattern) {
        $matches = trim($git('grep -I -l -E -- '.escapeshellarg($pattern).' '.escapeshellarg($commit).' -- .'.$stderrRedirect));
        foreach (preg_split('/\R/', $matches, -1, PREG_SPLIT_NO_EMPTY) as $match) {
            $findings[] = "GIT_HISTORY\t{$commit}:{$match}\t{$name}";
        }
    }
}

$historicalNames = preg_split('/\R/', trim($git('log --all --name-only --format=')), -1, PREG_SPLIT_NO_EMPTY);
if (in_array('.env', $historicalNames, true)) {
    $findings[] = "GIT_HISTORY\t.env\tforbidden-secret-file";
}

if (trim($git('ls-files --error-unmatch .env'.$stderrRedirect)) !== '') {
    $findings[] = "TRACKING\t.env\tforbidden-secret-file";
}

if ($findings !== []) {
    fwrite(STDERR, "LIMITED_FALLBACK_SECRET_SCAN: FAIL\n".implode("\n", array_unique($findings))."\n");
    exit(1);
}

echo "LIMITED_FALLBACK_SECRET_SCAN: PASS\n";
echo "FILES_SCANNED: {$scanned}\n";
echo "GIT_HISTORY_SCANNED: YES\n";
echo "KNOWN_LIMITATION: deterministic patterns are narrower than gitleaks\n";
