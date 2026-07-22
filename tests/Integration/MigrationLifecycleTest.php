<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationLifecycleTest extends TestCase
{
    public function test_postgresql_fresh_rollback_and_reapply_lifecycle(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        foreach (['owner_accounts', 'application_sessions', 'audit_records', 'blob_objects', 'processing_runs', 'outbox_messages', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing PostgreSQL table {$table}");
        }
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => 4, '--force' => true]));
        $this->assertFalse(Schema::hasTable('owner_accounts'));
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertTrue(Schema::hasTable('owner_accounts'));
    }
}
