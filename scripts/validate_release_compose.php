<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$compose = file_get_contents($root.'/compose.release.yaml');
$dockerfile = file_get_contents($root.'/Dockerfile');
if (! is_string($compose) || ! is_string($dockerfile)) {
    fwrite(STDERR, "Release deployment files are missing.\n");
    exit(1);
}
$checks = [
    'release-loopback-binding' => str_contains($compose, '127.0.0.1:${APP_PORT:-8081}:8080'),
    'shared-app-queue-image' => substr_count($compose, 'image: ${RELEASE_IMAGE:-cybersecurity-education-platform:v1}') === 2,
    'postgres-pinned' => str_contains($compose, 'image: postgres:18.4-bookworm'),
    'postgres-no-host-port' => ! preg_match('/\n  postgres:.*?\n    ports:/s', $compose),
    'postgres-healthcheck' => str_contains($compose, 'pg_isready -U cyber_platform -d cyber_platform'),
    'required-secrets' => str_contains($compose, 'APP_KEY: "${APP_KEY:?') && str_contains($compose, 'DB_PASSWORD: "${DB_PASSWORD:?'),
    'queue-bounded-retries' => str_contains($compose, 'queue:work') && str_contains($compose, '--tries=3') && str_contains($compose, '--timeout=120') && str_contains($compose, '--max-time=3600'),
    'queue-http-health-disabled' => preg_match('/\n  queue:\n.*?\n    healthcheck:\n      disable: true(?:\n|$)/s', str_replace("\r\n", "\n", $compose)) === 1,
    'non-root-runtime' => substr_count($compose, 'user: "www-data"') === 2 && str_contains($dockerfile, 'USER www-data'),
    'capabilities-dropped' => substr_count($compose, 'cap_drop: ["ALL"]') === 2,
    'no-new-privileges' => substr_count($compose, 'no-new-privileges:true') >= 3,
    'manual-http-loopback' => str_contains($compose, 'FORCE_HTTPS: "false"'),
];
foreach ($checks as $name => $passed) {
    echo $name.'='.($passed ? 'PASS' : 'FAIL').PHP_EOL;
}
exit(in_array(false, $checks, true) ? 1 : 0);
