<?php

namespace Tests\Feature\Modules\Platform\SystemOperations;

use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\Platform\Validation\ValidationExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use InvalidArgumentException;
use Illuminate\Database\UniqueConstraintViolationException;

class ValidationExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_binds_evidence_strictly_to_running_execution(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'validation_task',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'val-test-1',
            'status' => 'running',
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $service = new ValidationExecutionService();
        $evidence = $service->bindEvidence(
            $run->id,
            'package_validation',
            ['tech-error-1'],
            ['knowledge-warning-1']
        );

        $this->assertEquals($run->id, $evidence->execution_id);
        $this->assertEquals(1, $evidence->technical_findings_count);
        $this->assertEquals(1, $evidence->knowledge_findings_count);
        $this->assertNotNull($evidence->findings_digest);
    }
    
    public function test_it_enforces_one_evidence_set_per_artifact(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'validation_task',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'val-test-2',
            'status' => 'running',
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $service = new ValidationExecutionService();
        $service->bindEvidence($run->id, 'package_validation', [], []);
        
        $this->expectException(UniqueConstraintViolationException::class);
        $service->bindEvidence($run->id, 'package_validation', ['duplicate'], []);
    }
    
    public function test_it_prevents_binding_after_execution_completion(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'validation_task',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'val-test-3',
            'status' => 'completed',
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $service = new ValidationExecutionService();
        $this->expectException(InvalidArgumentException::class);
        $service->bindEvidence($run->id, 'package_validation', [], []);
    }

    public function test_it_handles_concurrent_terminalization_races(): void
    {
        $run = ProcessingRun::query()->create([
            'type' => 'validation_task',
            'input_digest' => hash('sha256', 'test'),
            'idempotency_key' => 'val-test-con',
            'status' => 'running',
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        \Illuminate\Support\Facades\DB::commit();

        $defaultConn = config('database.default');
        config(['database.connections.pgsql_worker_a' => config("database.connections.{$defaultConn}")]);
        config(['database.connections.pgsql_worker_b' => config("database.connections.{$defaultConn}")]);
        \Illuminate\Support\Facades\DB::purge($defaultConn);

        $barrierLock = 7654321;

        $pid = pcntl_fork();
        
        if ($pid == -1) {
            $this->fail('Failed to fork process.');
        } else if ($pid) {
            // Parent process: Terminalizes the run
            config(['database.default' => 'pgsql_worker_a']);
            $dbA = \Illuminate\Support\Facades\DB::connection('pgsql_worker_a');
            
            $pidA = $dbA->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $this->assertGreaterThan(0, $pidA);
            
            $dbA->select('SELECT pg_advisory_lock(?)', [$barrierLock]);
            
            $dbA->beginTransaction();
            $lockedRun = ProcessingRun::query()->where('id', $run->id)->lockForUpdate()->first();
            
            $dbA->select('SELECT pg_advisory_unlock(?)', [$barrierLock]);
            
            usleep(200000);
            
            $lockedRun->transitionTo('completed');
            $dbA->commit();
            
            pcntl_wait($status);
            $this->assertEquals(0, pcntl_wexitstatus($status), 'Child process failed or crashed.');
            
            config(['database.default' => $defaultConn]);
            ProcessingRun::query()->where('id', $run->id)->delete();
            \Illuminate\Support\Facades\DB::beginTransaction();
        } else {
            // Child process: Tries to bind evidence
            try {
                config(['database.default' => 'pgsql_worker_b']);
                $dbB = \Illuminate\Support\Facades\DB::connection('pgsql_worker_b');
                
                $pidB = $dbB->selectOne('SELECT pg_backend_pid() AS pid')->pid;
                if (!$pidB) { exit(1); }
                
                usleep(50000);
                $dbB->select('SELECT pg_advisory_lock(?)', [$barrierLock]);
                $dbB->select('SELECT pg_advisory_unlock(?)', [$barrierLock]);
                
                $service = new ValidationExecutionService();
                $service->bindEvidence($run->id, 'package_validation', [], []);
                exit(2); // Should not reach here, should throw InvalidArgumentException
            } catch (\InvalidArgumentException $e) {
                // Correctly rejected because it completed
                exit(0);
            } catch (\Throwable $e) {
                exit(3);
            }
        }
    }
}
