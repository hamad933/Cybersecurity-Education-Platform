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
    public function checks(): array
    {
        return [
            'configuration' => config('platform.auth_bypass') === false ? 'ok' : 'failed',
            'database' => $this->attempt(fn () => DB::selectOne('select 1') !== null),
            'queue' => $this->attempt(fn () => Schema::hasTable('jobs')),
            'storage' => is_writable(storage_path()) ? 'ok' : 'failed',
            'blob' => $this->attempt(function (): bool {
                $stream = fopen('php://temp', 'w+b');
                fwrite($stream, 'diagnostic');
                rewind($stream);

                return $this->blobs->writeStream($stream)->size === 10;
            }),
            'migrations' => $this->attempt(fn () => Schema::hasTable('migrations') && Schema::hasTable('owner_accounts')),
            'profile' => in_array(config('platform.profile'), ['local', 'test', 'release'], true) ? 'ok' : 'failed',
        ];
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
