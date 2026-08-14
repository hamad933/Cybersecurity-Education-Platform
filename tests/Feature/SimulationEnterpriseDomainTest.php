<?php

namespace Tests\Feature;

use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimulationEnterpriseDomainTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function scenario_and_lab_are_distinct_and_scenario_lab_references_become_instances_not_standalone_runs(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);

        $scenario = DB::table('simulation_scenario_definitions')->firstOrFail();
        $lab = DB::table('simulation_lab_definitions')->firstOrFail();
        $run = DB::table('simulation_runs')->firstOrFail();

        $this->assertNotSame((string) $scenario->id, (string) $lab->id);
        $this->assertSame('Scenario Run', $run->run_type);
        $this->assertSame((string) $scenario->id, (string) $run->scenario_definition_id);
        $this->assertNull($run->standalone_lab_definition_id);
        $this->assertDatabaseCount('simulation_scenario_lab_references', 1);
        $this->assertDatabaseCount('simulation_run_lab_module_instances', 1);
        $instance = DB::table('simulation_run_lab_module_instances')->firstOrFail();
        $this->assertSame((string) $run->id, (string) $instance->run_id);
        $this->assertSame((string) $lab->id, (string) $instance->lab_definition_id);
    }

    #[Test]
    public function exactly_two_run_types_are_enforced_by_postgresql_and_lifecycle_is_separate_from_result_outcome(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $constraint = DB::selectOne("SELECT pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conname = 'sim_run_type_check'");
        $this->assertNotNull($constraint);
        $definition = (string) $constraint->definition;
        $this->assertStringContainsString('Standalone Lab Run', $definition);
        $this->assertStringContainsString('Scenario Run', $definition);

        $run = DB::table('simulation_runs')->firstOrFail();
        $result = DB::table('simulation_run_results')->where('run_id', $run->id)->firstOrFail();
        $this->assertSame('COMPLETED', $run->lifecycle);
        $this->assertSame('PARTIAL', $result->outcome);
        $this->assertNotContains($result->outcome, SimulationEnterpriseService::LIFECYCLES);
        $this->assertNotContains($run->lifecycle, SimulationEnterpriseService::OUTCOMES);
    }

    #[Test]
    public function standalone_and_scenario_runs_use_deterministic_traceable_internal_execution(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');

        $first = $simulation->prepareStandaloneLabRun($labId, 901, ['mode' => 'SOLO']);
        $simulation->markReady((string) $first['id']);
        $simulation->start((string) $first['id']);
        $simulation->completeInternalSimulation((string) $first['id']);

        $second = $simulation->prepareStandaloneLabRun($labId, 901, ['mode' => 'SOLO']);
        $simulation->markReady((string) $second['id']);
        $simulation->start((string) $second['id']);
        $simulation->completeInternalSimulation((string) $second['id']);

        $firstState = json_decode((string) DB::table('simulation_runs')->where('id', $first['id'])->value('runtime_state'), true, 512, JSON_THROW_ON_ERROR);
        $secondState = json_decode((string) DB::table('simulation_runs')->where('id', $second['id'])->value('runtime_state'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('INTERNAL_HIGH_FIDELITY_V1', $firstState['engine']);
        $this->assertTrue($firstState['validation']['traceable']);
        $this->assertTrue($firstState['validation']['deterministic']);
        $this->assertSame($firstState['trace_digest'], $secondState['trace_digest']);
        $this->assertSame($firstState['telemetry'], $secondState['telemetry']);
        $this->assertDatabaseHas('simulation_runs', ['id' => $first['id'], 'run_type' => 'Standalone Lab Run', 'lifecycle' => 'COMPLETED']);
        $this->assertGreaterThanOrEqual(5, DB::table('simulation_run_events')->where('run_id', $first['id'])->count());
        $this->assertGreaterThanOrEqual(2, DB::table('simulation_runtime_snapshots')->where('run_id', $first['id'])->count());
    }

    #[Test]
    public function each_result_belongs_to_exactly_one_run_and_cannot_be_resealed_or_overwritten(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $result = DB::table('simulation_run_results')->firstOrFail();

        $this->expectException(DomainException::class);
        $simulation->sealResult((string) $result->run_id, 'ACHIEVED', 'محاولة إعادة ختم غير مسموحة.');
    }

    #[Test]
    public function postgresql_trigger_rejects_direct_mutation_of_sealed_result_history(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $result = DB::table('simulation_run_results')->firstOrFail();

        try {
            DB::table('simulation_run_results')->where('id', $result->id)->update(['outcome' => 'ACHIEVED']);
            $this->fail('Sealed Result update unexpectedly succeeded.');
        } catch (QueryException $exception) {
            $this->assertSame('55000', (string) $exception->getCode());
        }
    }

    #[Test]
    public function candidate_evidence_handoff_stops_at_the_w03_boundary(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $handoff = DB::table('simulation_candidate_evidence_handoffs')->firstOrFail();

        $this->assertSame('READY_FOR_INTAKE', $handoff->status);
        $this->assertSame('progress-evidence-intake:v1', $handoff->intake_contract_ref);
        $this->assertDatabaseCount('evidence_records', 0);
    }
}
