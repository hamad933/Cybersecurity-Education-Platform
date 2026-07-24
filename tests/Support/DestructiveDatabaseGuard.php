<?php

namespace Tests\Support;

use RuntimeException;

final class DestructiveDatabaseGuard
{
    /**
     * @param  list<string>  $allowedConnections
     * @param  list<string>  $allowedHosts
     */
    public function __construct(
        private readonly string $environment,
        private readonly string $connection,
        private readonly string $database,
        private readonly string $host,
        private readonly array $allowedConnections,
        private readonly array $allowedHosts,
    ) {}

    public static function fromRuntime(): self
    {
        $connection = (string) config('database.default');

        return new self(
            (string) app()->environment(),
            $connection,
            (string) config("database.connections.{$connection}.database"),
            (string) config("database.connections.{$connection}.host"),
            self::csv((string) env('TEST_DATABASE_ALLOWED_CONNECTIONS', 'pgsql')),
            self::csv((string) env('TEST_DATABASE_ALLOWED_HOSTS', '127.0.0.1,localhost,postgres')),
        );
    }

    public function assertSafe(): void
    {
        if ($this->environment !== 'testing') {
            throw new RuntimeException('Destructive database operations require APP_ENV=testing.');
        }
        if (! in_array($this->connection, $this->allowedConnections, true)) {
            throw new RuntimeException('The configured test database connection is not allow-listed.');
        }
        if ($this->database === '' || ! str_ends_with(strtolower($this->database), '_test')) {
            throw new RuntimeException('The test database name must end with _test.');
        }
        if (! in_array(strtolower($this->host), array_map('strtolower', $this->allowedHosts), true)) {
            throw new RuntimeException('The configured test database host is not allow-listed.');
        }
    }

    /** @return list<string> */
    private static function csv(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $item): bool => $item !== ''));
    }
}
