<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditRecord;
use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\Platform\Release\ReleaseReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemOperationsCoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function createActor(): OwnerAccount
    {
        return OwnerAccount::query()->create([
            'id' => (string) Str::uuid(),
            'display_name' => 'SysOps Admin',
            'email' => 'sysops@example.com',
            'password_hash' => 'dummy_hash',
        ]);
    }

    public function test_processing_run_retry_route_transitions_to_pending_and_audits(): void
    {
        $actor = $this->createActor();

        $run = ProcessingRun::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'document.parse',
            'input_digest' => str_repeat('a', 64),
            'idempotency_key' => 'idemp_retry_1',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'error_category' => 'WORKER_CRASHED',
            'safe_error_message' => 'Test worker error',
        ]);

        $response = $this->actingAs($actor)->post("/system/processing/runs/{$run->id}/retry");
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $run->refresh();
        $this->assertSame('pending', $run->status);
        $this->assertNull($run->started_at);
        $this->assertNull($run->completed_at);
        $this->assertNull($run->error_category);
        $this->assertNull($run->safe_error_message);

        // Verify audit event
        $audit = AuditRecord::query()->where('action', 'processing.run.retried')->first();
        $this->assertNotNull($audit);
        $this->assertSame((string) $actor->id, (string) $audit->actor_identifier);
        $this->assertSame((string) $run->id, (string) $audit->target_identifier);
        $this->assertSame('success', $audit->outcome);
    }

    public function test_processing_run_retry_rejected_when_max_attempts_exhausted(): void
    {
        $actor = $this->createActor();

        $run = ProcessingRun::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'document.parse',
            'input_digest' => str_repeat('b', 64),
            'idempotency_key' => 'idemp_retry_exhausted',
            'status' => 'failed',
            'attempt_count' => 3,
            'max_attempts' => 3,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($actor)->post("/system/processing/runs/{$run->id}/retry");
        $response->assertSessionHasErrors('processing');

        $run->refresh();
        $this->assertSame('failed', $run->status);
    }

    public function test_processing_run_retry_rejected_for_invalid_statuses(): void
    {
        $actor = $this->createActor();

        $run = ProcessingRun::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'document.parse',
            'input_digest' => str_repeat('c', 64),
            'idempotency_key' => 'idemp_retry_invalid',
            'status' => 'completed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($actor)->post("/system/processing/runs/{$run->id}/retry");
        $response->assertSessionHasErrors('processing');

        $run->refresh();
        $this->assertSame('completed', $run->status);
    }

    public function test_processing_run_cancellation_and_audit(): void
    {
        $actor = $this->createActor();

        $run = ProcessingRun::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'document.parse',
            'input_digest' => str_repeat('d', 64),
            'idempotency_key' => 'idemp_cancel_1',
            'status' => 'running',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'started_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($actor)->post("/system/processing/runs/{$run->id}/cancel");
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $run->refresh();
        $this->assertSame('cancelled', $run->status);
        $this->assertNotNull($run->cancelled_at);

        $audit = AuditRecord::query()->where('action', 'processing.run.cancelled')->first();
        $this->assertNotNull($audit);
        $this->assertSame((string) $actor->id, (string) $audit->actor_identifier);
        $this->assertSame((string) $run->id, (string) $audit->target_identifier);
    }

    public function test_release_readiness_includes_evidence_acceptance(): void
    {
        $readiness = app(ReleaseReadiness::class);
        $eval = $readiness->evaluate();

        $this->assertArrayHasKey('environment', $eval['checks']);
        $this->assertArrayHasKey('database', $eval['checks']);

        if (Schema::hasTable('imported_evidence_records')) {
            $this->assertArrayHasKey('evidence_acceptance', $eval['checks']);
        }
    }
}
