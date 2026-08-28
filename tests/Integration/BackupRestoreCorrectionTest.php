<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Backup\BackupService;
use App\Modules\Platform\Backup\BackupManifest;
use App\Modules\Platform\Backup\RestoreRun;
use App\Modules\Platform\Packages\PortablePackage;
use App\Modules\Platform\Blobs\BlobStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Modules\Platform\Audit\AuditChainVerifier;
use Mockery;

final class BackupRestoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_records_durable_failure_on_exception(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        
        $mock = Mockery::mock(AuditChainVerifier::class);
        $mock->shouldReceive('verify')->andReturn(['valid' => false, 'count' => 0]);
        $this->app->instance(AuditChainVerifier::class, $mock);

        try {
            app(BackupService::class)->create((string) $owner->id);
            $this->fail('Should throw exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Backup refused', $e->getMessage());
        }

        $this->assertDatabaseHas('audit_records', [
            'action' => 'backup.created',
            'outcome' => 'failure',
            'target_type' => 'system',
        ]);
    }

    public function test_apply_to_isolated_database_transitions_to_activation_pending(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $result = app(BackupService::class)->create((string) $owner->id);

        $stream = app(BlobStore::class)->readStream($result['blob_key']);
        $package = app(BackupService::class)->inspect($stream);
        fclose($stream);

        // Switch to _restore_drill database name for test
        $originalDb = DB::connection()->getDatabaseName();
        config(['database.connections.pgsql.database' => $originalDb . '_restore_drill']);
        DB::purge('pgsql');

        // Apply
        $run = app(BackupService::class)->applyToIsolatedDatabase($package, (string) $owner->id);

        $this->assertSame('activation_pending', $run->status);
        $this->assertDatabaseHas('restore_runs', [
            'id' => $run->id,
            'status' => 'activation_pending',
        ]);
        
        $this->assertDatabaseHas('audit_records', [
            'action' => 'restore.drill.completed',
            'outcome' => 'success',
            'target_type' => 'restore_run',
            'target_identifier' => (string) $run->id,
        ]);

        config(['database.connections.pgsql.database' => $originalDb]);
        DB::purge('pgsql');
    }

    public function test_apply_to_isolated_database_transitions_to_rollback_failed_on_exception(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $result = app(BackupService::class)->create((string) $owner->id);

        $stream = app(BlobStore::class)->readStream($result['blob_key']);
        $package = app(BackupService::class)->inspect($stream);
        fclose($stream);

        // Force a mock failure in verifyRestoredCounts by mocking AuditChainVerifier during restore
        $mock = Mockery::mock(AuditChainVerifier::class);
        $mock->shouldReceive('verify')->andThrow(new \RuntimeException('Forced exception for drill'));
        $this->app->instance(AuditChainVerifier::class, $mock);

        $originalDb = DB::connection()->getDatabaseName();
        config(['database.connections.pgsql.database' => $originalDb . '_restore_drill']);
        DB::purge('pgsql');

        try {
            app(BackupService::class)->applyToIsolatedDatabase($package, (string) $owner->id);
            $this->fail('Should throw exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Forced exception for drill', $e->getMessage());
        }

        $this->assertDatabaseHas('restore_runs', [
            'status' => 'rollback_failed',
        ]);
        
        $this->assertDatabaseHas('audit_records', [
            'action' => 'restore.drill.completed',
            'outcome' => 'failure',
            'target_type' => 'restore_run',
        ]);

        config(['database.connections.pgsql.database' => $originalDb]);
        DB::purge('pgsql');
    }
}
