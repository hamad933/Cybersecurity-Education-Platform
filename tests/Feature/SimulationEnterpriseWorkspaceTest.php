<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use App\Modules\Simulator\RunResult\RunResultCapability;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use stdClass;
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
            ->where('runs.0.definition_digest', fn (string $digest): bool => strlen($digest) === 64)
            ->has('runs.0.operations', 1)
            ->has('runs.0.events')
            ->has('runs.0.snapshots')
            ->where('run_preflight.status', 'READY')
            ->where('run_preflight.execution_model', 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION')
            ->where('run_preflight.scenario_definitions.0.status', 'READY')
            ->where('run_preflight.scenario_definitions.0.targets.0.missing_capabilities', [])
            ->where('run_preflight.lab_definitions.0.status', 'READY')
            ->where('run_workspace.mode', 'operations'));

        $this->actingAs($owner)->get('/simulation/results')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SimulationEnterprise/Workspace')
            ->where('section', 'results')
            ->has('results', 1)
            ->where('results.0.outcome', 'PARTIAL')
            ->where('results.0.run_lifecycle', 'COMPLETED')
            ->where('results.0.provenance', 'SIMULATED')
            ->where('results.0.source_fixture', true)
            ->where('results.0.analytics.status', 'INITIAL_REVISION_REQUIRED')
            ->where('results.0.analytics.overview.lineage.status', 'INITIAL_REVISION_REQUIRED')
            ->where('results.0.legacy_history.candidate_evidence_handoff.status', 'READY_FOR_INTAKE')
            ->where('results_workspace.mode', 'overview'));
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
        $capability = app(RunResultCapability::class);
        $revisionId = $capability->createResultRevision($resultId, []);
        $candidateProjection = $capability->projectResultAnalytics($resultId)['candidate_evidence'];
        $this->assertSame('ZERO_WRITE_SOURCE_PREVIEW', $candidateProjection['write_behavior']);
        $this->assertSame('NOT_CREATED_OR_CLAIMED', $candidateProjection['w04_state']);
        $this->assertSame($revisionId, $candidateProjection['envelope']['effective_revision_id']);
        $legacyHandoffCount = DB::table('simulation_candidate_evidence_handoffs')->count();
        $legacyCompareCount = DB::table('simulation_result_replay_compares')->count();
        $evidenceCount = DB::table('evidence_records')->count();

        $this->actingAs($owner)
            ->get('/simulation/results?mode=candidate-evidence&result='.$resultId)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results_workspace.mode', 'candidate-evidence')
                ->where('results_workspace.selected_result_id', $resultId));

        $this->assertSame($legacyHandoffCount, DB::table('simulation_candidate_evidence_handoffs')->count());
        $this->assertSame($legacyCompareCount, DB::table('simulation_result_replay_compares')->count());
        $this->assertSame($evidenceCount, DB::table('evidence_records')->count());
        $this->assertDatabaseMissing('simulation_candidate_evidence_handoffs', ['result_id' => $resultId]);
    }

    #[Test]
    public function results_analytics_reads_are_zero_write_source_traceable_and_compare_distinct_runs(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $actor = (string) $owner->id;
        $capability = app(RunResultCapability::class);
        $simulation = app(SimulationEnterpriseService::class);

        $firstResultId = (string) DB::table('simulation_run_results')->value('id');
        $firstRevisionId = $capability->createResultRevision($firstResultId, []);

        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');
        $baselineId = (string) DB::table('simulation_baselines')->value('id');
        $secondRun = $simulation->prepareScenarioRun($scenarioId, $baselineId, 808, ['mode' => 'GUIDED'], $actor);
        $simulation->markReady((string) $secondRun['id'], $actor);
        $simulation->start((string) $secondRun['id'], $actor);
        $simulation->applyOperation((string) $secondRun['id'], [
            'operation_key' => 'analytics-operation-808',
            'verb' => 'SET_CONTROL_STATE',
            'target' => 'IDENTITY_MFA',
            'value' => true,
        ], $actor);
        $simulation->completeInternalSimulation((string) $secondRun['id'], $actor);
        $secondResult = $simulation->sealResult(
            (string) $secondRun['id'],
            'INCONCLUSIVE',
            'تعليق مختوم لا يُحوّل إلى سبب أو درس.',
            null,
            $actor,
        );
        $secondResultId = (string) $secondResult['id'];
        $secondRevisionId = $capability->createResultRevision($secondResultId, []);
        $firstAnalytics = $capability->projectResultAnalytics($firstResultId);
        $this->assertSame('READY', $firstAnalytics['overview']['lineage']['status']);
        $this->assertSame('READY', $firstAnalytics['replay']['status']);
        $this->assertSame('ZERO_WRITE_PROJECTION', $firstAnalytics['replay']['write_behavior']);
        $this->assertSame('SEALED_HISTORY_AND_EFFECTIVE_REVISION_ONLY', $firstAnalytics['aar']['source_policy']);
        $this->assertSame('UNAVAILABLE_FROM_SEALED_TRUTH', $firstAnalytics['aar']['unavailable_sections'][0]['reason']);

        $watchedTables = [
            'simulation_run_result_revisions',
            'simulation_result_replay_compares',
            'simulation_candidate_evidence_handoffs',
            'evidence_records',
        ];
        $before = collect($watchedTables)->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->count(),
        ]);

        $this->actingAs($owner)
            ->get('/simulation/results?mode=replay&result='.$firstResultId)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results_workspace.mode', 'replay')
                ->where('results_workspace.selected_result_id', $firstResultId)
                ->has('results', 2));

        $this->actingAs($owner)
            ->get('/simulation/results?mode=aar&result='.$firstResultId)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results_workspace.mode', 'aar')
                ->where('results_workspace.selected_result_id', $firstResultId)
                ->has('results', 2));

        $query = http_build_query([
            'mode' => 'compare',
            'compare' => [$firstResultId, $secondResultId],
        ]);
        $firstRunId = (string) DB::table('simulation_run_results')
            ->where('id', $firstResultId)
            ->value('run_id');
        $this->actingAs($owner)
            ->get('/simulation/results?'.$query)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results_workspace.mode', 'compare')
                ->where('results_workspace.compare.selection_valid', true)
                ->where('results_workspace.compare.selected_result_ids', [$firstResultId, $secondResultId])
                ->where('results_workspace.compare.selected_run_ids.0', $firstRunId)
                ->where('results_workspace.compare.selected_run_ids.1', (string) $secondRun['id'])
                ->where('results_workspace.compare.dimensions.1.key', 'score')
                ->where('results_workspace.compare.dimensions.1.values.1.display', 'N/A')
                ->where('results_workspace.compare.write_behavior', 'ZERO_WRITE_PROJECTION'));

        $duplicateQuery = http_build_query([
            'mode' => 'compare',
            'compare' => [$firstResultId, $firstResultId],
        ]);
        $this->actingAs($owner)
            ->get('/simulation/results?'.$duplicateQuery)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results_workspace.compare.status', 'UNAVAILABLE')
                ->where('results_workspace.compare.selection_valid', false)
                ->where('results_workspace.compare.reason', 'COMPARE_DUPLICATE_CANONICAL_RESULT_LINEAGE'));

        $this->assertNotSame($firstRevisionId, $secondRevisionId);
        foreach ($watchedTables as $table) {
            $this->assertSame($before[$table], DB::table($table)->count(), "{$table} changed during zero-write Results reads.");
        }
    }

    #[Test]
    public function result_projection_fails_closed_when_revision_lineage_forks(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $owner = $this->owner();
        $resultId = (string) DB::table('simulation_run_results')->value('id');
        $capability = app(RunResultCapability::class);
        $baseRevision = $capability->createResultRevision($resultId, []);
        $capability->createResultRevision(
            $resultId,
            ['outcome' => 'ACHIEVED'],
            'controller-c',
            $baseRevision,
            'تصحيح أول',
        );
        $capability->createResultRevision(
            $resultId,
            ['outcome' => 'NOT_ACHIEVED'],
            'controller-c',
            $baseRevision,
            'تصحيح متعارض',
        );
        $revisionCount = DB::table('simulation_run_result_revisions')->count();

        $this->actingAs($owner)
            ->get('/simulation/results?mode=overview&result='.$resultId)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('results.0.analytics.status', 'LINEAGE_RECONCILIATION_REQUIRED')
                ->where('results.0.analytics.overview.lineage.status', 'LINEAGE_RECONCILIATION_REQUIRED')
                ->where('results.0.analytics.overview.effective', null));

        $this->assertSame($revisionCount, DB::table('simulation_run_result_revisions')->count());
    }

    #[Test]
    public function zero_operation_replay_still_rejects_an_applied_operation_event(): void
    {
        $canonicalResult = new stdClass;
        $canonicalResult->sealed_payload = json_encode(['operations' => []], JSON_THROW_ON_ERROR);
        $canonicalResult->replay_timeline = json_encode([[
            'sequence' => 1,
            'event_type' => 'SIMULATION_OPERATION_APPLIED',
            'payload' => ['operation_key' => 'zero-op-invalid-reference'],
            'actor_id' => 'SYSTEM:TEST',
            'occurred_at' => '2026-09-02T00:00:00Z',
        ]], JSON_THROW_ON_ERROR);

        $method = new ReflectionMethod(RunResultCapability::class, 'projectReplayAnalytics');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeline references operations but operations list is empty.');
        $method->invoke(app(RunResultCapability::class), $canonicalResult);
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
