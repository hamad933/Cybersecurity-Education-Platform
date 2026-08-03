<?php

declare(strict_types=1);

$compose = file_get_contents(dirname(__DIR__).'/compose.yaml');
$dockerfile = file_get_contents(dirname(__DIR__).'/Dockerfile');
$checks = [
    'app-loopback-binding' => str_contains($compose, '127.0.0.1:${APP_PORT:-8080}:8080'),
    'postgres-pinned' => str_contains($compose, 'image: postgres:18.4-bookworm'),
    'postgres-no-host-port' => ! preg_match('/  postgres:.*?\n    ports:/s', $compose),
    'postgres-healthcheck' => str_contains($compose, 'pg_isready -U cyber_platform -d cyber_platform'),
    'named-database-volume' => str_contains($compose, 'postgres-data:/var/lib/postgresql'),
    'required-app-key' => str_contains($compose, 'APP_KEY: "${APP_KEY:?'),
    'required-database-password' => str_contains($compose, 'DB_PASSWORD: "${DB_PASSWORD:?'),
    'no-prohibited-service' => ! preg_match('/^  (redis|kafka|mailpit|selenium):/mi', $compose),
    'non-root-runtime' => str_contains($dockerfile, 'USER www-data'),
    'reproducible-images' => str_contains($dockerfile, 'composer:2.10.2') && str_contains($dockerfile, 'node:24.18.0') && str_contains($dockerfile, 'php:8.5.8'),
];

foreach ($checks as $name => $passed) {
    echo $name.'='.($passed ? 'PASS' : 'FAIL').PHP_EOL;
}
exit(in_array(false, $checks, true) ? 1 : 0);
