<?php

namespace Tests\Feature;

use App\Modules\Enterprise\Application\SimulationEnterpriseFixtureWriter;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Database\Seeders\SimulationEnterpriseWave1Seeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimulationEnterpriseDomainTest extends TestCase
{
    use RefreshDatabase;

    private const ACTOR = 'SYSTEM:W03_DOMAIN_TEST';

    #[Test]
    public function one_enterprise_owns_two_digital_twins_with_independent_revision_streams(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $enterpriseId = (string) DB::table('simulation_enterprises')->value('id');
        $twins = DB::table('simulation_digital_twins')->where('enterprise_id', $enterpriseId)->orderBy('slug')->get();
        $this->assertCount(2, $twins);
        $this->assertDatabaseCount('simulation_enterprises', 1);

        $writer = app(SimulationEnterpriseFixtureWriter::class);
        foreach ($twins as $twin) {
            $revision = $writer->publishDigitalTwinRevision(
                $enterpriseId,
                (string) $twin->id,
                ['nodes' => [['id' => 'REVISION-2', 'kind' => 'bounded-node']], 'links' => []],
                ['behavior' => 'SIMULATED_REVISION_2'],
                self::ACTOR,
            );
            $this->assertSame(2, (int) $revision['revision']);

            $previousBaselineRevision = (int) DB::table('simulation_baselines')
                ->where('digital_twin_id', $twin->id)
                ->max('revision');
            $baseline = $writer->publishBaseline(
                $enterpriseId,
                (string) $twin->id,
                (string) $revision['id'],
                ['identity_policy' => ['mfa_required' => false]],
                self::ACTOR,
            );
            $this->assertSame($previousBaselineRevision + 1, (int) $baseline['revision']);
            $this->assertSame((string) $twin->id, (string) $baseline['digital_twin_id']);
        }

        foreach ($twins as $twin) {
            $this->assertSame([1, 2], DB::table('simulation_digital_twin_revisions')->where('digital_twin_id', $twin->id)->orderBy('revision')->pluck('revision')->map(fn (mixed $value): int => (int) $value)->all());
        }
    }

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
        $this->assertDatabaseCount('simulation_run_lab_module_instances', 1);
    }

    #[Test]
    public function scenario_is_target_agnostic_and_preparation_materializes_a_snapshot_then_checkpoint(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $this->assertFalse(Schema::hasColumn('simulation_scenario_definitions', 'enterprise_id'));
        $this->assertFalse(Schema::hasColumn('simulation_scenario_definitions', 'baseline_id'));
        $this->assertTrue(Schema::hasColumn('simulation_scenario_definitions', 'environment_contract'));

        $scenario = DB::table('simulation_scenario_definitions')->firstOrFail();
        $contract = json_decode((string) $scenario->environment_contract, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('cep.simulation.environment-contract.v1', $contract['schema']);
        $this->assertArrayNotHasKey('baseline_id', $contract);
        $this->assertArrayNotHasKey('digital_twin_id', $contract);

        $enterpriseId = (string) DB::table('simulation_enterprises')->value('id');
        $secondaryTwin = DB::table('simulation_digital_twins')->where('slug', 'recovery-validation-twin')->firstOrFail();
        $secondaryRevision = DB::table('simulation_digital_twin_revisions')->where('digital_twin_id', $secondaryTwin->id)->firstOrFail();
        $target = app(SimulationEnterpriseFixtureWriter::class)->publishBaseline(
            $enterpriseId,
            (string) $secondaryTwin->id,
            (string) $secondaryRevision->id,
            [
                'capabilities' => ['IDENTITY_POLICY', 'APPLICATION_STATE', 'INTERNAL_TELEMETRY'],
                'identity_policy' => ['mfa_required' => true],
            ],
            self::ACTOR,
        );

        $run = app(SimulationEnterpriseService::class)->prepareScenarioRun(
            (string) $scenario->id,
            (string) $target['id'],
            903,
            ['mode' => 'GUIDED'],
            self::ACTOR,
        );

        $this->assertSame((string) $target['id'], (string) $run['baseline_id']);
        $this->assertSame((string) $secondaryTwin->id, (string) $run['digital_twin_id']);
        $snapshot = DB::table('simulation_runtime_snapshots')->where('run_id', $run['id'])->firstOrFail();
        $checkpoint = DB::table('simulation_runtime_checkpoints')->where('run_id', $run['id'])->firstOrFail();
        $this->assertSame('RUN_PREPARATION', $snapshot->snapshot_kind);
        $this->assertSame((string) $snapshot->id, (string) $checkpoint->source_snapshot_id);
        $this->assertSame((string) $snapshot->state_digest, (string) $checkpoint->state_digest);
        $this->assertSame((string) $snapshot->state, (string) $checkpoint->state);
        $this->assertTrue((bool) $checkpoint->restorable);
    }

    #[Test]
    public function scenario_environment_contract_rejects_fixed_targets_and_incompatible_preparation_targets(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);

        try {
            $simulation->publishScenario(
                'invalid-fixed-target',
                'سيناريو غير صالح',
                [
                    'schema' => 'cep.simulation.environment-contract.v1',
                    'execution_model' => 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
                    'required_capabilities' => ['IDENTITY_POLICY'],
                    'baseline_id' => (string) Str::uuid7(),
                ],
                ['phases' => []],
                [],
                self::ACTOR,
            );
            $this->fail('A fixed Scenario execution target unexpectedly passed validation.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseMissing('simulation_scenario_definitions', ['slug' => 'invalid-fixed-target']);
        }

        $scenarioId = (string) DB::table('simulation_scenario_definitions')->value('id');
        $enterpriseId = (string) DB::table('simulation_enterprises')->value('id');
        $writer = app(SimulationEnterpriseFixtureWriter::class);
        $twin = $writer->createDigitalTwin($enterpriseId, 'incompatible-target', 'هدف غير متوافق', self::ACTOR);
        $revision = $writer->publishDigitalTwinRevision(
            $enterpriseId,
            (string) $twin['id'],
            ['nodes' => [], 'links' => []],
            ['behavior' => 'SIMULATED'],
            self::ACTOR,
        );
        $baseline = $writer->publishBaseline(
            $enterpriseId,
            (string) $twin['id'],
            (string) $revision['id'],
            ['capabilities' => ['APPLICATION_STATE']],
            self::ACTOR,
        );

        $this->expectException(LogicException::class);
        $simulation->prepareScenarioRun($scenarioId, (string) $baseline['id'], 904, ['mode' => 'SOLO'], self::ACTOR);
    }

    #[Test]
    public function exactly_two_run_types_are_enforced_and_lifecycle_is_separate_from_result_outcome(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $constraint = DB::selectOne("SELECT pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conname = 'sim_run_type_check'");
        $this->assertNotNull($constraint);
        $this->assertStringContainsString('Standalone Lab Run', (string) $constraint->definition);
        $this->assertStringContainsString('Scenario Run', (string) $constraint->definition);
        $run = DB::table('simulation_runs')->firstOrFail();
        $result = DB::table('simulation_run_results')->where('run_id', $run->id)->firstOrFail();
        $this->assertSame('COMPLETED', $run->lifecycle);
        $this->assertSame('PARTIAL', $result->outcome);
        $this->assertNotContains($result->outcome, SimulationEnterpriseService::LIFECYCLES);
        $this->assertNotContains($run->lifecycle, SimulationEnterpriseService::OUTCOMES);
    }

    #[Test]
    public function bounded_operation_mutates_runtime_state_and_derives_actor_audited_telemetry(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $run = $simulation->prepareStandaloneLabRun($labId, 901, ['mode' => 'SOLO'], self::ACTOR);
        $simulation->markReady((string) $run['id'], self::ACTOR);
        $simulation->start((string) $run['id'], self::ACTOR);
        $operation = $simulation->applyOperation((string) $run['id'], [
            'operation_key' => 'domain-operation-001',
            'verb' => 'SET_CONTROL_STATE',
            'target' => 'IDENTITY_MFA',
            'value' => false,
        ], self::ACTOR);

        $state = json_decode((string) DB::table('simulation_runs')->where('id', $run['id'])->value('runtime_state'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($state['simulated_state']['identity_policy']['mfa_required']);
        $this->assertTrue(json_decode((string) $operation['telemetry'], true, 512, JSON_THROW_ON_ERROR)['state_changed']);
        $this->assertDatabaseHas('simulation_run_events', ['run_id' => $run['id'], 'event_type' => 'SIMULATION_OPERATION_APPLIED', 'actor_id' => self::ACTOR]);
        $this->assertDatabaseHas('simulation_runtime_snapshots', ['run_id' => $run['id'], 'captured_by' => self::ACTOR]);
    }

    #[Test]
    public function semantic_replay_reconstructs_sealed_state_and_compares_integrity(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $resultId = (string) DB::table('simulation_run_results')->value('id');
        $compare = $simulation->replayAndCompareResult($resultId, self::ACTOR);
        $reconstruction = json_decode((string) $compare['reconstruction'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue((bool) $compare['integrity_match']);
        $this->assertNotEmpty($reconstruction['sealed_lineage']);
        $this->assertNotEmpty($reconstruction['timeline']);
        $this->assertNotEmpty($reconstruction['artifacts']);
        $this->assertNotEmpty($reconstruction['runtime_snapshots']);
        $this->assertTrue($reconstruction['checks']['sealed_result_digest_valid']);
        $this->assertSame(self::ACTOR, $compare['actor_id']);
    }

    #[Test]
    public function semantic_replay_rejects_a_self_consistent_snapshot_at_the_wrong_event_sequence(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $labId = (string) DB::table('simulation_lab_definitions')->value('id');
        $run = $simulation->prepareStandaloneLabRun($labId, 902, ['mode' => 'SOLO'], self::ACTOR);
        $simulation->markReady((string) $run['id'], self::ACTOR);
        $simulation->start((string) $run['id'], self::ACTOR);
        $simulation->applyOperation((string) $run['id'], [
            'operation_key' => 'misplaced-snapshot-operation-001',
            'verb' => 'SET_CONTROL_STATE',
            'target' => 'IDENTITY_MFA',
            'value' => false,
        ], self::ACTOR);

        $initialSnapshot = DB::table('simulation_runtime_snapshots')->where('run_id', $run['id'])->orderBy('sequence')->firstOrFail();
        $nextSequence = (int) DB::table('simulation_runtime_snapshots')->where('run_id', $run['id'])->max('sequence') + 1;
        DB::table('simulation_runtime_snapshots')->insert([
            'id' => (string) Str::uuid7(),
            'run_id' => $run['id'],
            'sequence' => $nextSequence,
            'event_sequence' => 4,
            'digital_twin_id' => $run['digital_twin_id'],
            'digital_twin_revision_id' => $run['digital_twin_revision_id'],
            'baseline_id' => $run['baseline_id'],
            'snapshot_kind' => 'MANUAL',
            'state' => $initialSnapshot->state,
            'state_digest' => $initialSnapshot->state_digest,
            'captured_by' => self::ACTOR,
            'captured_at' => now(),
            'created_at' => now(),
        ]);

        $simulation->completeInternalSimulation((string) $run['id'], self::ACTOR);
        $result = $simulation->sealResult((string) $run['id'], 'PARTIAL', 'لقطة تاريخية في موضع خاطئ.', null, self::ACTOR);
        $compare = $simulation->replayAndCompareResult((string) $result['id'], self::ACTOR);
        $reconstruction = json_decode((string) $compare['reconstruction'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse((bool) $compare['integrity_match']);
        $this->assertFalse($reconstruction['checks']['snapshot_integrity']);
    }

    #[Test]
    public function simulated_fixture_provenance_propagates_to_run_result_and_stable_handoff(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $run = DB::table('simulation_runs')->firstOrFail();
        $result = DB::table('simulation_run_results')->firstOrFail();
        $handoff = DB::table('simulation_candidate_evidence_handoffs')->firstOrFail();
        $this->assertSame('SIMULATED', $run->provenance);
        $this->assertTrue((bool) $run->source_fixture);
        $this->assertSame('SIMULATED', $result->provenance);
        $this->assertTrue((bool) $result->source_fixture);
        $this->assertSame((string) $result->result_digest, (string) $handoff->source_result_digest);
        $this->assertSame('SIMULATED', $handoff->provenance);
        $this->assertTrue((bool) $handoff->source_fixture);
        $this->assertSame('SYSTEM:SIMULATION_WAVE1_SEEDER', $handoff->created_by);
        $this->assertSame(64, strlen((string) $handoff->manifest_digest));
        $this->assertSame(0, DB::table('simulation_run_events')->where('actor_id', '!=', 'SYSTEM:SIMULATION_WAVE1_SEEDER')->count());
        $this->assertDatabaseCount('evidence_records', 0);
    }

    #[DataProvider('publishedDefinitionTables')]
    #[Test]
    public function published_definitions_are_immutable_in_postgresql(string $table, string $mutation): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $id = DB::table($table)->value('id');
        $this->expectException(QueryException::class);
        if ($mutation === 'UPDATE') {
            DB::table($table)->where('id', $id)->update(['status' => 'DRAFT']);

            return;
        }
        DB::table($table)->where('id', $id)->delete();
    }

    /** @return array<string,array{string,string}> */
    public static function publishedDefinitionTables(): array
    {
        return [
            'Digital Twin Revision UPDATE' => ['simulation_digital_twin_revisions', 'UPDATE'],
            'Digital Twin Revision DELETE' => ['simulation_digital_twin_revisions', 'DELETE'],
            'Baseline UPDATE' => ['simulation_baselines', 'UPDATE'],
            'Baseline DELETE' => ['simulation_baselines', 'DELETE'],
            'Scenario UPDATE' => ['simulation_scenario_definitions', 'UPDATE'],
            'Scenario DELETE' => ['simulation_scenario_definitions', 'DELETE'],
            'Lab UPDATE' => ['simulation_lab_definitions', 'UPDATE'],
            'Lab DELETE' => ['simulation_lab_definitions', 'DELETE'],
        ];
    }

    #[DataProvider('handoffMutations')]
    #[Test]
    public function candidate_evidence_handoff_is_append_only(string $mutation): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $id = DB::table('simulation_candidate_evidence_handoffs')->value('id');
        $this->expectException(QueryException::class);
        if ($mutation === 'UPDATE') {
            DB::table('simulation_candidate_evidence_handoffs')->where('id', $id)->update(['status' => 'HANDED_OFF']);

            return;
        }
        DB::table('simulation_candidate_evidence_handoffs')->where('id', $id)->delete();
    }

    /** @return array<string,array{string}> */
    public static function handoffMutations(): array
    {
        return [
            'UPDATE' => ['UPDATE'],
            'DELETE' => ['DELETE'],
        ];
    }

    #[DataProvider('replayComparisonMutations')]
    #[Test]
    public function replay_comparisons_are_append_only(string $mutation): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $resultId = (string) DB::table('simulation_run_results')->value('id');
        $compare = $simulation->replayAndCompareResult($resultId, self::ACTOR);

        $this->expectException(QueryException::class);
        if ($mutation === 'UPDATE') {
            DB::table('simulation_result_replay_compares')->where('id', $compare['id'])->update(['actor_id' => 'SYSTEM:TAMPERED']);

            return;
        }
        DB::table('simulation_result_replay_compares')->where('id', $compare['id'])->delete();
    }

    /** @return array<string,array{string}> */
    public static function replayComparisonMutations(): array
    {
        return [
            'UPDATE' => ['UPDATE'],
            'DELETE' => ['DELETE'],
        ];
    }

    #[DataProvider('historicalAuditTables')]
    #[Test]
    public function historical_run_audit_rows_are_append_only(string $table, string $actorColumn): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $id = DB::table($table)->value('id');
        $this->expectException(QueryException::class);
        DB::table($table)->where('id', $id)->update([$actorColumn => 'SYSTEM:TAMPERED']);
    }

    /** @return array<string,array{string,string}> */
    public static function historicalAuditTables(): array
    {
        return [
            'Operation' => ['simulation_run_operations', 'actor_id'],
            'Event' => ['simulation_run_events', 'actor_id'],
            'Runtime Snapshot' => ['simulation_runtime_snapshots', 'captured_by'],
            'Runtime Checkpoint' => ['simulation_runtime_checkpoints', 'created_by'],
        ];
    }

    #[Test]
    public function sealed_result_cannot_be_resealed_or_directly_mutated(): void
    {
        $this->seed(SimulationEnterpriseWave1Seeder::class);
        $simulation = app(SimulationEnterpriseService::class);
        $result = DB::table('simulation_run_results')->firstOrFail();
        try {
            $simulation->sealResult((string) $result->run_id, 'ACHIEVED', 'محاولة إعادة ختم.', null, self::ACTOR);
            $this->fail('Result reseal unexpectedly succeeded.');
        } catch (DomainException) {
            $this->assertDatabaseCount('simulation_run_results', 1);
        }
        $this->expectException(QueryException::class);
        DB::table('simulation_run_results')->where('id', $result->id)->update(['outcome' => 'ACHIEVED']);
    }
}
