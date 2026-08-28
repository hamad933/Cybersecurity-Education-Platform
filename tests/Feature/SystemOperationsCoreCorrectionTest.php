<?php

namespace Tests\Feature;

use App\Modules\Platform\Health\FoundationHealth;
use App\Modules\Platform\Processing\ProcessingRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\OwnerAccount; // Or User model? Let's check auth.

class SystemOperationsCoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_controller_reports_degraded_on_failure(): void
    {
        $mock = $this->mock(FoundationHealth::class);
        $mock->shouldReceive('summaryChecks')->once()->andReturn([
            'database' => 'failed',
        ]);

        $response = $this->getJson('/health/live');
        $response->assertStatus(503)
                 ->assertJson(['status' => 'degraded', 'checks' => ['database' => 'failed']]);
    }

    public function test_liveness_controller_reports_ok_on_success(): void
    {
        $mock = $this->mock(FoundationHealth::class);
        $mock->shouldReceive('summaryChecks')->once()->andReturn([
            'database' => 'ok',
        ]);

        $response = $this->getJson('/health/live');
        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok', 'checks' => ['database' => 'ok']]);
    }
}
