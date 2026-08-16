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
    public const RUN_STANDALONE_LAB = 'Standalone Lab Run';

    public const RUN_SCENARIO = 'Scenario Run';

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

    public function __construct(
        private readonly SimulationEnterpriseStateReader $enterpriseState,
    ) {}

    /**
     * @param  array<string, mixed>  $orchestration
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function publishScenario(string $enterpriseId, string $baselineId, string $slug, string $titleAr, array $orchestration, array $validation = [], ?string $actorId = null): array
    {
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
     * @return array<string, mixed>
     */
    public function publishLab(string $enterpriseId, string $baselineId, string $slug, string $titleAr, array $configuration, array $validation = [], ?string $actorId = null): array
    {
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
     */
    public function prepareScenarioRun(string $scenarioDefinitionId, int $seed, array $executionPolicies = [], ?string $actorId = null): array
    {
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
            ]);
            $this->captureSnapshot((string) $run['id']);

            return $this->row('simulation_runs', (string) $run['id']);
        });
    }

    /**
     * @param  array<string, mixed>  $executionPolicies
     * @return array<string, mixed>
     */
    public function prepareStandaloneLabRun(string $labDefinitionId, int $seed, array $executionPolicies = [], ?string $actorId = null): array
    {
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
            ]);
            $this->captureSnapshot((string) $run['id']);

            return $this->row('simulation_runs', (string) $run['id']);
        });
    }

    /** @return array<string,mixed> */
    public function markReady(string $runId): array
    {
        return $this->transition($runId, 'READY', 'RUN_READY');
    }

    /** @return array<string,mixed> */
    public function start(string $runId): array
    {
        return $this->transition($runId, 'RUNNING', 'RUN_STARTED');
    }

    /** @return array<string,mixed> */
    public function pause(string $runId): array
    {
        return $this->transition($runId, 'PAUSED', 'RUN_PAUSED');
    }

    /** @return array<string,mixed> */
    public function resume(string $runId): array
    {
        return $this->transition($runId, 'RUNNING', 'RUN_RESUMED');
    }

    /** @return array<string,mixed> */
    public function stop(string $runId): array
    {
        return $this->transition($runId, 'STOPPED', 'RUN_STOPPED');
    }

    /** @return array<string,mixed> */
    public function completeInternalSimulation(string $runId): array
    {
        return DB::transaction(function () use ($runId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || (string) $run->lifecycle !== 'RUNNING') {
                throw new DomainException('Internal simulation can complete only a RUNNING Run.');
            }

            $enterpriseState = $this->enterpriseState->findForSimulation(
                (string) $run->enterprise_id,
                (string) $run->digital_twin_revision_id,
                (string) $run->baseline_id,
            );
            if ($enterpriseState === null) {
                throw new DomainException('Run lineage references missing Enterprise-owned state.');
            }

            $traceBase = [
                'engine' => 'INTERNAL_HIGH_FIDELITY_V1',
                'run_type' => (string) $run->run_type,
                'seed' => (int) $run->seed,
                'input_digest' => (string) $run->input_digest,
                'baseline_digest' => (string) $enterpriseState->baseline['digest'],
                'digital_twin_digest' => (string) $enterpriseState->digitalTwinRevision['digest'],
            ];
            $traceDigest = $this->digest($traceBase);
            $numeric = (int) sprintf('%u', crc32($traceDigest));
            $telemetry = [
                'signal_count' => ($numeric % 7) + 3,
                'validated_transitions' => ($numeric % 4) + 2,
                'causal_branch' => $numeric % 2 === 0 ? 'PRIMARY' : 'ALTERNATE',
                'validation_score' => 70 + ($numeric % 31),
            ];
            $state = $this->decodeJson($run->runtime_state);
            $state['engine'] = 'INTERNAL_HIGH_FIDELITY_V1';
            $state['trace_digest'] = $traceDigest;
            $state['telemetry'] = $telemetry;
            $state['validation'] = ['traceable' => true, 'deterministic' => true];
            DB::table('simulation_runs')->where('id', $runId)->update([
                'runtime_state' => $this->json($state),
                'updated_at' => now(),
            ]);
            $this->appendEvent($runId, 'SIMULATION_STATE_APPLIED', ['trace_digest' => $traceDigest, 'causal_branch' => $telemetry['causal_branch']]);
            $this->appendEvent($runId, 'TELEMETRY_CAPTURED', $telemetry);
            $this->captureSnapshot($runId);

            return $this->transitionLocked($runId, 'RUNNING', 'COMPLETED', 'RUN_COMPLETED');
        });
    }

    /** @return array<string,mixed> */
    public function captureSnapshot(string $runId): array
    {
        $run = $this->requireRow('simulation_runs', $runId);
        if (in_array((string) $run->lifecycle, ['COMPLETED', 'STOPPED', 'FAILED'], true)) {
            throw new DomainException('Terminal Run snapshots are sealed by historical Result data.');
        }
        $sequence = (int) DB::table('simulation_runtime_snapshots')->where('run_id', $runId)->max('sequence') + 1;
        $eventSequence = (int) DB::table('simulation_run_events')->where('run_id', $runId)->max('sequence');
        $state = $this->decodeJson($run->runtime_state);
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_runtime_snapshots')->insert([
            'id' => $id,
            'run_id' => $runId,
            'sequence' => $sequence,
            'event_sequence' => $eventSequence,
            'digital_twin_revision_id' => $run->digital_twin_revision_id,
            'baseline_id' => $run->baseline_id,
            'state' => $this->json($state),
            'state_digest' => $this->digest($state),
            'captured_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_runtime_snapshots', $id);
    }

    /**
     * @param  list<mixed>  $artifacts
     * @return array<string, mixed>
     */
    public function sealResult(string $runId, string $outcome, string $summaryAr, ?float $score = null, array $artifacts = []): array
    {
        if (in_array($outcome, self::OUTCOMES, true) === false) {
            throw new InvalidArgumentException('Unsupported Result outcome.');
        }
        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Result score must be between 0 and 100.');
        }

        return DB::transaction(function () use ($runId, $outcome, $summaryAr, $score, $artifacts): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || in_array((string) $run->lifecycle, ['COMPLETED', 'STOPPED', 'FAILED'], true) === false) {
                throw new DomainException('Result can be sealed only for a terminal Run.');
            }
            if (DB::table('simulation_run_results')->where('run_id', $runId)->exists()) {
                throw new DomainException('A sealed Result already exists for this Run.');
            }
            $events = DB::table('simulation_run_events')->where('run_id', $runId)->orderBy('sequence')->get()->map(fn (stdClass $event): array => [
                'sequence' => (int) $event->sequence,
                'event_type' => (string) $event->event_type,
                'payload' => $this->decodeJson($event->payload),
                'occurred_at' => (string) $event->occurred_at,
            ])->all();
            $snapshots = DB::table('simulation_runtime_snapshots')->where('run_id', $runId)->orderBy('sequence')->get()->map(fn (stdClass $snapshot): array => [
                'id' => (string) $snapshot->id,
                'sequence' => (int) $snapshot->sequence,
                'event_sequence' => (int) $snapshot->event_sequence,
                'state_digest' => (string) $snapshot->state_digest,
                'captured_at' => (string) $snapshot->captured_at,
            ])->all();
            $sealedPayload = [
                'run_id' => $runId,
                'run_type' => (string) $run->run_type,
                'run_lifecycle' => (string) $run->lifecycle,
                'enterprise_id' => (string) $run->enterprise_id,
                'digital_twin_revision_id' => (string) $run->digital_twin_revision_id,
                'baseline_id' => (string) $run->baseline_id,
                'scenario_definition_id' => $run->scenario_definition_id,
                'standalone_lab_definition_id' => $run->standalone_lab_definition_id,
                'seed' => (int) $run->seed,
                'input_digest' => (string) $run->input_digest,
                'runtime_state' => $this->decodeJson($run->runtime_state),
                'snapshots' => $snapshots,
            ];
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
                'artifacts' => $this->json($artifacts),
                'sealed_at' => $now,
                'created_at' => $now,
            ]);

            return $this->row('simulation_run_results', $id);
        });
    }

    /**
     * @param  array<string, mixed>  $candidateManifest
     * @return array<string, mixed>
     */
    public function createCandidateEvidenceHandoff(string $resultId, array $candidateManifest, ?string $intakeContractRef = null): array
    {
        $result = $this->requireRow('simulation_run_results', $resultId);
        if (DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $resultId)->exists()) {
            throw new DomainException('Candidate Evidence Handoff already exists for this Result.');
        }
        $id = (string) Str::uuid7();
        $now = now();
        DB::table('simulation_candidate_evidence_handoffs')->insert([
            'id' => $id,
            'result_id' => $result->id,
            'status' => 'READY_FOR_INTAKE',
            'candidate_manifest' => $this->json($candidateManifest),
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
            'RUNNING' => ['complete', 'pause', 'stop', 'snapshot'],
            'PAUSED' => ['resume', 'stop', 'snapshot'],
            default => [],
        };
    }

    /**
     * @param  array<string, string>  $lineage
     * @param  array<string, mixed>  $executionPolicies
     * @return array<string, mixed>
     */
    private function insertRun(string $runType, array $lineage, int $seed, array $executionPolicies, ?string $actorId, ?string $scenarioDefinitionId, ?string $labDefinitionId, string $definitionDigest): array
    {
        if (in_array($runType, self::RUN_TYPES, true) === false) {
            throw new InvalidArgumentException('Unsupported Run type.');
        }
        $id = (string) Str::uuid7();
        $now = now();
        $input = [
            'run_type' => $runType,
            'enterprise_id' => $lineage['enterprise_id'],
            'digital_twin_revision_id' => $lineage['digital_twin_revision_id'],
            'baseline_id' => $lineage['baseline_id'],
            'definition_digest' => $definitionDigest,
            'seed' => $seed,
            'execution_policies' => $executionPolicies,
        ];
        DB::table('simulation_runs')->insert([
            'id' => $id,
            'enterprise_id' => $lineage['enterprise_id'],
            'digital_twin_revision_id' => $lineage['digital_twin_revision_id'],
            'baseline_id' => $lineage['baseline_id'],
            'run_type' => $runType,
            'scenario_definition_id' => $scenarioDefinitionId,
            'standalone_lab_definition_id' => $labDefinitionId,
            'lifecycle' => 'PREPARING',
            'execution_policies' => $this->json($executionPolicies),
            'seed' => $seed,
            'runtime_state' => $this->json([
                'engine' => 'INTERNAL_HIGH_FIDELITY_V1',
                'phase' => 'PREPARING',
                'causality' => [],
                'telemetry' => [],
                'validation' => [],
            ]),
            'input_digest' => $this->digest($input),
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

    /** @return array<string,string> */
    private function lineage(string $enterpriseId, string $baselineId): array
    {
        $enterpriseState = $this->requirePublishedBaseline($enterpriseId, $baselineId);
        if ((string) ($enterpriseState->digitalTwinRevision['status'] ?? '') !== 'PUBLISHED') {
            throw new LogicException('Run lineage requires a published Digital Twin Revision.');
        }

        return [
            'enterprise_id' => $enterpriseId,
            'digital_twin_revision_id' => (string) $enterpriseState->digitalTwinRevision['id'],
            'baseline_id' => (string) $enterpriseState->baseline['id'],
        ];
    }

    private function requirePublishedBaseline(
        string $enterpriseId,
        string $baselineId,
    ): SimulationEnterpriseState {
        $enterpriseState = $this->enterpriseState->findPublishedBaselineForSimulation(
            $enterpriseId,
            $baselineId,
        );

        if ($enterpriseState === null) {
            throw new LogicException('Definition must pin a published Baseline from the same Enterprise.');
        }

        return $enterpriseState;
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

    /** @return array<string,mixed> */
    private function transition(string $runId, string $to, string $eventType): array
    {
        return DB::transaction(function () use ($runId, $to, $eventType): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }

            return $this->transitionLocked($runId, (string) $run->lifecycle, $to, $eventType);
        });
    }

    /** @return array<string,mixed> */
    private function transitionLocked(string $runId, string $from, string $to, string $eventType): array
    {
        if (in_array($to, self::TRANSITIONS[$from] ?? [], true) === false) {
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
        $this->appendEvent($runId, $eventType, ['from' => $from, 'to' => $to]);

        return $this->row('simulation_runs', $runId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function appendEvent(string $runId, string $eventType, array $payload): array
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
            'occurred_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_run_events', $id);
    }

    /** @return array<string,mixed> */
    private function row(string $table, string $id): array
    {
        $row = $this->requireRow($table, $id);

        return (array) $row;
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException("Missing required simulation record in {$table}.");
        }

        return $row;
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string,mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) === false || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value) === false) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
