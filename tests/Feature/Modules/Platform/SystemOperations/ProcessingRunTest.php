<?php

namespace Tests\Feature\Modules\Platform\SystemOperations;

use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\Platform\Processing\WorkerHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ProcessingRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prevents_duplicate_retry_children_and_returns_existing(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-dup',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'retry_ordinal' => 0,
        ]);

        $child1 = $run->retryRun();
        $child2 = $run->retryRun();

        $this->assertEquals($child1->id, $child2->id);
        $this->assertEquals(1, $child2->retry_ordinal); // Ordinal does not fork
        $this->assertEquals(1, ProcessingRun::query()->where('retry_of_id', $run->id)->count());
    }
    
    public function test_it_handles_concurrent_duplicate_requests_deterministically(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-dup-con',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'retry_ordinal' => 0,
        ]);

        \Illuminate\Support\Facades\DB::commit();

        $defaultConn = config('database.default');
        config(['database.connections.pgsql_worker_a' => config("database.connections.{$defaultConn}")]);
        config(['database.connections.pgsql_worker_b' => config("database.connections.{$defaultConn}")]);
        \Illuminate\Support\Facades\DB::purge($defaultConn);

        $barrierLock = 1234567;
        
        $pid = pcntl_fork();
        
        if ($pid == -1) {
            $this->fail('Failed to fork process.');
        } else if ($pid) {
            // Parent process
            config(['database.default' => 'pgsql_worker_a']);
            $dbA = \Illuminate\Support\Facades\DB::connection('pgsql_worker_a');
            
            $pidA = $dbA->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $this->assertGreaterThan(0, $pidA);
            
            $dbA->select('SELECT pg_advisory_lock(?)', [$barrierLock]);
            
            $dbA->beginTransaction();
            $lockedRun = ProcessingRun::query()->where('id', $run->id)->lockForUpdate()->first();
            
            $dbA->select('SELECT pg_advisory_unlock(?)', [$barrierLock]);
            
            usleep(200000);
            
            $child1 = $lockedRun->retryRun();
            $dbA->commit();
            
            pcntl_wait($status);
            $this->assertEquals(0, pcntl_wexitstatus($status), 'Child process failed or crashed.');
            
            config(['database.default' => $defaultConn]);
            $this->assertEquals(1, ProcessingRun::query()->where('retry_of_id', $run->id)->count());
            
            ProcessingRun::query()->where('retry_of_id', $run->id)->delete();
            ProcessingRun::query()->where('id', $run->id)->delete();
            \Illuminate\Support\Facades\DB::beginTransaction();
        } else {
            // Child process
            try {
                config(['database.default' => 'pgsql_worker_b']);
                $dbB = \Illuminate\Support\Facades\DB::connection('pgsql_worker_b');
                
                $pidB = $dbB->selectOne('SELECT pg_backend_pid() AS pid')->pid;
                if (!$pidB) { exit(1); }
                
                usleep(50000);
                $dbB->select('SELECT pg_advisory_lock(?)', [$barrierLock]);
                $dbB->select('SELECT pg_advisory_unlock(?)', [$barrierLock]);
                
                $child2 = $run->fresh()->retryRun();
                if (!$child2) { exit(2); }
                
                exit(0);
            } catch (\Throwable $e) {
                exit(3);
            }
        }
    }
    
    public function test_migration_down_preflight_verifies_duplicates(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-migr',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'retry_ordinal' => 0,
        ]);
        
        $child = $run->retryRun(); // Creates child with same idempotency_key
        
        $migrationPath = database_path('migrations/2026_09_01_000000_sysops_processing_lineage_correction.php');
        $migration = require $migrationPath;
        
        // 1. Fail down
        $failed = false;
        try {
            $migration->down();
        } catch (\RuntimeException $e) {
            $failed = true;
            $this->assertStringContainsString('Cannot reverse processing_runs idempotency constraint', $e->getMessage());
        }
        $this->assertTrue($failed, 'Expected migration to fail due to duplicates.');
        
        // 2. Verify state unchanged
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'retry_ordinal'));
        
        // 3. Fix data
        $child->update(['idempotency_key' => 'test-idemp-migr-fixed']);
        
        // 4. Succeed down
        $migration->down();
        
        // 5. Verify schema (down succeeded)
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'retry_ordinal'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'worker_identifier'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'retry_of_id'));
        
        // 6. Succeed up
        $migration->up();
        
        // 7. Verify schema (up succeeded)
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'retry_ordinal'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'worker_identifier'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('processing_runs', 'retry_of_id'));
    }
    public function test_it_creates_new_retry_lineage_on_retry(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-1',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'retry_ordinal' => 0,
            'worker_identifier' => 'test-worker',
        ]);

        $retryRun = $run->retryRun();

        $this->assertNotEquals($run->id, $retryRun->id);
        $this->assertEquals($run->id, $retryRun->retry_of_id);
        $this->assertEquals('pending', $retryRun->status);
        $this->assertEquals($run->idempotency_key, $retryRun->idempotency_key);
        $this->assertEquals(1, $retryRun->retry_ordinal);
        $this->assertEquals(0, $retryRun->attempt_count); // Fresh instance local attempts
    }

    public function test_it_prevents_retry_if_max_attempts_reached(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-2',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'retry_ordinal' => 3, // Retry chain exhausted
        ]);

        $this->expectException(InvalidArgumentException::class);
        $run->retryRun();
    }

    public function test_it_preserves_terminal_status(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'test_run',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'test-idemp-3',
            'status' => 'completed',
            'attempt_count' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $run->transitionTo('running');
    }
    
    public function test_it_supports_worker_liveness_heartbeats(): void
    {
        $worker = WorkerHeartbeat::query()->create([
            'worker_identifier' => 'test-host-123',
            'provider' => 'database',
            'last_seen_at' => now(),
            'ttl_seconds' => 120,
        ]);
        
        $this->assertEquals('HEALTHY', $worker->status());
        
        $worker->last_seen_at = now()->subSeconds(200);
        $this->assertEquals('STALE', $worker->status());
        
        $worker->last_seen_at = now()->subSeconds(400);
        $this->assertEquals('DOWN', $worker->status());
        
        $unknown = new WorkerHeartbeat();
        $this->assertEquals('UNKNOWN', $unknown->status());
    }

    public function test_production_heartbeat_refresher_works_correctly(): void
    {
        $service = new \App\Modules\Platform\Processing\WorkerHeartbeatService();
        $workerId = 'test-run-worker';
        
        // Ensure never-observed -> UNKNOWN
        $unknown = new WorkerHeartbeat();
        $this->assertEquals('UNKNOWN', $unknown->status());

        // Test fresh real refresh -> HEALTHY
        $service->recordHeartbeat($workerId, 'database', 120);
        
        $heartbeat = WorkerHeartbeat::query()->where('worker_identifier', $workerId)->first();
        $this->assertNotNull($heartbeat);
        $this->assertEquals('database', $heartbeat->provider);
        $this->assertEquals($workerId, $heartbeat->worker_identifier);
        $this->assertEquals('HEALTHY', $heartbeat->status());
        
        // Ensure unrelated stale persisted state cannot be presented as fresh runtime truth
        $staleWorker = WorkerHeartbeat::query()->create([
            'worker_identifier' => 'stale-worker',
            'provider' => 'database',
            'last_seen_at' => now()->subSeconds(300),
            'ttl_seconds' => 120,
        ]);
        
        $this->assertEquals('STALE', $staleWorker->status());
        
        // Prove aged > TTL => DOWN/STALE
        $downWorker = WorkerHeartbeat::query()->create([
            'worker_identifier' => 'down-worker',
            'provider' => 'database',
            'last_seen_at' => now()->subSeconds(600),
            'ttl_seconds' => 120,
        ]);
        
        $this->assertEquals('DOWN', $downWorker->status());
    }
}
