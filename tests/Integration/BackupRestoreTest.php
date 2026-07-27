<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Backup\BackupService;
use App\Modules\Platform\Blobs\BlobStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_is_verified_and_web_path_only_stages_restore(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $result = app(BackupService::class)->create((string) $owner->id);
        $this->assertSame('verified', $result['manifest']->status);

        $stream = app(BlobStore::class)->readStream($result['blob_key']);
        try {
            $run = app(BackupService::class)->stage($stream, (string) $owner->id);
        } finally {
            fclose($stream);
        }
        $this->assertSame('staged', $run->status);
        $this->assertFalse($run->verification['activation_allowed']);
        $this->assertDatabaseHas('audit_records', ['action' => 'restore.staged', 'outcome' => 'success']);
    }
}
