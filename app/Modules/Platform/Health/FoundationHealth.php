<?php

namespace App\Modules\Platform\Health;

use App\Modules\Platform\Blobs\BlobStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FoundationHealth
{
    public function __construct(private readonly BlobStore $blobs) {}

    /** @return array<string, 'ok'|'failed'> */
    public function summaryChecks(): array
    {
        $database = $this->attempt(fn () => DB::selectOne('select 1') !== null);

        return [
            'configuration' => config('platform.auth_bypass') === false ? 'ok' : 'failed',
            'database' => $database,
            'queue' => $database === 'ok' ? $this->attempt(fn () => Schema::hasTable('jobs')) : 'failed',
            'storage' => is_writable(storage_path()) ? 'ok' : 'failed',
            'migrations' => $database === 'ok' ? $this->attempt(fn () => Schema::hasTable('migrations') && Schema::hasTable('owner_accounts')) : 'failed',
            'profile' => in_array(config('platform.profile'), ['local', 'test', 'release'], true) ? 'ok' : 'failed',
        ];
    }

    /** @return array<string, 'ok'|'failed'> */
    public function diagnosticChecks(): array
    {
        $summary = $this->summaryChecks();

        return [
            'configuration' => $summary['configuration'],
            'database' => $summary['database'],
            'queue' => $summary['queue'],
            'storage' => $summary['storage'],
            'blob' => $this->attempt(fn () => $this->probeBlobStore()),
            'migrations' => $summary['migrations'],
            'profile' => $summary['profile'],
        ];
    }

    private function probeBlobStore(): bool
    {
        $input = fopen('php://temp', 'w+b');
        if ($input === false) {
            return false;
        }

        $stored = null;
        $output = null;
        try {
            fwrite($input, 'diagnostic');
            rewind($input);
            $stored = $this->blobs->writeStream($input);
            $output = $this->blobs->readStream($stored->key);

            return $stored->size === 10
                && $stored->sha256 === hash('sha256', 'diagnostic')
                && stream_get_contents($output) === 'diagnostic';
        } finally {
            if (is_resource($output)) {
                fclose($output);
            }
            fclose($input);
            if ($stored !== null) {
                $this->blobs->delete($stored->key);
            }
        }
    }

    private function attempt(callable $check): string
    {
        try {
            return $check() ? 'ok' : 'failed';
        } catch (Throwable) {
            return 'failed';
        }
    }
}
