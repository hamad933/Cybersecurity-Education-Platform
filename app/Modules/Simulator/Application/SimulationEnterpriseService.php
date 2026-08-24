<?php

namespace App\Modules\Simulator\Application;

use App\Modules\Enterprise\Application\SimulationEnterpriseState;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use stdClass;

final class SimulationEnterpriseService
{
    private const RUN_OPERATIONS_TABLE = 'simulation_run_operations';

    private const RESULT_REPLAY_COMPARES_TABLE = 'simulation_result_replay_compares';

    public const RUN_STANDALONE_LAB = 'Standalone Lab Run';

    public const RUN_SCENARIO = 'Scenario Run';

    public const PROVENANCE_SIMULATED = 'SIMULATED';

    public const OPERATION_GRAMMAR = 'CEP_INTERNAL_OPERATION_V1';

    /** @var list<string> */
    public const RUN_TYPES = [self::RUN_STANDALONE_LAB, self::RUN_SCENARIO];

    /** @var list<string> */
    public const LIFECYCLES = ['PREPARING', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED', 'STOPPED', 'FAILED'];

    /** @var list<string> */
    public const OUTCOMES = ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'];

    /** @var array<string,list<string>> */
    private const TRANSITIONS = [
        'PREPARING' => ['READY', 'FAILED'],
        'READY' => ['RUNNING', 'STOPPED', 'FAILED'],
        'RUNNING' => ['PAUSED', 'COMPLETED', 'STOPPED', 'FAILED'],
        'PAUSED' => ['RUNNING', 'STOPPED', 'FAILED'],
        'COMPLETED' => [],
        'STOPPED' => [],
        'FAILED' => [],
    ];

    public function __construct(private readonly SimulationEnterpriseStateReader $enterpriseState) {}

    /**
     * @param  array<string, mixed>  $orchestration
     * @param  array<string, mixed>  $validation
     * @return array{id: string, enterprise_id: string, baseline_id: string, slug: string, title_ar: string, title_en: string|null, revision: int, status: string, orchestration: string, validation: string, digest: string, created_by: string|null, created_at: string, updated_at: string}
     */
    public function publishScenario(
        string $enterpriseId,
        string $baselineId,
        string $slug,
        string $titleAr,
        array $orchestration,
        array $validation = [],
        ?string $actorId = null,
    ): array {
        $enterpriseState = $this->requirePublishedBaseline($enterpriseId, $baselineId);
        $revision = (int) DB::table('simulation_scenario_definitions')->where('slug', $slug)->max('revision') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_scenario_definitions')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'baseline_id' => $baselineId,
            'slug' => $slug,
            'title_ar' => $titleAr,
            'title_en' => null,
            'revision' => $revision,
            'status' => 'PUBLISHED',
            'orchestration' => $this->json($orchestration),
            'validation' => $this->json($validation),
            'digest' => $this->digest([
                'baseline' => $enterpriseState->baseline['digest'],
                'orchestration' => $orchestration,
                'validation' => $validation,
            ]),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_scenario_definitions', $id);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $validation
     * @return array{id: string, enterprise_id: string, baseline_id: string, slug: string, title_ar: string, title_en: string|null, revision: int, status: string, configuration: string, validation: string, digest: string, created_by: string|null, created_at: string, updated_at: string}
     */
    public function publishLab(
        string $enterpriseId,
        string $baselineId,
        string $slug,
        string $titleAr,
        array $configuration,
        array $validation = [],
        ?string $actorId = null,
    ): array {
        $enterpriseState = $this->requirePublishedBaseline($enterpriseId, $baselineId);
        $revision = (int) DB::table('simulation_lab_definitions')->where('slug', $slug)->max('revision') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_lab_definitions')->insert([
            'id' => $id,
            'enterprise_id' => $enterpriseId,
            'baseline_id' => $baselineId,
            'slug' => $slug,
            'title_ar' => $titleAr,
            'title_en' => null,
            'revision' => $revision,
            'status' => 'PUBLISHED',
            'configuration' => $this->json($configuration),
            'validation' => $this->json($validation),
            'digest' => $this->digest([
                'baseline' => $enterpriseState->baseline['digest'],
                'configuration' => $configuration,
                'validation' => $validation,
            ]),
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_lab_definitions', $id);
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array{id: string, scenario_definition_id: string, lab_definition_id: string, module_key: string, ordinal: int, policy: string, created_at: string, updated_at: string}
     */
    public function attachLabModule(string $scenarioDefinitionId, string $labDefinitionId, string $moduleKey, array $policy = []): array
    {
        $scenario = $this->requireRow('simulation_scenario_definitions', $scenarioDefinitionId);
        $lab = $this->requireRow('simulation_lab_definitions', $labDefinitionId);
        if ((string) $scenario->enterprise_id !== (string) $lab->enterprise_id) {
            throw new LogicException('Scenario Lab Module Reference must remain inside one Enterprise.');
        }
        if ((string) $scenario->baseline_id !== (string) $lab->baseline_id) {
            throw new LogicException('Scenario and referenced Lab must pin the same Baseline in Wave 1.');
        }
        $ordinal = (int) DB::table('simulation_scenario_lab_references')->where('scenario_definition_id', $scenarioDefinitionId)->max('ordinal') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_scenario_lab_references')->insert([
            'id' => $id,
            'scenario_definition_id' => $scenarioDefinitionId,
            'lab_definition_id' => $labDefinitionId,
            'module_key' => $moduleKey,
            'ordinal' => $ordinal,
            'policy' => $this->json($policy),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_scenario_lab_references', $id);
    }

    /**
     * @param  array<string, mixed>  $executionPolicies
     * @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string}
     */
    public function prepareScenarioRun(string $scenarioDefinitionId, int $seed, array $executionPolicies, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($scenarioDefinitionId, $seed, $executionPolicies, $actorId): array {
            $scenario = $this->requireRow('simulation_scenario_definitions', $scenarioDefinitionId);
            if ((string) $scenario->status !== 'PUBLISHED') {
                throw new LogicException('Scenario Run requires a published Scenario Definition.');
            }
            $lineage = $this->lineage((string) $scenario->enterprise_id, (string) $scenario->baseline_id);
            $run = $this->insertRun(self::RUN_SCENARIO, $lineage, $seed, $executionPolicies, $actorId, $scenarioDefinitionId, null, (string) $scenario->digest);
            $references = DB::table('simulation_scenario_lab_references')->where('scenario_definition_id', $scenarioDefinitionId)->orderBy('ordinal')->get();
            foreach ($references as $reference) {
                $this->instantiateLabModule((string) $run['id'], $reference);
            }
            $this->appendEvent((string) $run['id'], 'RUN_PREPARED', [
                'run_type' => self::RUN_SCENARIO,
                'scenario_definition_id' => $scenarioDefinitionId,
                'lab_module_instance_count' => $references->count(),
                'provenance' => self::PROVENANCE_SIMULATED,
            ], $actorId);
            $this->insertSnapshot($this->requireRow('simulation_runs', (string) $run['id']), $actorId);

            return $this->row('simulation_runs', (string) $run['id']);
        });
    }

    /**
     * @param  array<string, mixed>  $executionPolicies
     * @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string}
     */
    public function prepareStandaloneLabRun(string $labDefinitionId, int $seed, array $executionPolicies, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($labDefinitionId, $seed, $executionPolicies, $actorId): array {
            $lab = $this->requireRow('simulation_lab_definitions', $labDefinitionId);
            if ((string) $lab->status !== 'PUBLISHED') {
                throw new LogicException('Standalone Lab Run requires a published Lab Definition.');
            }
            $lineage = $this->lineage((string) $lab->enterprise_id, (string) $lab->baseline_id);
            $run = $this->insertRun(self::RUN_STANDALONE_LAB, $lineage, $seed, $executionPolicies, $actorId, null, $labDefinitionId, (string) $lab->digest);
            $this->appendEvent((string) $run['id'], 'RUN_PREPARED', [
                'run_type' => self::RUN_STANDALONE_LAB,
                'lab_definition_id' => $labDefinitionId,
                'provenance' => self::PROVENANCE_SIMULATED,
            ], $actorId);
            $this->insertSnapshot($this->requireRow('simulation_runs', (string) $run['id']), $actorId);

            return $this->row('simulation_runs', (string) $run['id']);
        });
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function markReady(string $runId, string $actorId): array
    {
        return $this->transition($runId, 'READY', 'RUN_READY', $actorId);
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function start(string $runId, string $actorId): array
    {
        return $this->transition($runId, 'RUNNING', 'RUN_STARTED', $actorId);
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function pause(string $runId, string $actorId): array
    {
        return $this->transition($runId, 'PAUSED', 'RUN_PAUSED', $actorId);
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function resume(string $runId, string $actorId): array
    {
        return $this->transition($runId, 'RUNNING', 'RUN_RESUMED', $actorId);
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function stop(string $runId, string $actorId): array
    {
        return $this->transition($runId, 'STOPPED', 'RUN_STOPPED', $actorId);
    }

    /**
     * @param  array{operation_key: string, verb: string, target: string, value: mixed}  $operation
     * @return array{id: string, run_id: string, operation_key: string, grammar_version: string, verb: string, target: string, value: string, pre_state_digest: string, post_state_digest: string, telemetry: string, actor_id: string, occurred_at: string}
     */
    public function applyOperation(string $runId, array $operation, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($runId, $operation, $actorId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }
            if ((string) $run->lifecycle !== 'RUNNING') {
                throw new DomainException('Operations can be applied only to a RUNNING Run.');
            }
            $this->assertOperation($operation);
            $inputDigest = $this->digest($operation);
            $existing = DB::table(self::RUN_OPERATIONS_TABLE)
                ->where('run_id', $runId)
                ->where('operation_key', $operation['operation_key'])
                ->first();
            if ($existing !== null) {
                if (hash_equals((string) $existing->input_digest, $inputDigest) && (string) $existing->actor_id === $actorId) {
                    return (array) $existing;
                }
                throw new DomainException('Operation key conflicts with a different actor or payload.');
            }

            $runtimeState = $this->decodeJson($run->runtime_state);
            $preStateDigest = $this->digest($runtimeState);
            [$newState, $telemetry] = $this->applyOperationGrammar($runtimeState, $operation);
            $postStateDigest = $this->digest($newState);
            $operationId = (string) Str::uuid7();
            $now = now();
            DB::table(self::RUN_OPERATIONS_TABLE)->insert([
                'id' => $operationId,
                'run_id' => $runId,
                'operation_key' => $operation['operation_key'],
                'grammar_version' => self::OPERATION_GRAMMAR,
                'verb' => $operation['verb'],
                'target' => $operation['target'],
                'input' => $this->json($operation),
                'input_digest' => $inputDigest,
                'pre_state_digest' => $preStateDigest,
                'post_state_digest' => $postStateDigest,
                'telemetry' => $this->json($telemetry),
                'actor_id' => $actorId,
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
            DB::table('simulation_runs')->where('id', $runId)->update([
                'runtime_state' => $this->json($newState),
                'updated_at' => $now,
            ]);
            $this->appendEvent($runId, 'SIMULATION_OPERATION_APPLIED', [
                'operation_id' => $operationId,
                'operation_key' => $operation['operation_key'],
                'grammar_version' => self::OPERATION_GRAMMAR,
                'verb' => $operation['verb'],
                'target' => $operation['target'],
                'value' => $operation['value'],
                'pre_state_digest' => $preStateDigest,
                'post_state_digest' => $postStateDigest,
                'telemetry' => $telemetry,
            ], $actorId);
            $this->insertSnapshot($this->requireRow('simulation_runs', $runId), $actorId);

            return $this->row(self::RUN_OPERATIONS_TABLE, $operationId);
        });
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    public function completeInternalSimulation(string $runId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($runId, $actorId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || (string) $run->lifecycle !== 'RUNNING') {
                throw new DomainException('Internal simulation can complete only a RUNNING Run.');
            }
            $operations = DB::table(self::RUN_OPERATIONS_TABLE)->where('run_id', $runId)->orderBy('occurred_at')->get();
            if ($operations->isEmpty()) {
                throw new DomainException('Complete at least one bounded in-Run operation before completion.');
            }
            $state = $this->decodeJson($run->runtime_state);
            $changedOperations = $operations->filter(function (stdClass $operation): bool {
                $telemetry = $this->decodeJson($operation->telemetry);

                return ($telemetry['state_changed'] ?? false) === true;
            })->count();
            $state['engine'] = self::OPERATION_GRAMMAR;
            $state['telemetry'] = [
                'operation_count' => $operations->count(),
                'state_change_count' => $changedOperations,
                'final_state_digest' => $this->digest($state['simulated_state'] ?? []),
            ];
            $state['validation'] = [
                'traceable' => true,
                'deterministic' => true,
                'operation_grammar' => self::OPERATION_GRAMMAR,
            ];
            DB::table('simulation_runs')->where('id', $runId)->update([
                'runtime_state' => $this->json($state),
                'updated_at' => now(),
            ]);
            $this->appendEvent($runId, 'SIMULATION_STATE_FINALIZED', [
                'operation_count' => $operations->count(),
                'state_change_count' => $changedOperations,
                'runtime_state_digest' => $this->digest($state),
            ], $actorId);
            $this->insertSnapshot($this->requireRow('simulation_runs', $runId), $actorId);

            return $this->transitionLocked($runId, 'RUNNING', 'COMPLETED', 'RUN_COMPLETED', $actorId);
        });
    }

    /** @return array{id: string, run_id: string, sequence: int, event_sequence: int, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, state: string, state_digest: string, captured_by: string, captured_at: string, created_at: string} */
    public function captureSnapshot(string $runId, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($runId, $actorId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }
            if (in_array((string) $run->lifecycle, ['COMPLETED', 'STOPPED', 'FAILED'], true)) {
                throw new DomainException('Terminal Run snapshots are sealed by historical Result data.');
            }
            $snapshot = $this->insertSnapshot($run, $actorId);
            $this->appendEvent($runId, 'RUNTIME_SNAPSHOT_CAPTURED', [
                'snapshot_id' => $snapshot['id'],
                'state_digest' => $snapshot['state_digest'],
            ], $actorId);

            return $snapshot;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $artifacts
     * @return array{id: string, run_id: string, outcome: string, score: float|null, summary_ar: string, sealed_payload: string, replay_timeline: string, artifacts: string, result_revision: int, result_digest: string, provenance: string, source_fixture: bool, sealed_by: string, sealed_at: string, created_at: string}
     */
    public function sealResult(string $runId, string $outcome, string $summaryAr, ?float $score, string $actorId, array $artifacts = []): array
    {
        $this->assertActor($actorId);
        if (! in_array($outcome, self::OUTCOMES, true)) {
            throw new InvalidArgumentException('Unsupported Result outcome.');
        }
        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Result score must be between 0 and 100.');
        }

        return DB::transaction(function () use ($runId, $outcome, $summaryAr, $score, $actorId, $artifacts): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || ! in_array((string) $run->lifecycle, ['COMPLETED', 'STOPPED', 'FAILED'], true)) {
                throw new DomainException('Result can be sealed only for a terminal Run.');
            }
            if (DB::table('simulation_run_results')->where('run_id', $runId)->exists()) {
                throw new DomainException('A sealed Result already exists for this Run.');
            }
            $events = DB::table('simulation_run_events')->where('run_id', $runId)->orderBy('sequence')->get()->map(fn (stdClass $event): array => [
                'sequence' => (int) $event->sequence,
                'event_type' => (string) $event->event_type,
                'payload' => $this->decodeJson($event->payload),
                'actor_id' => (string) $event->actor_id,
                'occurred_at' => (string) $event->occurred_at,
            ])->all();
            $snapshots = DB::table('simulation_runtime_snapshots')->where('run_id', $runId)->orderBy('sequence')->get()->map(fn (stdClass $snapshot): array => [
                'id' => (string) $snapshot->id,
                'sequence' => (int) $snapshot->sequence,
                'event_sequence' => (int) $snapshot->event_sequence,
                'digital_twin_id' => (string) $snapshot->digital_twin_id,
                'digital_twin_revision_id' => (string) $snapshot->digital_twin_revision_id,
                'baseline_id' => (string) $snapshot->baseline_id,
                'state' => $this->decodeJson($snapshot->state),
                'state_digest' => (string) $snapshot->state_digest,
                'captured_by' => (string) $snapshot->captured_by,
                'captured_at' => (string) $snapshot->captured_at,
            ])->all();
            $operations = DB::table(self::RUN_OPERATIONS_TABLE)->where('run_id', $runId)->orderBy('occurred_at')->get()->map(fn (stdClass $operation): array => [
                'id' => (string) $operation->id,
                'operation_key' => (string) $operation->operation_key,
                'grammar_version' => (string) $operation->grammar_version,
                'input' => $this->decodeJson($operation->input),
                'input_digest' => (string) $operation->input_digest,
                'pre_state_digest' => (string) $operation->pre_state_digest,
                'post_state_digest' => (string) $operation->post_state_digest,
                'telemetry' => $this->decodeJson($operation->telemetry),
                'actor_id' => (string) $operation->actor_id,
                'occurred_at' => (string) $operation->occurred_at,
            ])->all();
            $operationArtifacts = array_map(static fn (array $operation): array => [
                'kind' => 'SIMULATED_OPERATION_TRACE',
                'ref' => 'simulation://operations/'.$operation['id'],
                'digest' => $operation['post_state_digest'],
                'provenance' => self::PROVENANCE_SIMULATED,
            ], $operations);
            $sealedArtifacts = array_merge($artifacts, $operationArtifacts);
            $sealedPayload = [
                'schema' => 'cep.simulation.run-result.v1',
                'run_id' => $runId,
                'run_type' => (string) $run->run_type,
                'run_lifecycle' => (string) $run->lifecycle,
                'provenance' => (string) $run->provenance,
                'source_fixture' => (bool) $run->source_fixture,
                'lineage' => [
                    'enterprise_id' => (string) $run->enterprise_id,
                    'digital_twin_id' => (string) $run->digital_twin_id,
                    'digital_twin_revision_id' => (string) $run->digital_twin_revision_id,
                    'baseline_id' => (string) $run->baseline_id,
                ],
                'scenario_definition_id' => $run->scenario_definition_id,
                'standalone_lab_definition_id' => $run->standalone_lab_definition_id,
                'seed' => (int) $run->seed,
                'input_digest' => (string) $run->input_digest,
                'initial_runtime_state' => $snapshots[0]['state'] ?? [],
                'runtime_state' => $this->decodeJson($run->runtime_state),
                'operations' => $operations,
                'snapshots' => $snapshots,
            ];
            $resultRevision = 1;
            $digestPayload = $this->resultDigestPayload(
                $runId,
                $outcome,
                $score,
                $summaryAr,
                $sealedPayload,
                $events,
                $sealedArtifacts,
                $resultRevision,
                (string) $run->provenance,
                (bool) $run->source_fixture,
            );
            $resultDigest = $this->digest($digestPayload);
            $id = (string) Str::uuid7();
            $now = now();
            DB::table('simulation_run_results')->insert([
                'id' => $id,
                'run_id' => $runId,
                'outcome' => $outcome,
                'score' => $score,
                'summary_ar' => $summaryAr,
                'sealed_payload' => $this->json($sealedPayload),
                'replay_timeline' => $this->json($events),
                'artifacts' => $this->json($sealedArtifacts),
                'result_revision' => $resultRevision,
                'result_digest' => $resultDigest,
                'provenance' => (string) $run->provenance,
                'source_fixture' => (bool) $run->source_fixture,
                'sealed_by' => $actorId,
                'sealed_at' => $now,
                'created_at' => $now,
            ]);

            return $this->row('simulation_run_results', $id);
        });
    }

    /** @return array{id: string, result_id: string, reconstruction: string, sealed_result_digest: string, reconstructed_state_digest: string, integrity_match: bool, actor_id: string, compared_at: string, created_at: string} */
    public function replayAndCompareResult(string $resultId, string $actorId): array
    {
        $this->assertActor($actorId);
        $result = $this->requireRow('simulation_run_results', $resultId);
        $sealedPayload = $this->decodeJson($result->sealed_payload);
        $timeline = $this->decodeList($result->replay_timeline);
        $artifacts = $this->decodeList($result->artifacts);
        $operations = is_array($sealedPayload['operations'] ?? null) ? $sealedPayload['operations'] : [];
        $snapshots = is_array($sealedPayload['snapshots'] ?? null) ? $sealedPayload['snapshots'] : [];
        $state = is_array($sealedPayload['initial_runtime_state'] ?? null) ? $sealedPayload['initial_runtime_state'] : [];
        $operationByKey = [];
        $operationChainValid = true;
        foreach ($operations as $operation) {
            if (
                ! is_array($operation)
                || ! is_array($operation['input'] ?? null)
                || ! is_string($operation['operation_key'] ?? null)
                || isset($operationByKey[$operation['operation_key']])
                || ! hash_equals((string) ($operation['input_digest'] ?? ''), $this->digest($operation['input']))
            ) {
                $operationChainValid = false;
                break;
            }
            $operationByKey[$operation['operation_key']] = $operation;
        }
        $timelineIntegrity = $this->timelineIsSequential($timeline);
        $reconstructedStatesByEventSequence = [];
        $appliedOperationKeys = [];
        if ($timelineIntegrity && $operationChainValid) {
            foreach ($timeline as $event) {
                $payload = is_array($event['payload'] ?? null) ? $event['payload'] : [];
                $from = $payload['from'] ?? null;
                $to = $payload['to'] ?? null;
                if (is_string($from) && is_string($to)) {
                    if (($state['phase'] ?? null) !== $from) {
                        $operationChainValid = false;
                        break;
                    }
                    $state['phase'] = $to;
                }

                if (($event['event_type'] ?? null) === 'SIMULATION_OPERATION_APPLIED') {
                    $operationKey = $payload['operation_key'] ?? null;
                    if (! is_string($operationKey) || ! isset($operationByKey[$operationKey]) || isset($appliedOperationKeys[$operationKey])) {
                        $operationChainValid = false;
                        break;
                    }
                    $operation = $operationByKey[$operationKey];
                    if (
                        ! hash_equals((string) ($operation['pre_state_digest'] ?? ''), $this->digest($state))
                        || ! hash_equals((string) ($payload['pre_state_digest'] ?? ''), (string) $operation['pre_state_digest'])
                        || ($event['actor_id'] ?? null) !== ($operation['actor_id'] ?? null)
                        || ($payload['grammar_version'] ?? null) !== ($operation['grammar_version'] ?? null)
                        || ($payload['verb'] ?? null) !== ($operation['input']['verb'] ?? null)
                        || ($payload['target'] ?? null) !== ($operation['input']['target'] ?? null)
                        || ($payload['value'] ?? null) !== ($operation['input']['value'] ?? null)
                    ) {
                        $operationChainValid = false;
                        break;
                    }
                    [$state] = $this->applyOperationGrammar($state, $operation['input']);
                    if (
                        ! hash_equals((string) ($operation['post_state_digest'] ?? ''), $this->digest($state))
                        || ! hash_equals((string) ($payload['post_state_digest'] ?? ''), (string) $operation['post_state_digest'])
                    ) {
                        $operationChainValid = false;
                        break;
                    }
                    $appliedOperationKeys[$operationKey] = true;
                }

                if (($event['event_type'] ?? null) === 'SIMULATION_STATE_FINALIZED') {
                    $appliedOperations = array_intersect_key($operationByKey, $appliedOperationKeys);
                    $stateChangeCount = count(array_filter($appliedOperations, static function (array $operation): bool {
                        $telemetry = $operation['telemetry'] ?? null;

                        return is_array($telemetry) && ($telemetry['state_changed'] ?? false) === true;
                    }));
                    $state['engine'] = self::OPERATION_GRAMMAR;
                    $state['telemetry'] = [
                        'operation_count' => count($appliedOperations),
                        'state_change_count' => $stateChangeCount,
                        'final_state_digest' => $this->digest($state['simulated_state'] ?? []),
                    ];
                    $state['validation'] = [
                        'traceable' => true,
                        'deterministic' => true,
                        'operation_grammar' => self::OPERATION_GRAMMAR,
                    ];
                    if (
                        ($payload['operation_count'] ?? null) !== count($appliedOperations)
                        || ($payload['state_change_count'] ?? null) !== $stateChangeCount
                        || ! hash_equals((string) ($payload['runtime_state_digest'] ?? ''), $this->digest($state))
                    ) {
                        $operationChainValid = false;
                        break;
                    }
                }

                $reconstructedStatesByEventSequence[(int) $event['sequence']] = $state;
            }
        }
        $operationChainValid = $operationChainValid && count($appliedOperationKeys) === count($operationByKey);
        $sealedLineage = is_array($sealedPayload['lineage'] ?? null) ? $sealedPayload['lineage'] : [];
        $snapshotIntegrity = $snapshots !== [];
        $previousSnapshotEventSequence = 0;
        $expectedSnapshotSequence = 1;
        foreach ($snapshots as $snapshot) {
            $eventSequence = is_array($snapshot) ? ($snapshot['event_sequence'] ?? null) : null;
            if (
                ! is_array($snapshot)
                || ! is_int($snapshot['sequence'] ?? null)
                || $snapshot['sequence'] !== $expectedSnapshotSequence
                || ! is_int($eventSequence)
                || $eventSequence < 1
                || $eventSequence > count($timeline)
                || $eventSequence <= $previousSnapshotEventSequence
                || ! is_array($snapshot['state'] ?? null)
                || ! hash_equals((string) ($snapshot['state_digest'] ?? ''), $this->digest($snapshot['state']))
                || ! isset($reconstructedStatesByEventSequence[$eventSequence])
                || ! hash_equals($this->digest($snapshot['state']), $this->digest($reconstructedStatesByEventSequence[$eventSequence]))
                || ($snapshot['digital_twin_id'] ?? null) !== ($sealedLineage['digital_twin_id'] ?? null)
                || ($snapshot['digital_twin_revision_id'] ?? null) !== ($sealedLineage['digital_twin_revision_id'] ?? null)
                || ($snapshot['baseline_id'] ?? null) !== ($sealedLineage['baseline_id'] ?? null)
                || ! is_string($snapshot['captured_by'] ?? null)
                || $snapshot['captured_by'] === ''
            ) {
                $snapshotIntegrity = false;
                break;
            }
            $previousSnapshotEventSequence = $eventSequence;
            $expectedSnapshotSequence++;
        }
        $artifactIntegrity = true;
        foreach ($operationByKey as $operation) {
            $expectedRef = 'simulation://operations/'.($operation['id'] ?? '');
            $matchingArtifact = array_values(array_filter($artifacts, static fn (mixed $artifact): bool => is_array($artifact) && ($artifact['ref'] ?? null) === $expectedRef));
            if (
                count($matchingArtifact) !== 1
                || ($matchingArtifact[0]['digest'] ?? null) !== ($operation['post_state_digest'] ?? null)
                || ($matchingArtifact[0]['provenance'] ?? null) !== self::PROVENANCE_SIMULATED
            ) {
                $artifactIntegrity = false;
                break;
            }
        }
        $finalRuntimeState = is_array($sealedPayload['runtime_state'] ?? null) ? $sealedPayload['runtime_state'] : [];
        $reconstructedStateDigest = $this->digest($state);
        $finalStateMatches = hash_equals($this->digest($finalRuntimeState), $reconstructedStateDigest);
        $storedDigestValid = hash_equals((string) $result->result_digest, $this->digest($this->resultDigestPayloadFromRow($result)));
        $integrityMatch = $operationChainValid && $snapshotIntegrity && $artifactIntegrity && $finalStateMatches && $timelineIntegrity && $storedDigestValid;
        $reconstruction = [
            'schema' => 'cep.simulation.semantic-replay-compare.v1',
            'sealed_lineage' => $sealedLineage,
            'timeline' => $timeline,
            'artifacts' => $artifacts,
            'runtime_snapshots' => $snapshots,
            'operation_count' => count($operations),
            'reconstructed_runtime_state' => $state,
            'checks' => [
                'operation_chain_valid' => $operationChainValid,
                'snapshot_integrity' => $snapshotIntegrity,
                'artifact_integrity' => $artifactIntegrity,
                'final_state_matches' => $finalStateMatches,
                'timeline_integrity' => $timelineIntegrity,
                'sealed_result_digest_valid' => $storedDigestValid,
            ],
            'provenance' => (string) $result->provenance,
            'source_fixture' => (bool) $result->source_fixture,
        ];
        $id = (string) Str::uuid7();
        $now = now();
        DB::table(self::RESULT_REPLAY_COMPARES_TABLE)->insert([
            'id' => $id,
            'result_id' => $resultId,
            'reconstruction' => $this->json($reconstruction),
            'sealed_result_digest' => (string) $result->result_digest,
            'reconstructed_state_digest' => $reconstructedStateDigest,
            'integrity_match' => $integrityMatch,
            'actor_id' => $actorId,
            'compared_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row(self::RESULT_REPLAY_COMPARES_TABLE, $id);
    }

    /**
     * @param  array<string, mixed>  $candidateManifest
     * @return array{id: string, result_id: string, status: string, candidate_manifest: string, source_result_revision: int, source_result_digest: string, provenance: string, source_fixture: bool, manifest_digest: string, created_by: string, intake_contract_ref: string|null, handed_off_at: string|null, created_at: string, updated_at: string}
     */
    public function createCandidateEvidenceHandoff(string $resultId, array $candidateManifest, ?string $intakeContractRef, string $actorId): array
    {
        $this->assertActor($actorId);
        $result = $this->requireRow('simulation_run_results', $resultId);
        if (DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $resultId)->exists()) {
            throw new DomainException('Candidate Evidence Handoff already exists for this Result.');
        }
        if ($intakeContractRef !== null && ($intakeContractRef === '' || strlen($intakeContractRef) > 160)) {
            throw new InvalidArgumentException('Candidate Evidence intake contract reference is malformed.');
        }
        if (! hash_equals((string) $result->result_digest, $this->digest($this->resultDigestPayloadFromRow($result)))) {
            throw new DomainException('Source Result digest verification failed.');
        }
        $claimAr = $candidateManifest['claim_ar'] ?? null;
        $artifactRefs = $candidateManifest['artifact_refs'] ?? [];
        if (! is_string($claimAr) || trim($claimAr) === '' || mb_strlen($claimAr) > 1000 || ! is_array($artifactRefs) || ! array_is_list($artifactRefs) || count($artifactRefs) > 20) {
            throw new InvalidArgumentException('Candidate Evidence manifest is malformed or exceeds its bounded contract.');
        }
        foreach ($artifactRefs as $artifactRef) {
            if (! is_string($artifactRef) || $artifactRef === '' || strlen($artifactRef) > 240) {
                throw new InvalidArgumentException('Candidate Evidence artifact references must be bounded strings.');
            }
        }
        $artifactRefs = array_values(array_unique($artifactRefs));
        $sealedArtifactRefs = array_values(array_filter(array_map(
            static fn (mixed $artifact): mixed => is_array($artifact) ? ($artifact['ref'] ?? null) : null,
            $this->decodeList($result->artifacts),
        ), static fn (mixed $ref): bool => is_string($ref)));
        if (array_diff($artifactRefs, $sealedArtifactRefs) !== []) {
            throw new DomainException('Candidate Evidence references must resolve to artifacts sealed in the source Result.');
        }
        $manifest = [
            'schema' => 'cep.simulation.candidate-evidence-handoff.v1',
            'source_result' => [
                'id' => $resultId,
                'revision' => (int) $result->result_revision,
                'digest' => (string) $result->result_digest,
                'run_id' => (string) $result->run_id,
                'provenance' => (string) $result->provenance,
                'source_fixture' => (bool) $result->source_fixture,
            ],
            'claim_ar' => $claimAr,
            'artifact_refs' => $artifactRefs,
            'source' => 'SIMULATION_RUN_RESULT',
        ];
        $manifestDigest = $this->digest($manifest);
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_candidate_evidence_handoffs')->insert([
            'id' => $id,
            'result_id' => $resultId,
            'status' => 'READY_FOR_INTAKE',
            'candidate_manifest' => $this->json($manifest),
            'source_result_revision' => (int) $result->result_revision,
            'source_result_digest' => (string) $result->result_digest,
            'provenance' => (string) $result->provenance,
            'source_fixture' => (bool) $result->source_fixture,
            'manifest_digest' => $manifestDigest,
            'created_by' => $actorId,
            'intake_contract_ref' => $intakeContractRef,
            'handed_off_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_candidate_evidence_handoffs', $id);
    }

    /** @return list<string> */
    public function availableActions(string $lifecycle): array
    {
        return match ($lifecycle) {
            'PREPARING' => ['ready'],
            'READY' => ['start', 'stop', 'snapshot'],
            'RUNNING' => ['operate', 'complete', 'pause', 'stop', 'snapshot'],
            'PAUSED' => ['resume', 'stop', 'snapshot'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $lineage
     * @param  array<string, mixed>  $executionPolicies
     * @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string}
     */
    private function insertRun(
        string $runType,
        array $lineage,
        int $seed,
        array $executionPolicies,
        string $actorId,
        ?string $scenarioDefinitionId,
        ?string $labDefinitionId,
        string $definitionDigest,
    ): array {
        if (! in_array($runType, self::RUN_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported Run type.');
        }
        $id = (string) Str::uuid7();
        $now = now();
        $input = [
            'run_type' => $runType,
            'enterprise_id' => $lineage['enterprise_id'],
            'digital_twin_id' => $lineage['digital_twin_id'],
            'digital_twin_revision_id' => $lineage['digital_twin_revision_id'],
            'baseline_id' => $lineage['baseline_id'],
            'definition_digest' => $definitionDigest,
            'seed' => $seed,
            'execution_policies' => $executionPolicies,
            'provenance' => $lineage['provenance'],
        ];
        DB::table('simulation_runs')->insert([
            'id' => $id,
            'enterprise_id' => $lineage['enterprise_id'],
            'digital_twin_id' => $lineage['digital_twin_id'],
            'digital_twin_revision_id' => $lineage['digital_twin_revision_id'],
            'baseline_id' => $lineage['baseline_id'],
            'run_type' => $runType,
            'scenario_definition_id' => $scenarioDefinitionId,
            'standalone_lab_definition_id' => $labDefinitionId,
            'lifecycle' => 'PREPARING',
            'execution_policies' => $this->json($executionPolicies),
            'seed' => $seed,
            'runtime_state' => $this->json([
                'engine' => self::OPERATION_GRAMMAR,
                'phase' => 'PREPARING',
                'simulated_state' => $lineage['baseline_state'],
                'causality' => [],
                'telemetry' => ['operation_count' => 0, 'state_change_count' => 0],
                'validation' => [],
                'provenance' => self::PROVENANCE_SIMULATED,
            ]),
            'input_digest' => $this->digest($input),
            'provenance' => $lineage['provenance'],
            'source_fixture' => $lineage['source_fixture'],
            'created_by' => $actorId,
            'prepared_at' => $now,
            'ready_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'stopped_at' => null,
            'failed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->row('simulation_runs', $id);
    }

    /** @return array{enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, baseline_state: string, provenance: string, source_fixture: bool} */
    private function lineage(string $enterpriseId, string $baselineId): array
    {
        $state = $this->requirePublishedBaseline($enterpriseId, $baselineId);
        if ((string) ($state->digitalTwinRevision['status'] ?? '') !== 'PUBLISHED') {
            throw new LogicException('Run lineage requires a published Digital Twin Revision.');
        }
        if ((string) ($state->enterprise['provenance'] ?? '') !== self::PROVENANCE_SIMULATED || (string) ($state->digitalTwin['provenance'] ?? '') !== self::PROVENANCE_SIMULATED) {
            throw new LogicException('V1 Run lineage requires explicit SIMULATED provenance.');
        }

        return [
            'enterprise_id' => $enterpriseId,
            'digital_twin_id' => (string) $state->digitalTwin['id'],
            'digital_twin_revision_id' => (string) $state->digitalTwinRevision['id'],
            'baseline_id' => (string) $state->baseline['id'],
            'baseline_state' => $state->baseline['state'],
            'provenance' => self::PROVENANCE_SIMULATED,
            'source_fixture' => (bool) ($state->enterprise['is_fixture'] ?? false) || (bool) ($state->digitalTwin['is_fixture'] ?? false),
        ];
    }

    private function requirePublishedBaseline(string $enterpriseId, string $baselineId): SimulationEnterpriseState
    {
        $state = $this->enterpriseState->findPublishedBaselineForSimulation($enterpriseId, $baselineId);
        if ($state === null) {
            throw new LogicException('Definition must pin a published Baseline from the same Enterprise.');
        }

        return $state;
    }

    private function instantiateLabModule(string $runId, stdClass $reference): void
    {
        $now = now();
        DB::table('simulation_run_lab_module_instances')->insert([
            'id' => (string) Str::uuid7(),
            'run_id' => $runId,
            'scenario_lab_reference_id' => $reference->id,
            'lab_definition_id' => $reference->lab_definition_id,
            'instance_key' => $reference->module_key.'@'.$runId,
            'state' => $this->json(['status' => 'PREPARED', 'policy' => $this->decodeJson($reference->policy)]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    private function transition(string $runId, string $to, string $eventType, string $actorId): array
    {
        $this->assertActor($actorId);

        return DB::transaction(function () use ($runId, $to, $eventType, $actorId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }

            return $this->transitionLocked($runId, (string) $run->lifecycle, $to, $eventType, $actorId);
        });
    }

    /** @return array{id: string, run_type: string, lifecycle: string, enterprise_id: string, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, scenario_definition_id: string|null, standalone_lab_definition_id: string|null, seed: int, execution_policies: string, runtime_state: string, input_digest: string, definition_digest: string, provenance: string, source_fixture: bool, started_at: string|null, ready_at: string|null, stopped_at: string|null, completed_at: string|null, failed_at: string|null, created_at: string, updated_at: string} */
    private function transitionLocked(string $runId, string $from, string $to, string $eventType, string $actorId): array
    {
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new DomainException("Invalid Run lifecycle transition: {$from} -> {$to}.");
        }
        $run = $this->requireRow('simulation_runs', $runId);
        $state = $this->decodeJson($run->runtime_state);
        $state['phase'] = $to;
        $now = now();
        $timestamps = match ($to) {
            'READY' => ['ready_at' => $now],
            'RUNNING' => $run->started_at === null ? ['started_at' => $now] : [],
            'COMPLETED' => ['completed_at' => $now],
            'STOPPED' => ['stopped_at' => $now],
            'FAILED' => ['failed_at' => $now],
            default => [],
        };
        DB::table('simulation_runs')->where('id', $runId)->update($timestamps + [
            'lifecycle' => $to,
            'runtime_state' => $this->json($state),
            'updated_at' => $now,
        ]);
        $this->appendEvent($runId, $eventType, ['from' => $from, 'to' => $to], $actorId);

        return $this->row('simulation_runs', $runId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id: string, run_id: string, sequence: int, event_type: string, payload: string, actor_id: string, occurred_at: string, created_at: string}
     */
    private function appendEvent(string $runId, string $eventType, array $payload, string $actorId): array
    {
        $sequence = (int) DB::table('simulation_run_events')->where('run_id', $runId)->max('sequence') + 1;
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_run_events')->insert([
            'id' => $id,
            'run_id' => $runId,
            'sequence' => $sequence,
            'event_type' => $eventType,
            'payload' => $this->json($payload),
            'actor_id' => $actorId,
            'occurred_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_run_events', $id);
    }

    /** @return array{id: string, run_id: string, sequence: int, event_sequence: int, digital_twin_id: string, digital_twin_revision_id: string, baseline_id: string, state: string, state_digest: string, captured_by: string, captured_at: string, created_at: string} */
    private function insertSnapshot(stdClass $run, string $actorId): array
    {
        $sequence = (int) DB::table('simulation_runtime_snapshots')->where('run_id', $run->id)->max('sequence') + 1;
        $eventSequence = (int) DB::table('simulation_run_events')->where('run_id', $run->id)->max('sequence');
        $state = $this->decodeJson($run->runtime_state);
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_runtime_snapshots')->insert([
            'id' => $id,
            'run_id' => $run->id,
            'sequence' => $sequence,
            'event_sequence' => $eventSequence,
            'digital_twin_id' => $run->digital_twin_id,
            'digital_twin_revision_id' => $run->digital_twin_revision_id,
            'baseline_id' => $run->baseline_id,
            'state' => $this->json($state),
            'state_digest' => $this->digest($state),
            'captured_by' => $actorId,
            'captured_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_runtime_snapshots', $id);
    }

    /**
     * @param  array{operation_key: string, verb: string, target: string, value: mixed}  $operation
     */
    private function assertOperation(array $operation): void
    {
        if (
            preg_match('/^[A-Za-z0-9._:-]{12,120}$/', $operation['operation_key']) !== 1
            || $operation['verb'] !== 'SET_CONTROL_STATE'
            || $operation['target'] !== 'IDENTITY_MFA'
            || ! is_bool($operation['value'])
        ) {
            throw new InvalidArgumentException('Unsupported or malformed internal simulation operation.');
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array{operation_key: string, verb: string, target: string, value: mixed}  $operation
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function applyOperationGrammar(array $state, array $operation): array
    {
        $this->assertOperation($operation);
        $simulatedState = is_array($state['simulated_state'] ?? null) ? $state['simulated_state'] : [];
        $identityPolicy = is_array($simulatedState['identity_policy'] ?? null) ? $simulatedState['identity_policy'] : [];
        $before = ($identityPolicy['mfa_required'] ?? false) === true;
        $after = $operation['value'];
        $identityPolicy['mfa_required'] = $after;
        $simulatedState['identity_policy'] = $identityPolicy;
        $state['simulated_state'] = $simulatedState;
        $causality = is_array($state['causality'] ?? null) ? $state['causality'] : [];
        $causality[] = [
            'operation_key' => $operation['operation_key'],
            'grammar_version' => self::OPERATION_GRAMMAR,
            'verb' => $operation['verb'],
            'target' => $operation['target'],
            'before' => $before,
            'after' => $after,
        ];
        $state['causality'] = $causality;
        $existingTelemetry = is_array($state['telemetry'] ?? null) ? $state['telemetry'] : [];
        $telemetry = [
            'state_changed' => $before !== $after,
            'mfa_required_before' => $before,
            'mfa_required_after' => $after,
            'operation_count' => (int) ($existingTelemetry['operation_count'] ?? 0) + 1,
            'state_change_count' => (int) ($existingTelemetry['state_change_count'] ?? 0) + ($before !== $after ? 1 : 0),
        ];
        $state['telemetry'] = $telemetry;

        return [$state, $telemetry];
    }

    /** @return array{runId: string, outcome: string, score: float|null, summaryAr: string, sealedPayload: array<array-key, mixed>, timeline: list<array<array-key, mixed>>, artifacts: list<array<array-key, mixed>>, revision: int, provenance: string, sourceFixture: bool} */
    private function resultDigestPayloadFromRow(stdClass $result): array
    {
        return $this->resultDigestPayload(
            (string) $result->run_id,
            (string) $result->outcome,
            $result->score === null ? null : (float) $result->score,
            (string) $result->summary_ar,
            $this->decodeJson($result->sealed_payload),
            $this->decodeList($result->replay_timeline),
            $this->decodeList($result->artifacts),
            (int) $result->result_revision,
            (string) $result->provenance,
            (bool) $result->source_fixture,
        );
    }

    /**
     * @param  array<string, mixed>  $sealedPayload
     * @param  list<array<string, mixed>>  $timeline
     * @param  list<array<string, mixed>>  $artifacts
     * @return array{runId: string, outcome: string, score: float|null, summaryAr: string, sealedPayload: array<array-key, mixed>, timeline: list<array<array-key, mixed>>, artifacts: list<array<array-key, mixed>>, revision: int, provenance: string, sourceFixture: bool}
     */
    private function resultDigestPayload(
        string $runId,
        string $outcome,
        ?float $score,
        string $summaryAr,
        array $sealedPayload,
        array $timeline,
        array $artifacts,
        int $revision,
        string $provenance,
        bool $sourceFixture,
    ): array {
        return compact('runId', 'outcome', 'score', 'summaryAr', 'sealedPayload', 'timeline', 'artifacts', 'revision', 'provenance', 'sourceFixture');
    }

    /**
     * @param  list<array<string, mixed>>  $timeline
     */
    private function timelineIsSequential(array $timeline): bool
    {
        foreach ($timeline as $index => $event) {
            if (($event['sequence'] ?? null) !== $index + 1 || ! is_string($event['actor_id'] ?? null) || $event['actor_id'] === '') {
                return false;
            }
        }

        return $timeline !== [];
    }

    private function assertActor(string $actorId): void
    {
        if ($actorId === '' || strlen($actorId) > 120) {
            throw new InvalidArgumentException('A bounded simulation actor identifier is required.');
        }
    }

    /** @return array{id: string, [key: string]: mixed} */
    private function row(string $table, string $id): array
    {
        return (array) $this->requireRow($table, $id);
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException('Required simulation record was not found.');
        }

        return $row;
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<mixed>
     */
    private function decodeList(mixed $value): array
    {
        $decoded = $this->decodeJson($value);

        return array_is_list($decoded) ? $decoded : [];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
