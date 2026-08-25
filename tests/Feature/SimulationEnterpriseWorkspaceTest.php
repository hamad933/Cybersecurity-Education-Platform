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
            ->where('enterprises.0.provenance', 'SIMULATED')
            ->has('enterprises.0.digital_twins', 2)
            ->where('enterprises.0.digital_twins.0.provenance', 'SIMULATED')
            ->has('enterprises.0.digital_twins.0.revisions', 1));

        $this->actingAs($owner)->get('/simulation/scenarios')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'scenarios')
            ->has('scenarios', 1)
            ->where('scenarios.0.environment_contract.schema', 'cep.simulation.environment-contract.v1')
            ->missing('scenarios.0.baseline_id')
            ->has('scenarios.0.preparation_targets', 1)
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
            ->where('runs.0.provenance', 'SIMULATED')
            ->where('runs.0.source_fixture', true)
            ->has('runs.0.operations', 1)
            ->has('runs.0.events')
            ->has('runs.0.snapshots'));

        $this->actingAs($owner)->get('/simulation/results')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'results')
            ->has('results', 1)
            ->where('results.0.outcome', 'PARTIAL')
            ->where('results.0.run_lifecycle', 'COMPLETED')
            ->where('results.0.provenance', 'SIMULATED')
            ->where('results.0.source_fixture', true)
            ->where('results.0.candidate_evidence_handoff.status', 'READY_FOR_INTAKE'));
    }

    #[Test]
    public function representative_run_actions_are_server_authoritative(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');
        $baselineId = (string) DB::table('simulation_baselines')->value('id');

        $this->actingAs($owner)->post("/simulation/scenarios/{$scenarioId}/runs", ['baseline_id' => $baselineId, 'seed' => 77, 'mode' => 'TEAM'])
            ->assertRedirect(route('cep.simulation.runs'));
        $runId = (string) DB::table('simulation_runs')->where('seed', 77)->value('id');
        $this->assertDatabaseHas('simulation_runs', ['id' => $runId, 'lifecycle' => 'PREPARING', 'run_type' => 'Scenario Run']);

        $this->actingAs($owner)->post("/simulation/runs/{$runId}/ready")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/start")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/operations", [
            'operation_key' => 'workspace-operation-001',
            'verb' => 'SET_CONTROL_STATE',
            'target' => 'IDENTITY_MFA',
            'value' => false,
        ])->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/pause")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/resume")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/snapshot")->assertRedirect();
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/complete")->assertRedirect();

        $this->assertDatabaseHas('simulation_runs', ['id' => $runId, 'lifecycle' => 'COMPLETED']);
        $this->assertDatabaseHas('simulation_run_operations', ['run_id' => $runId, 'actor_id' => $owner->id]);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $runId, 'event_type' => 'RUN_COMPLETED', 'actor_id' => $owner->id]);
        $this->assertDatabaseCount('simulation_run_results', 1);

        $this->actingAs($owner)->post("/simulation/runs/{$runId}/result", [
            'outcome' => 'NOT_EVALUATED',
            'summary_ar' => 'تم ختم الوقائع التشغيلية دون تحويل حالة التشغيل إلى حكم نتيجة.',
        ])->assertRedirect(route('cep.simulation.results'));

        $this->assertDatabaseHas('simulation_run_results', ['run_id' => $runId, 'outcome' => 'NOT_EVALUATED']);
        $resultId = (string) DB::table('simulation_run_results')->where('run_id', $runId)->value('id');
        $this->actingAs($owner)->post("/simulation/results/{$resultId}/replay-compare")->assertRedirect(route('cep.simulation.results'));
        $this->assertDatabaseHas('simulation_result_replay_compares', ['result_id' => $resultId, 'integrity_match' => true, 'actor_id' => $owner->id]);
        $this->actingAs($owner)->from('/simulation/results')->post("/simulation/results/{$resultId}/candidate-evidence-handoff", [
            'claim_ar' => 'مرشح لا يجوز أن يشير إلى أثر غير مختوم.',
            'artifact_refs' => ['external://not-sealed'],
            'intake_contract_ref' => 'progress-evidence-intake:v1',
        ])->assertRedirect('/simulation/results')->assertSessionHasErrors('simulation');
        $this->assertDatabaseMissing('simulation_candidate_evidence_handoffs', ['result_id' => $resultId]);
        $this->actingAs($owner)->post("/simulation/results/{$resultId}/candidate-evidence-handoff", [
            'claim_ar' => 'مرشح دليل محاكاة يخضع لعملية الاستقبال المنفصلة.',
            'artifact_refs' => [],
            'intake_contract_ref' => 'progress-evidence-intake:v1',
        ])->assertRedirect(route('cep.simulation.results'));
        $this->assertDatabaseHas('simulation_candidate_evidence_handoffs', ['result_id' => $resultId, 'created_by' => $owner->id, 'provenance' => 'SIMULATED']);
        $this->assertDatabaseCount('evidence_records', 0);
    }

    #[Test]
    public function invalid_transition_returns_a_bounded_error_without_state_mutation(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');
        $baselineId = (string) DB::table('simulation_baselines')->value('id');
        $this->actingAs($owner)->post("/simulation/scenarios/{$scenarioId}/runs", ['baseline_id' => $baselineId, 'seed' => 99, 'mode' => 'GUIDED']);
        $runId = (string) DB::table('simulation_runs')->where('seed', 99)->value('id');
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/ready");
        $this->actingAs($owner)->post("/simulation/runs/{$runId}/start");
        $eventCount = DB::table('simulation_run_events')->where('run_id', $runId)->count();

        $this->actingAs($owner)->from('/simulation/runs')->post("/simulation/runs/{$runId}/start")
            ->assertRedirect('/simulation/runs')
            ->assertSessionHasErrors('simulation');
        $this->assertDatabaseHas('simulation_runs', ['id' => $runId, 'lifecycle' => 'RUNNING']);
        $this->assertSame($eventCount, DB::table('simulation_run_events')->where('run_id', $runId)->count());
    }

    #[Test]
    public function result_read_uses_sealed_lifecycle_truth_instead_of_live_run_state(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $runId = (string) DB::table('simulation_runs')->value('id');
        DB::table('simulation_runs')->where('id', $runId)->update(['lifecycle' => 'STOPPED']);

        $this->actingAs($owner)->get('/simulation/results')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('results.0.run_lifecycle', 'COMPLETED')
            ->where('results.0.sealed_payload.run_lifecycle', 'COMPLETED'));
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
