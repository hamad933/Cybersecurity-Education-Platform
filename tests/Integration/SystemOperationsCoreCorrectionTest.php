<?php

namespace Tests\Integration;

use App\Modules\Platform\Processing\ProcessingRun;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemOperationsCoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_run_retry_route_transitions_to_pending(): void
    {
        $actor = OwnerAccount::query()->create([
            'id' => (string) Str::uuid(),
            'display_name' => 'Admin',
            'email' => 'admin@example.com',
            'password_hash' => 'hash',
        ]);

        $run = ProcessingRun::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test_job',
            'input_digest' => str_repeat('a', 64),
            'idempotency_key' => 'idemp_1',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'error_category' => 'WORKER_CRASHED',
            'safe_error_message' => 'Test error',
        ]);

        $response = $this->actingAs($actor)->postJson("/system/processing/runs/{$run->id}/retry");
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $run->refresh();
        $this->assertSame('pending', $run->status);
        $this->assertNull($run->started_at);
        $this->assertNull($run->completed_at);
        $this->assertNull($run->error_category);
        $this->assertNull($run->safe_error_message);
    }
}
