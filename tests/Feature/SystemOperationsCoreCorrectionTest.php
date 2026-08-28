<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Health\FoundationHealth;
use App\Modules\Platform\Processing\ProcessingRun;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class SystemOperationsCoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_controller_reports_degraded_on_failure(): void
    {
        $mock = $this->mock(FoundationHealth::class);
        $mock->shouldReceive('summaryChecks')->once()->andReturn([
            'database' => 'failed',
            'storage' => 'ok',
        ]);

        $response = $this->getJson('/health/live');
        $response->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'checks' => [
                    'database' => 'failed',
                    'storage' => 'ok',
                ],
            ]);
    }

    public function test_liveness_controller_reports_ok_on_success(): void
    {
        $mock = $this->mock(FoundationHealth::class);
        $mock->shouldReceive('summaryChecks')->once()->andReturn([
            'database' => 'ok',
            'storage' => 'ok',
            'queue' => 'ok',
        ]);

        $response = $this->getJson('/health/live');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'checks' => [
                    'database' => 'ok',
                    'storage' => 'ok',
                    'queue' => 'ok',
                ],
            ]);
    }

    public function test_processing_run_lifecycle_transitions_and_retry(): void
    {
        $run = new ProcessingRun([
            'id' => (string) Str::uuid(),
            'type' => 'content.ingest',
            'input_digest' => str_repeat('a', 64),
            'idempotency_key' => 'idemp_key_1',
            'status' => 'pending',
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);
        $run->save();

        // pending -> running
        $run->transitionTo('running');
        $this->assertSame('running', $run->status);
        $this->assertSame(1, $run->attempt_count);
        $this->assertNotNull($run->started_at);

        // running -> failed
        $run->transitionTo('failed', 'TIMEOUT', 'Operation timed out safely');
        $this->assertSame('failed', $run->status);
        $this->assertSame('TIMEOUT', $run->error_category);
        $this->assertSame('Operation timed out safely', $run->safe_error_message);
        $this->assertNotNull($run->completed_at);

        // failed -> pending (Retry transition)
        $run->transitionTo('pending');
        $this->assertSame('pending', $run->status);
        $this->assertNull($run->started_at);
        $this->assertNull($run->completed_at);
        $this->assertNull($run->error_category);
        $this->assertNull($run->safe_error_message);

        // retry: pending -> running
        $run->transitionTo('running');
        $this->assertSame('running', $run->status);
        $this->assertSame(2, $run->attempt_count);

        // running -> completed
        $run->transitionTo('completed');
        $this->assertSame('completed', $run->status);
        $this->assertNotNull($run->completed_at);
    }

    public function test_processing_run_fails_when_lease_is_expired(): void
    {
        $run = new ProcessingRun([
            'id' => (string) Str::uuid(),
            'type' => 'content.ingest',
            'input_digest' => str_repeat('b', 64),
            'idempotency_key' => 'idemp_key_2',
            'status' => 'running',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'leased_until' => CarbonImmutable::now()->subMinutes(5),
        ]);
        $run->save();

        $run->failIfLeaseExpired('Lease elapsed');
        $this->assertSame('failed', $run->status);
        $this->assertSame('WORKER_CRASHED', $run->error_category);
        $this->assertSame('Lease elapsed', $run->safe_error_message);
    }

    public function test_audit_writer_rejects_substring_sensitive_keys(): void
    {
        $writer = app(AuditWriter::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sensitive audit metadata key rejected.');

        $writer->append([
            'action' => 'user.login',
            'target_type' => 'user',
            'correlation_id' => (string) Str::uuid(),
            'outcome' => 'failure',
            'safe_metadata' => [
                'user_token_id' => '12345',
            ],
        ]);
    }
}
