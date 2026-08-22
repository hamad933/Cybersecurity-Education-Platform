<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimulationEnterpriseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function route_family_is_authenticated_and_reads_real_persisted_state(): void
    {
        foreach (['/simulation', '/simulation/scenarios', '/simulation/labs', '/simulation/runs', '/simulation/results'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();

        $this->actingAs($owner)->get('/simulation')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'enterprise')
            ->has('navigation', 5)
            ->has('enterprises', 1)
            ->where('enterprises.0.is_fixture', true)
            ->where('enterprises.0.digital_twin_revision.revision', 1)
            ->where('enterprises.0.baseline.revision', 1));

        $this->actingAs($owner)->get('/simulation/scenarios')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'scenarios')
            ->has('scenarios', 1)
            ->has('scenarios.0.lab_module_references', 1));

        $this->actingAs($owner)->get('/simulation/labs')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'labs')
            ->has('labs', 1));

        $this->actingAs($owner)->get('/simulation/runs')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'runs')
            ->has('runs', 1)
            ->where('runs.0.run_type', 'Scenario Run')
            ->where('runs.0.lifecycle', 'COMPLETED')
            ->has('runs.0.events')
            ->has('runs.0.snapshots'));

        $this->actingAs($owner)->get('/simulation/results')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'results')
            ->has('results', 1)
            ->where('results.0.outcome', 'PARTIAL')
            ->where('results.0.run_lifecycle', 'COMPLETED')
            ->where('results.0.candidate_evidence_handoff.status', 'READY_FOR_INTAKE'));
    }

    #[Test]
    public function representative_run_actions_are_server_authoritative(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');

        $this->actingAs($owner)->post("/simulation/scenarios/{$scenarioId}/runs", ['seed' => 77, 'mode' => 'TEAM'])
            ->assertRedirect(route('cep.simulation.runs'));
        $runId = (string) DB::table('simulation_runs')->where('seed', 77)->value('id');
        $this->assertDatabaseHas('simulation_runs', ['id' => $runId, 'lifecycle' => 'PREPARING', 'run_type' => 'Scenario Run']);

        $this->actingAs($owner)->post("/simulation/runs/{$runId}/ready")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/start")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/pause")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/resume")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/snapshot")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/complete")->assertRedirect();

        $this->assertDatabaseHas('simulation_runs', ['id' => $runId, 'lifecycle' => 'COMPLETED']);
        $this->assertDatabaseCount('simulation_run_results', 1);

        $this->actingAs($owner)->post("/simulation/runs/{$runId}/result", [
            'outcome' => 'NOT_EVALUATED',
            'summary_ar' => 'تم ختم الوقائع التشغيلية دون تحويل حالة التشغيل إلى حكم نتيجة.',
        ])->assertRedirect(route('cep.simulation.results'));

        $this->assertDatabaseHas('simulation_run_results', ['run_id' => $runId, 'outcome' => 'NOT_EVALUATED']);
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'W03 Owner',
            'w03-owner@example.test',
            'ReviewReady!Pass9',
            (string) Str::uuid7(),
        );
    }
}
