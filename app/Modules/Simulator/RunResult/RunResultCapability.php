<?php

namespace App\Modules\Simulator\RunResult;

use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use stdClass;

final class RunResultCapability
{
    public function __construct(
        private readonly SimulationEnterpriseStateReader $enterpriseState,
        private readonly DeterministicSimulationEngine $engine,
    ) {}

    /**
     * @param  array<string,mixed>  $executionPolicies
     * @return array<string,mixed>
     */
    public function prepareStandaloneLabRun(
        string $labDefinitionId,
        int $seed,
        array $executionPolicies = [],
        ?string $actorId = null,
    ): array {
        return DB::transaction(function () use ($labDefinitionId, $seed, $executionPolicies, $actorId): array {
            $lab = $this->requireRow('simulation_lab_definitions', $labDefinitionId);
            if ((string) $lab->status !== 'PUBLISHED') {
                throw new LogicException('Standalone Lab Run requires a published Lab Definition.');
            }

            $run = $this->insertRun(
                RunResultVocabulary::RUN_STANDALONE_LAB,
                $this->lineage((string) $lab->enterprise_id, (string) $lab->baseline_id),
                $seed,
                $executionPolicies,
                $actorId,
                null,
                $labDefinitionId,
                (string) $lab->digest,
            );
            $this->appendEvent((string) $run->id, 'RUN_PREPARED', [
                'run_type' => RunResultVocabulary::RUN_STANDALONE_LAB,
                'lab_definition_id' => $labDefinitionId,
                'engine' => 'INTERNAL_HIGH_FIDELITY_V1',
            ]);
            $this->captureSnapshotLocked($run);

            return $this->row('simulation_runs', (string) $run->id);
        });
    }

    /**
     * @param  array<string,mixed>  $executionPolicies
     * @return array<string,mixed>
     */
    public function prepareScenarioRun(
        string $scenarioDefinitionId,
        int $seed,
        array $executionPolicies = [],
        ?string $actorId = null,
    ): array {
        return DB::transaction(function () use ($scenarioDefinitionId, $seed, $executionPolicies, $actorId): array {
            $scenario = $this->requireRow('simulation_scenario_definitions', $scenarioDefinitionId);
            if ((string) $scenario->status !== 'PUBLISHED') {
                throw new LogicException('Scenario Run requires a published Scenario Definition.');
            }

            $run = $this->insertRun(
                RunResultVocabulary::RUN_SCENARIO,
                $this->lineage((string) $scenario->enterprise_id, (string) $scenario->baseline_id),
                $seed,
                $executionPolicies,
                $actorId,
                $scenarioDefinitionId,
                null,
                (string) $scenario->digest,
            );
            $references = DB::table('simulation_scenario_lab_references')
                ->where('scenario_definition_id', $scenarioDefinitionId)
                ->orderBy('ordinal')
                ->get();
            foreach ($references as $reference) {
                $this->instantiateLabModule((string) $run->id, $reference);
            }
            $this->appendEvent((string) $run->id, 'RUN_PREPARED', [
                'run_type' => RunResultVocabulary::RUN_SCENARIO,
                'scenario_definition_id' => $scenarioDefinitionId,
                'lab_module_instance_count' => $references->count(),
                'engine' => 'INTERNAL_HIGH_FIDELITY_V1',
            ]);
            $this->captureSnapshotLocked($run);

            return $this->row('simulation_runs', (string) $run->id);
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

    /**
     * @param  array<string,mixed>  $details
     * @return array<string,mixed>
     */
    public function fail(string $runId, string $reasonCode, array $details = []): array
    {
        if (trim($reasonCode) === '') {
            throw new InvalidArgumentException('Run failure reason code is required.');
        }

        return DB::transaction(function () use ($runId, $reasonCode, $details): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }

            return $this->transitionLocked($run, 'FAILED', 'RUN_FAILED', [
                'reason_code' => $reasonCode,
                'details' => $details,
            ]);
        });
    }

    /** @return array<string,mixed> */
    public function captureSnapshot(string $runId): array
    {
        return DB::transaction(function () use ($runId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null) {
                throw new DomainException('Run not found.');
            }

            return $this->captureSnapshotLocked($run);
        });
    }

    /** @return array<string,mixed> */
    public function executeInternal(string $runId): array
    {
        return DB::transaction(function () use ($runId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || (string) $run->lifecycle !== 'RUNNING') {
                throw new DomainException('Internal high-fidelity execution requires a RUNNING Run.');
            }

            $state = $this->enterpriseState->findForSimulation(
                (string) $run->enterprise_id,
                (string) $run->digital_twin_revision_id,
                (string) $run->baseline_id,
            );
            if ($state === null) {
                throw new DomainException('Run lineage references missing Enterprise-owned state.');
            }

            $definition = $this->runDefinition($run);
            $moduleInstances = DB::table('simulation_run_lab_module_instances')
                ->where('run_id', $runId)
                ->orderBy('instance_key')
                ->get()
                ->map(fn (stdClass $instance): array => [
                    'lab_definition_id' => (string) $instance->lab_definition_id,
                    'instance_key' => (string) $instance->instance_key,
                    'state' => $this->decodeJson($instance->state),
                ])
                ->all();
            $execution = $this->engine->execute(
                [
                    'run_type' => (string) $run->run_type,
                    'seed' => (int) $run->seed,
                    'input_digest' => (string) $run->input_digest,
                ],
                [
                    'baseline_digest' => (string) ($state->baseline['digest'] ?? ''),
                    'digital_twin_digest' => (string) ($state->digitalTwinRevision['digest'] ?? ''),
                ],
                $definition,
                $moduleInstances,
            );
            $runtimeState = $this->decodeJson($run->runtime_state);
            $runtimeState['engine'] = $execution['engine'];
            $runtimeState['trace_digest'] = $execution['trace_digest'];
            $runtimeState['causality'] = $execution['causality'];
            $runtimeState['telemetry'] = $execution['telemetry'];
            $runtimeState['validation'] = $execution['validation'];
            DB::table('simulation_runs')->where('id', $runId)->update([
                'runtime_state' => $this->json($runtimeState),
                'updated_at' => now(),
            ]);
            foreach ($execution['events'] as $event) {
                $this->appendEvent($runId, $event['event_type'], $event['payload']);
            }

            $run = $this->requireRow('simulation_runs', $runId);
            $this->captureSnapshotLocked($run);

            return $this->transitionLocked($run, 'COMPLETED', 'RUN_COMPLETED');
        });
    }

    /**
     * @param  list<mixed>  $artifacts
     * @return array<string,mixed>
     */
    public function sealResult(
        string $runId,
        string $outcome,
        string $summaryAr,
        ?float $score = null,
        array $artifacts = [],
        ?string $actorId = null,
    ): array {
        RunResultVocabulary::assertOutcome($outcome);
        RunResultVocabulary::assertScore($score);
        if (trim($summaryAr) === '') {
            throw new InvalidArgumentException('Result summary is required.');
        }

        return DB::transaction(function () use ($runId, $outcome, $summaryAr, $score, $artifacts, $actorId): array {
            $run = DB::table('simulation_runs')->where('id', $runId)->lockForUpdate()->first();
            if ($run === null || RunResultVocabulary::isTerminal((string) $run->lifecycle) === false) {
                throw new DomainException('Result can be sealed only for a terminal Run.');
            }
            if (DB::table('simulation_run_results')->where('run_id', $runId)->exists()) {
                throw new DomainException('A sealed Result already exists for this Run.');
            }

            $sealedPayload = $this->sealedRunPayload($run);
            $timeline = $this->eventTimeline($runId);
            $resultId = (string) Str::uuid7();
            $now = now();
            DB::table('simulation_run_results')->insert([
                'id' => $resultId,
                'run_id' => $runId,
                'outcome' => $outcome,
                'score' => $score,
                'summary_ar' => $summaryAr,
                'sealed_payload' => $this->json($sealedPayload),
                'replay_timeline' => $this->json($timeline),
                'artifacts' => $this->json($artifacts),
                'sealed_at' => $now,
                'created_at' => $now,
            ]);
            $this->insertResultRevision(
                $resultId,
                1,
                $outcome,
                $score,
                $summaryAr,
                $sealedPayload,
                $timeline,
                $artifacts,
                null,
                $actorId,
                $now,
            );

            return $this->result($resultId);
        });
    }

    /**
     * Add a sealed correction revision without overwriting the original Result record.
     *
     * @param  list<mixed>|null  $artifacts
     * @return array<string,mixed>
     */
    public function correctResult(
        string $resultId,
        string $outcome,
        string $summaryAr,
        string $correctionReason,
        ?float $score = null,
        ?array $artifacts = null,
        ?string $actorId = null,
    ): array {
        RunResultVocabulary::assertOutcome($outcome);
        RunResultVocabulary::assertScore($score);
        if (trim($summaryAr) === '' || trim($correctionReason) === '') {
            throw new InvalidArgumentException('Correction summary and reason are required.');
        }

        return DB::transaction(function () use ($resultId, $outcome, $summaryAr, $correctionReason, $score, $artifacts, $actorId): array {
            $base = DB::table('simulation_run_results')->where('id', $resultId)->lockForUpdate()->first();
            if ($base === null) {
                throw new DomainException('Result not found.');
            }

            $this->ensureBaseRevision($base);
            $current = $this->effectiveRevision($base);
            $revision = (int) DB::table('simulation_run_result_revisions')->where('result_id', $resultId)->max('revision') + 1;
            $this->insertResultRevision(
                $resultId,
                $revision,
                $outcome,
                $score,
                $summaryAr,
                $current['sealed_payload'],
                $current['replay_timeline'],
                $artifacts ?? $current['artifacts'],
                $correctionReason,
                $actorId,
                now(),
            );

            return $this->result($resultId);
        });
    }

    /** @return array<string,mixed> */
    public function result(string $resultId): array
    {
        $base = $this->requireRow('simulation_run_results', $resultId);
        $effective = $this->effectiveRevision($base);

        return [
            'id' => (string) $base->id,
            'run_id' => (string) $base->run_id,
            'sealed_at' => (string) $base->sealed_at,
            'base_outcome' => (string) $base->outcome,
            'effective_revision' => $effective,
        ];
    }

    /** @return array<string,mixed> */
    public function replay(string $resultId): array
    {
        $result = $this->result($resultId);
        $effective = $result['effective_revision'];
        $payload = $effective['sealed_payload'];

        return [
            'kind' => 'EVENT_SEMANTIC_REPLAY',
            'result_id' => $resultId,
            'result_revision' => $effective['revision'],
            'run_id' => $result['run_id'],
            'timeline' => $effective['replay_timeline'],
            'artifacts' => $effective['artifacts'],
            'runtime_snapshots' => $payload['runtime_snapshots'] ?? $payload['snapshots'] ?? [],
            'full_environment_state_replay' => false,
        ];
    }

    /** @return array<string,mixed> */
    public function afterActionReview(string $resultId): array
    {
        $result = $this->result($resultId);
        $replay = $this->replay($resultId);
        $effective = $result['effective_revision'];
        $runtimeState = $effective['sealed_payload']['runtime_state'] ?? [];
        $eventCounts = [];
        foreach ($replay['timeline'] as $event) {
            $type = (string) ($event['event_type'] ?? 'UNKNOWN');
            $eventCounts[$type] = ($eventCounts[$type] ?? 0) + 1;
        }

        return [
            'kind' => 'AFTER_ACTION_REVIEW',
            'result_id' => $resultId,
            'result_revision' => $effective['revision'],
            'run_id' => $result['run_id'],
            'outcome' => $effective['outcome'],
            'score' => $effective['score'],
            'summary_ar' => $effective['summary_ar'],
            'facts' => [
                'event_count' => count($replay['timeline']),
                'event_types' => $eventCounts,
                'artifact_count' => count($replay['artifacts']),
                'snapshot_count' => count($replay['runtime_snapshots']),
                'causality' => $runtimeState['causality'] ?? [],
                'telemetry' => $runtimeState['telemetry'] ?? [],
                'validation' => $runtimeState['validation'] ?? [],
            ],
            'interpretation' => [
                'validation_passed' => (bool) ($runtimeState['validation']['passed'] ?? false),
                'causal_branch' => $runtimeState['telemetry']['causal_branch'] ?? null,
                'governance_boundary' => 'RESULT_ANALYSIS_ONLY',
            ],
        ];
    }

    /**
     * @param  list<string>  $resultIds
     * @return array<string,mixed>
     */
    public function compareResults(array $resultIds): array
    {
        $resultIds = array_values(array_unique($resultIds));
        if (count($resultIds) < 2) {
            throw new InvalidArgumentException('Compare Runs requires at least two distinct Results.');
        }
        if (count($resultIds) > 10) {
            throw new InvalidArgumentException('Compare Runs accepts at most ten Results per comparison.');
        }

        $entries = [];
        foreach ($resultIds as $resultId) {
            $result = $this->result($resultId);
            $effective = $result['effective_revision'];
            $payload = $effective['sealed_payload'];
            $runtimeState = $payload['runtime_state'] ?? [];
            $definitionId = $payload['scenario_definition_id'] ?? $payload['standalone_lab_definition_id'] ?? null;
            $entries[] = [
                'result_id' => $resultId,
                'result_revision' => $effective['revision'],
                'run_id' => $result['run_id'],
                'run_type' => $payload['run_type'] ?? null,
                'run_lifecycle' => $payload['run_lifecycle'] ?? null,
                'definition_id' => $definitionId,
                'outcome' => $effective['outcome'],
                'score' => $effective['score'],
                'trace_digest' => $runtimeState['trace_digest'] ?? null,
                'telemetry' => $runtimeState['telemetry'] ?? [],
                'validation' => $runtimeState['validation'] ?? [],
                'event_count' => count($effective['replay_timeline']),
                'artifact_count' => count($effective['artifacts']),
            ];
        }
        $first = $entries[0];
        $sameRunType = count(array_unique(array_column($entries, 'run_type'), SORT_REGULAR)) === 1;
        $sameDefinition = count(array_unique(array_column($entries, 'definition_id'), SORT_REGULAR)) === 1;
        foreach ($entries as $index => $entry) {
            $scoreDelta = null;
            if (is_numeric($first['score']) && is_numeric($entry['score'])) {
                $scoreDelta = (float) $entry['score'] - (float) $first['score'];
            }
            $entries[$index]['score_delta_from_first'] = $scoreDelta;
        }

        return [
            'kind' => 'COMPARE_RUNS',
            'comparable_scope' => $sameRunType && $sameDefinition,
            'same_run_type' => $sameRunType,
            'same_definition' => $sameDefinition,
            'results' => $entries,
        ];
    }

    /**
     * Creates only the W03 handoff boundary. It does not create canonical Candidate Evidence.
     *
     * @param  list<string>  $criterionScope
     * @param  list<mixed>  $selectedMaterial
     * @return array<string,mixed>
     */
    public function createCandidateEvidenceHandoff(
        string $resultId,
        string $claim,
        string $purpose,
        string $subject,
        array $criterionScope = [],
        array $selectedMaterial = [],
        ?string $intakeContractRef = 'progress-evidence-intake:v1',
    ): array {
        foreach (['claim' => $claim, 'purpose' => $purpose, 'subject' => $subject] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("Candidate Evidence Handoff {$field} is required.");
            }
        }

        return DB::transaction(function () use ($resultId, $claim, $purpose, $subject, $criterionScope, $selectedMaterial, $intakeContractRef): array {
            $base = DB::table('simulation_run_results')->where('id', $resultId)->lockForUpdate()->first();
            if ($base === null) {
                throw new DomainException('Result not found.');
            }
            if (DB::table('simulation_candidate_evidence_handoffs')->where('result_id', $resultId)->exists()) {
                throw new DomainException('Candidate Evidence Handoff already exists for this Result.');
            }

            $effective = $this->effectiveRevision($base);
            $id = (string) Str::uuid7();
            $now = now();
            $manifest = [
                'handoff_kind' => 'RUN_RESULT_CANDIDATE_EVIDENCE_HANDOFF',
                'source' => [
                    'result_id' => $resultId,
                    'result_revision' => $effective['revision'],
                    'run_id' => (string) $base->run_id,
                    'result_integrity' => $this->digest([
                        'result_id' => $resultId,
                        'revision' => $effective['revision'],
                        'outcome' => $effective['outcome'],
                        'sealed_payload' => $effective['sealed_payload'],
                        'replay_timeline' => $effective['replay_timeline'],
                        'artifacts' => $effective['artifacts'],
                    ]),
                ],
                'claim' => $claim,
                'purpose' => $purpose,
                'subject' => $subject,
                'criterion_scope' => $criterionScope,
                'selected_material' => $selectedMaterial,
            ];
            DB::table('simulation_candidate_evidence_handoffs')->insert([
                'id' => $id,
                'result_id' => $resultId,
                'status' => 'READY_FOR_INTAKE',
                'candidate_manifest' => $this->json($manifest),
                'intake_contract_ref' => $intakeContractRef,
                'handed_off_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row('simulation_candidate_evidence_handoffs', $id);
        });
    }

    /** @return array<string,mixed> */
    public function markHandoffHandedOff(string $handoffId): array
    {
        return DB::transaction(function () use ($handoffId): array {
            $handoff = DB::table('simulation_candidate_evidence_handoffs')->where('id', $handoffId)->lockForUpdate()->first();
            if ($handoff === null) {
                throw new DomainException('Candidate Evidence Handoff not found.');
            }
            if ((string) $handoff->status !== 'READY_FOR_INTAKE') {
                throw new DomainException('Only a READY_FOR_INTAKE handoff can be handed off.');
            }
            $now = now();
            DB::table('simulation_candidate_evidence_handoffs')->where('id', $handoffId)->update([
                'status' => 'HANDED_OFF',
                'handed_off_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->row('simulation_candidate_evidence_handoffs', $handoffId);
        });
    }

    /** @return list<string> */
    public function availableActions(string $lifecycle): array
    {
        return RunResultVocabulary::availableActions($lifecycle);
    }

    /**
     * @param  array<string,string>  $lineage
     * @param  array<string,mixed>  $executionPolicies
     */
    private function insertRun(
        string $runType,
        array $lineage,
        int $seed,
        array $executionPolicies,
        ?string $actorId,
        ?string $scenarioDefinitionId,
        ?string $labDefinitionId,
        string $definitionDigest,
    ): stdClass {
        RunResultVocabulary::assertRunType($runType);
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

        return $this->requireRow('simulation_runs', $id);
    }

    /** @return array<string,string> */
    private function lineage(string $enterpriseId, string $baselineId): array
    {
        $state = $this->enterpriseState->findPublishedBaselineForSimulation($enterpriseId, $baselineId);
        if ($state === null || (string) ($state->digitalTwinRevision['status'] ?? '') !== 'PUBLISHED') {
            throw new LogicException('Run lineage requires a published Digital Twin Revision and Baseline.');
        }

        return [
            'enterprise_id' => $enterpriseId,
            'digital_twin_revision_id' => (string) $state->digitalTwinRevision['id'],
            'baseline_id' => (string) $state->baseline['id'],
        ];
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
            'state' => $this->json([
                'status' => 'PREPARED',
                'policy' => $this->decodeJson($reference->policy),
            ]),
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

            return $this->transitionLocked($run, $to, $eventType);
        });
    }

    /**
     * @param  array<string,mixed>  $extraPayload
     * @return array<string,mixed>
     */
    private function transitionLocked(stdClass $run, string $to, string $eventType, array $extraPayload = []): array
    {
        $from = (string) $run->lifecycle;
        RunResultVocabulary::assertTransition($from, $to);
        $state = $this->decodeJson($run->runtime_state);
        $state['phase'] = $to;
        if ($to === 'FAILED') {
            $state['failure'] = $extraPayload;
        }
        $now = now();
        $timestamps = match ($to) {
            'READY' => ['ready_at' => $now],
            'RUNNING' => $run->started_at === null ? ['started_at' => $now] : [],
            'COMPLETED' => ['completed_at' => $now],
            'STOPPED' => ['stopped_at' => $now],
            'FAILED' => ['failed_at' => $now],
            default => [],
        };
        DB::table('simulation_runs')->where('id', $run->id)->update($timestamps + [
            'lifecycle' => $to,
            'runtime_state' => $this->json($state),
            'updated_at' => $now,
        ]);
        $this->appendEvent((string) $run->id, $eventType, ['from' => $from, 'to' => $to] + $extraPayload);

        return $this->row('simulation_runs', (string) $run->id);
    }

    /** @return array<string,mixed> */
    private function captureSnapshotLocked(stdClass $run): array
    {
        if (RunResultVocabulary::isTerminal((string) $run->lifecycle)) {
            throw new DomainException('Terminal Run snapshots are sealed by historical Result data.');
        }
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
            'digital_twin_revision_id' => $run->digital_twin_revision_id,
            'baseline_id' => $run->baseline_id,
            'state' => $this->json($state),
            'state_digest' => $this->digest($state),
            'captured_at' => $now,
            'created_at' => $now,
        ]);

        return $this->row('simulation_runtime_snapshots', $id);
    }

    /** @param array<string,mixed> $payload */
    private function appendEvent(string $runId, string $eventType, array $payload): void
    {
        $sequence = (int) DB::table('simulation_run_events')->where('run_id', $runId)->max('sequence') + 1;
        $now = now();
        DB::table('simulation_run_events')->insert([
            'id' => (string) Str::uuid7(),
            'run_id' => $runId,
            'sequence' => $sequence,
            'event_type' => $eventType,
            'payload' => $this->json($payload),
            'occurred_at' => $now,
            'created_at' => $now,
        ]);
    }

    /** @return array<string,mixed> */
    private function runDefinition(stdClass $run): array
    {
        if ((string) $run->run_type === RunResultVocabulary::RUN_SCENARIO) {
            $definition = $this->requireRow('simulation_scenario_definitions', (string) $run->scenario_definition_id);

            return [
                'id' => (string) $definition->id,
                'digest' => (string) $definition->digest,
                'orchestration' => $this->decodeJson($definition->orchestration),
                'validation' => $this->decodeJson($definition->validation),
            ];
        }
        if ((string) $run->run_type === RunResultVocabulary::RUN_STANDALONE_LAB) {
            $definition = $this->requireRow('simulation_lab_definitions', (string) $run->standalone_lab_definition_id);

            return [
                'id' => (string) $definition->id,
                'digest' => (string) $definition->digest,
                'configuration' => $this->decodeJson($definition->configuration),
                'validation' => $this->decodeJson($definition->validation),
            ];
        }

        throw new DomainException('Run has an unsupported type.');
    }

    /** @return array<string,mixed> */
    private function sealedRunPayload(stdClass $run): array
    {
        return [
            'run_id' => (string) $run->id,
            'run_type' => (string) $run->run_type,
            'run_lifecycle' => (string) $run->lifecycle,
            'enterprise_id' => (string) $run->enterprise_id,
            'digital_twin_revision_id' => (string) $run->digital_twin_revision_id,
            'baseline_id' => (string) $run->baseline_id,
            'scenario_definition_id' => $run->scenario_definition_id === null ? null : (string) $run->scenario_definition_id,
            'standalone_lab_definition_id' => $run->standalone_lab_definition_id === null ? null : (string) $run->standalone_lab_definition_id,
            'seed' => (int) $run->seed,
            'input_digest' => (string) $run->input_digest,
            'runtime_state' => $this->decodeJson($run->runtime_state),
            'runtime_snapshots' => DB::table('simulation_runtime_snapshots')
                ->where('run_id', $run->id)
                ->orderBy('sequence')
                ->get()
                ->map(fn (stdClass $snapshot): array => [
                    'id' => (string) $snapshot->id,
                    'sequence' => (int) $snapshot->sequence,
                    'event_sequence' => (int) $snapshot->event_sequence,
                    'state_digest' => (string) $snapshot->state_digest,
                    'captured_at' => (string) $snapshot->captured_at,
                ])
                ->all(),
            'timestamps' => [
                'prepared_at' => $run->prepared_at === null ? null : (string) $run->prepared_at,
                'ready_at' => $run->ready_at === null ? null : (string) $run->ready_at,
                'started_at' => $run->started_at === null ? null : (string) $run->started_at,
                'completed_at' => $run->completed_at === null ? null : (string) $run->completed_at,
                'stopped_at' => $run->stopped_at === null ? null : (string) $run->stopped_at,
                'failed_at' => $run->failed_at === null ? null : (string) $run->failed_at,
            ],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function eventTimeline(string $runId): array
    {
        return DB::table('simulation_run_events')
            ->where('run_id', $runId)
            ->orderBy('sequence')
            ->get()
            ->map(fn (stdClass $event): array => [
                'sequence' => (int) $event->sequence,
                'event_type' => (string) $event->event_type,
                'payload' => $this->decodeJson($event->payload),
                'occurred_at' => (string) $event->occurred_at,
            ])
            ->all();
    }

    /**
     * @param  array<string,mixed>  $sealedPayload
     * @param  list<array<string,mixed>>  $timeline
     * @param  list<mixed>  $artifacts
     */
    private function insertResultRevision(
        string $resultId,
        int $revision,
        string $outcome,
        ?float $score,
        string $summaryAr,
        array $sealedPayload,
        array $timeline,
        array $artifacts,
        ?string $correctionReason,
        ?string $actorId,
        mixed $sealedAt,
    ): void {
        DB::table('simulation_run_result_revisions')->insert([
            'id' => (string) Str::uuid7(),
            'result_id' => $resultId,
            'revision' => $revision,
            'outcome' => $outcome,
            'score' => $score,
            'summary_ar' => $summaryAr,
            'sealed_payload' => $this->json($sealedPayload),
            'replay_timeline' => $this->json($timeline),
            'artifacts' => $this->json($artifacts),
            'correction_reason' => $correctionReason,
            'created_by' => $actorId,
            'sealed_at' => $sealedAt,
            'created_at' => $sealedAt,
        ]);
    }

    private function ensureBaseRevision(stdClass $base): void
    {
        if (DB::table('simulation_run_result_revisions')->where('result_id', $base->id)->exists()) {
            return;
        }
        $this->insertResultRevision(
            (string) $base->id,
            1,
            (string) $base->outcome,
            $base->score === null ? null : (float) $base->score,
            (string) $base->summary_ar,
            $this->decodeJson($base->sealed_payload),
            $this->decodeJsonList($base->replay_timeline),
            $this->decodeJsonList($base->artifacts),
            null,
            null,
            $base->sealed_at,
        );
    }

    /** @return array<string,mixed> */
    private function effectiveRevision(stdClass $base): array
    {
        $revision = DB::table('simulation_run_result_revisions')
            ->where('result_id', $base->id)
            ->orderByDesc('revision')
            ->first();
        if ($revision === null) {
            return [
                'revision' => 1,
                'outcome' => (string) $base->outcome,
                'score' => $base->score === null ? null : (float) $base->score,
                'summary_ar' => (string) $base->summary_ar,
                'sealed_payload' => $this->decodeJson($base->sealed_payload),
                'replay_timeline' => $this->decodeJsonList($base->replay_timeline),
                'artifacts' => $this->decodeJsonList($base->artifacts),
                'correction_reason' => null,
                'sealed_at' => (string) $base->sealed_at,
            ];
        }

        return [
            'revision' => (int) $revision->revision,
            'outcome' => (string) $revision->outcome,
            'score' => $revision->score === null ? null : (float) $revision->score,
            'summary_ar' => (string) $revision->summary_ar,
            'sealed_payload' => $this->decodeJson($revision->sealed_payload),
            'replay_timeline' => $this->decodeJsonList($revision->replay_timeline),
            'artifacts' => $this->decodeJsonList($revision->artifacts),
            'correction_reason' => $revision->correction_reason === null ? null : (string) $revision->correction_reason,
            'sealed_at' => (string) $revision->sealed_at,
        ];
    }

    /** @return array<string,mixed> */
    private function row(string $table, string $id): array
    {
        return (array) $this->requireRow($table, $id);
    }

    private function requireRow(string $table, string $id): stdClass
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new DomainException("Required Run/Result record not found in {$table}.");
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

    /** @return list<mixed> */
    private function decodeJsonList(mixed $value): array
    {
        $decoded = $this->decodeJson($value);

        return array_is_list($decoded) ? $decoded : [];
    }

    private function digest(mixed $value): string
    {
        return hash(
            'sha256',
            json_encode(
                $this->canonicalize($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
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
