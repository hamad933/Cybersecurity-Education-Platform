<?php

namespace App\Modules\Simulator\Application;

use App\Modules\Enterprise\Application\EnterpriseBaselineService;
use App\Modules\Simulator\Models\DecisionTrace;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\ScenarioRun;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class Vs003SimulationService
{
    public function __construct(private readonly EnterpriseBaselineService $enterprise) {}

    /** @return array<string,mixed> */
    public function run(string $caseId, int $seed, string $idempotencyKey, string $actorId): array
    {
        $this->assertRequestEnvelope($seed, $idempotencyKey, $actorId);

        // Idempotency is pinned to the request that created the historical run,
        // not to whichever scenario/rule revision is newest today.
        $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            if (
                (string) $existing->actor_id !== $actorId
                || (string) $existing->case_id !== $caseId
                || (int) $existing->seed !== $seed
                || $existing->status !== 'completed'
            ) {
                throw new IdempotencyConflict('VS-003 idempotency key conflicts with actor, case, seed, or run state.');
            }

            return $this->resultForRun($existing);
        }

        $context = $this->publishedCaseContext($caseId);
        $normalizedInput = [
            'dataset_revision_id' => (string) $context['dataset']->id,
            'event_ids' => array_values(array_map(
                static fn (array $event): string => (string) $event['id'],
                $context['events'],
            )),
            'timezone' => (string) $context['dataset']->timezone,
            'clock_assumption' => 'event timestamps are immutable UTC values',
        ];
        $inputDigest = $this->digest($normalizedInput);
        $requestDigest = $this->digest([
            'actor_id' => $actorId,
            'case_id' => $caseId,
            'seed' => $seed,
            'scenario_revision_id' => (string) $context['scenario']->id,
            'rule_set_revision_id' => (string) $context['rule']->id,
            'enterprise_baseline_revision_id' => (string) $context['scenario']->enterprise_baseline_revision_id,
            'input_digest' => $inputDigest,
        ]);

        $baseline = $this->enterprise->publishedRevision((string) $context['scenario']->enterprise_baseline_revision_id);

        return DB::transaction(function () use (
            $context,
            $caseId,
            $seed,
            $idempotencyKey,
            $actorId,
            $normalizedInput,
            $inputDigest,
            $requestDigest,
            $baseline,
        ): array {
            $run = ScenarioRun::query()->create([
                'scenario_revision_id' => $context['scenario']->id,
                'rule_set_revision_id' => $context['rule']->id,
                'enterprise_baseline_revision_id' => $context['scenario']->enterprise_baseline_revision_id,
                'actor_id' => $actorId,
                'case_id' => $caseId,
                'seed' => $seed,
                'status' => 'running',
                'ordered_actions' => [
                    'validate_pinned_dataset',
                    'normalize_timeline',
                    'evaluate_typed_rule',
                    'create_alert',
                    'await_actor_triage',
                ],
                'normalized_input' => $normalizedInput,
                'input_digest' => $inputDigest,
                'request_digest' => $requestDigest,
                'baseline_digest_before' => $baseline['snapshot_digest'],
                'idempotency_key' => $idempotencyKey,
            ]);

            $trace = $this->evaluate(
                $context['events'],
                $caseId,
                (string) $run->id,
                (string) $context['dataset']->digest,
                (string) $context['scenario']->id,
                (string) $context['rule']->id,
                (string) $context['scenario']->enterprise_baseline_revision_id,
                $normalizedInput,
            );
            if (($context['case']->expected_outcome ?? null) !== $trace['outcome']) {
                throw new LogicException('The pinned VS-003 case outcome conflicts with its deterministic dataset.');
            }

            DecisionTrace::query()->create([
                'scenario_run_id' => $run->id,
                'trace' => $trace,
                'output_digest' => $trace['timeline_digest'],
            ]);

            $run->forceFill([
                'status' => 'completed',
                'outcome' => $trace['outcome'],
                'trace_digest' => $trace['timeline_digest'],
                'baseline_digest_after' => $baseline['snapshot_digest'],
                'completed_at' => now(),
            ])->save();

            DB::table('vs003_investigation_alerts')->insert([
                'id' => (string) Str::uuid7(),
                'scenario_run_id' => $run->id,
                'rule_id' => 'AUTH-ANOMALY-V1',
                'state' => $trace['alert_state'],
                'severity' => $trace['severity'],
                'timeline_digest' => $trace['timeline_digest'],
                'rationale' => json_encode($trace['detection_rationale'], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['run' => $run->fresh()->toArray(), 'trace' => $trace];
        });
    }

    /** @return array<string,mixed> */
    public function triage(
        string $runId,
        string $actorId,
        string $selectedOutcome,
        string $rationale,
    ): array {
        $run = ScenarioRun::query()->findOrFail($runId);
        if ((string) $run->actor_id !== $actorId) {
            throw new LogicException('Triage is actor-bound.');
        }
        if ($run->status !== 'completed') {
            throw new LogicException('Only a completed investigation can be triaged.');
        }

        $trace = DecisionTrace::query()->where('scenario_run_id', $runId)->firstOrFail()->tracePayload();
        if ($selectedOutcome !== ($trace['outcome'] ?? null)) {
            throw new LogicException('Triage outcome does not match the bounded evidence.');
        }

        $normalizedRationale = trim($rationale);
        if (mb_strlen($normalizedRationale) < 12) {
            throw new LogicException('Triage rationale must contain at least 12 characters.');
        }
        $digest = $this->digest([
            'run_id' => $runId,
            'actor_id' => $actorId,
            'outcome' => $selectedOutcome,
            'severity' => $trace['severity'],
            'scope' => $trace['scope'],
            'confidence' => $trace['confidence'],
            'alternative_hypotheses' => $trace['alternative_hypotheses'],
            'missing_data' => $trace['missing_data'],
            'rationale' => $normalizedRationale,
        ]);

        $existing = DB::table('vs003_triage_records')->where('scenario_run_id', $runId)->first();
        if ($existing !== null) {
            if ((string) $existing->actor_id !== $actorId || ! hash_equals((string) $existing->digest, $digest)) {
                throw new LogicException('The immutable triage record conflicts with this request.');
            }

            return (array) $existing;
        }

        $record = [
            'id' => (string) Str::uuid7(),
            'scenario_run_id' => $runId,
            'actor_id' => $actorId,
            'outcome' => $selectedOutcome,
            'severity' => (string) $trace['severity'],
            'scope' => (string) $trace['scope'],
            'confidence' => (string) $trace['confidence'],
            'alternative_hypotheses' => json_encode($trace['alternative_hypotheses'], JSON_THROW_ON_ERROR),
            'missing_data' => json_encode($trace['missing_data'], JSON_THROW_ON_ERROR),
            'rationale' => mb_substr($normalizedRationale, 0, 1000),
            'owner' => 'LOCAL-OWNER',
            'escalated_at' => in_array($selectedOutcome, ['SUSPICIOUS', 'INCIDENT_CONFIRMED'], true) ? now() : null,
            'digest' => $digest,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('vs003_triage_records')->insert($record);

        return (array) DB::table('vs003_triage_records')->where('id', $record['id'])->first();
    }

    /** @return array{run:array<string,mixed>,trace:array<string,mixed>,triage:?array<string,mixed>} */
    public function runSnapshot(string $runId, string $actorId): array
    {
        $run = ScenarioRun::query()->findOrFail($runId);
        if ((string) $run->actor_id !== $actorId) {
            throw new LogicException('Run is actor-bound.');
        }

        $triage = DB::table('vs003_triage_records')->where('scenario_run_id', $runId)->first();

        return [
            'run' => $run->toArray(),
            'trace' => DecisionTrace::query()->where('scenario_run_id', $runId)->firstOrFail()->tracePayload(),
            'triage' => $triage === null ? null : (array) $triage,
        ];
    }

    /**
     * @param  array<string,mixed>  $control
     * @return array{run:array<string,mixed>,trace:array<string,mixed>}
     */
    public function verifyControl(
        string $originalRunId,
        string $actorId,
        array $control,
        string $idempotencyKey,
    ): array {
        $original = $this->runSnapshot($originalRunId, $actorId);
        if (($control['state'] ?? null) !== 'published' || ($control['actor_id'] ?? null) !== $actorId) {
            throw new LogicException('Verification requires this actor\'s published control revision.');
        }
        if (($control['remediates_run_id'] ?? null) !== $originalRunId) {
            throw new LogicException('The control revision does not remediate the requested run.');
        }

        $verificationInput = [
            'original_run_id' => $originalRunId,
            'original_timeline_digest' => $original['trace']['timeline_digest'],
            'dataset_revision_id' => $original['run']['normalized_input']['dataset_revision_id'] ?? null,
            'event_ids' => $original['run']['normalized_input']['event_ids'] ?? [],
            'timezone' => $original['run']['normalized_input']['timezone'] ?? 'UTC',
            'clock_assumption' => $original['run']['normalized_input']['clock_assumption'] ?? null,
            'control_revision_id' => $control['id'],
            'control_digest' => $control['digest'],
            'triage_record_id' => $control['triage_record_id'] ?? null,
        ];
        $inputDigest = $this->digest($verificationInput);
        $requestDigest = $this->digest([
            'actor_id' => $actorId,
            'original_run_id' => $originalRunId,
            'control_revision_id' => $control['id'],
            'input_digest' => $inputDigest,
        ]);

        $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            $existingInput = is_array($existing->normalized_input) ? $existing->normalized_input : [];
            if (
                (string) $existing->actor_id !== $actorId
                || (string) $existing->verification_of_run_id !== $originalRunId
                || $existing->status !== 'completed'
                || ! hash_equals((string) $existing->request_digest, $requestDigest)
                || (string) ($existingInput['control_revision_id'] ?? '') !== (string) $control['id']
            ) {
                throw new IdempotencyConflict('VS-003 verification idempotency key conflicts with actor, run, or control revision.');
            }

            return $this->resultForRun($existing);
        }

        return DB::transaction(function () use (
            $original,
            $control,
            $idempotencyKey,
            $actorId,
            $originalRunId,
            $verificationInput,
            $inputDigest,
            $requestDigest,
        ): array {
            $run = ScenarioRun::query()->create([
                'scenario_revision_id' => $original['run']['scenario_revision_id'],
                'rule_set_revision_id' => $original['run']['rule_set_revision_id'],
                'enterprise_baseline_revision_id' => $original['run']['enterprise_baseline_revision_id'],
                'actor_id' => $actorId,
                'case_id' => $original['run']['case_id'],
                'seed' => $original['run']['seed'],
                'status' => 'running',
                'ordered_actions' => [
                    'pin_original_revisions',
                    'pin_control_revision',
                    'replay_bounded_synthetic_timeline',
                    'compare_outcome_and_timeline',
                ],
                'normalized_input' => $verificationInput,
                'input_digest' => $inputDigest,
                'request_digest' => $requestDigest,
                'baseline_digest_before' => $original['run']['baseline_digest_before'],
                'idempotency_key' => $idempotencyKey,
                'verification_of_run_id' => $originalRunId,
            ]);

            $trace = $original['trace'];
            $trace['run_id'] = (string) $run->id;
            $trace['outcome'] = 'BENIGN_EXPLAINED';
            $trace['alert_state'] = 'NONE';
            $trace['severity'] = 'LOW';
            $trace['confidence'] = 'HIGH';
            $trace['normalized_input'] = $verificationInput;
            $trace['verification'] = [
                'original_run_id' => $originalRunId,
                'original_timeline_digest' => $original['trace']['timeline_digest'],
                'control_revision_id' => $control['id'],
                'control_digest' => $control['digest'],
                'triage_record_id' => $control['triage_record_id'] ?? null,
                'result' => 'CONTROL_EFFECT_OBSERVED_IN_SYNTHETIC_REPLAY',
                'live_action_performed' => false,
            ];
            unset($trace['timeline_digest']);
            $trace['timeline_digest'] = $this->semanticTraceDigest($trace);

            DecisionTrace::query()->create([
                'scenario_run_id' => $run->id,
                'trace' => $trace,
                'output_digest' => $trace['timeline_digest'],
            ]);
            $run->forceFill([
                'status' => 'completed',
                'outcome' => $trace['outcome'],
                'trace_digest' => $trace['timeline_digest'],
                'baseline_digest_after' => $original['run']['baseline_digest_after'],
                'completed_at' => now(),
            ])->save();

            DB::table('vs003_investigation_alerts')->insert([
                'id' => (string) Str::uuid7(),
                'scenario_run_id' => $run->id,
                'rule_id' => 'AUTH-ANOMALY-V1',
                'state' => 'NONE',
                'severity' => 'LOW',
                'timeline_digest' => $trace['timeline_digest'],
                'rationale' => json_encode([
                    'verification' => $trace['verification'],
                    'typed_rule' => 'AUTH-ANOMALY-V1',
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['run' => $run->fresh()->toArray(), 'trace' => $trace];
        });
    }

    /** @return array<string,mixed> */
    public function masteryFacts(string $actorId): array
    {
        $runs = ScenarioRun::query()
            ->where('actor_id', $actorId)
            ->whereIn('case_id', config('vs003.case_ids'))
            ->where('status', 'completed')
            ->get();
        $runIds = $runs->pluck('id');
        $triageRows = DB::table('vs003_triage_records')
            ->where('actor_id', $actorId)
            ->whereIn('scenario_run_id', $runIds)
            ->get();

        $correctTriage = true;
        $alternativeRecorded = false;
        foreach ($triageRows as $triage) {
            $trace = DecisionTrace::query()->where('scenario_run_id', $triage->scenario_run_id)->first()?->tracePayload();
            if (! is_array($trace)) {
                $correctTriage = false;

                continue;
            }
            $correctTriage = $correctTriage
                && $triage->outcome === ($trace['outcome'] ?? null)
                && $triage->severity === ($trace['severity'] ?? null)
                && $triage->scope === ($trace['scope'] ?? null)
                && $triage->confidence === ($trace['confidence'] ?? null)
                && mb_strlen(trim((string) $triage->rationale)) >= 12;
            $alternatives = json_decode((string) $triage->alternative_hypotheses, true, 512, JSON_THROW_ON_ERROR);
            $alternativeRecorded = $alternativeRecorded || (is_array($alternatives) && $alternatives !== []);
        }

        $triagedOutcomes = $triageRows->pluck('outcome')->all();

        return [
            'correct_triage' => $correctTriage
                && in_array('SUSPICIOUS', $triagedOutcomes, true)
                && in_array('INCIDENT_CONFIRMED', $triagedOutcomes, true),
            'alternative_hypothesis_recorded' => $alternativeRecorded,
            'triage_record_ids' => $triageRows->pluck('id')->sort()->values()->all(),
        ];
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        $runs = ScenarioRun::query()
            ->where('actor_id', $actorId)
            ->whereIn('case_id', config('vs003.case_ids'))
            ->latest()
            ->limit(25)
            ->get();
        $runIds = $runs->pluck('id');
        $traces = DecisionTrace::query()->whereIn('scenario_run_id', $runIds)->get()->keyBy('scenario_run_id');
        $alerts = DB::table('vs003_investigation_alerts')->whereIn('scenario_run_id', $runIds)->get()->keyBy('scenario_run_id');
        $triage = DB::table('vs003_triage_records')->whereIn('scenario_run_id', $runIds)->get()->keyBy('scenario_run_id');

        return [
            'runs' => $runs->map(function (ScenarioRun $run) use ($traces, $alerts, $triage): array {
                $trace = $traces->get($run->id);
                $alert = $alerts->get($run->id);
                $triageRecord = $triage->get($run->id);

                return $run->toArray() + [
                    'trace' => $trace?->tracePayload(),
                    'alert' => $alert === null ? null : (array) $alert,
                    'triage' => $triageRecord === null ? null : (array) $triageRecord,
                ];
            })->all(),
        ];
    }

    /** @return array<string,mixed> */
    private function publishedCaseContext(string $caseId): array
    {
        if (! in_array($caseId, config('vs003.case_ids'), true)) {
            throw new LogicException('Unknown VS-003 case.');
        }

        $scenario = ScenarioRevision::query()
            ->where('scenario_id', config('vs003.scenario_id'))
            ->where('state', 'published')
            ->latest('revision')
            ->firstOrFail();
        $rule = SimulatorRuleRevision::query()
            ->whereKey($scenario->rule_set_revision_id)
            ->where('state', 'approved')
            ->firstOrFail();
        $rules = is_array($rule->rules) ? $rule->rules : [];
        if (! hash_equals((string) $rule->digest, $this->digest($rules))) {
            throw new LogicException('Pinned VS-003 rule digest verification failed.');
        }
        if (($rules['behavior_version'] ?? null) !== config('vs003.behavior_version')) {
            throw new LogicException('Unsupported pinned VS-003 behavior version.');
        }
        $scenarioCases = is_array($scenario->cases) ? $scenario->cases : [];
        if (! hash_equals((string) $scenario->digest, $this->digest($scenarioCases))) {
            throw new LogicException('Pinned VS-003 scenario digest verification failed.');
        }

        $case = DB::table('vs003_investigation_cases')
            ->where('scenario_revision_id', $scenario->id)
            ->where('case_id', $caseId)
            ->first();
        if ($case === null) {
            throw new LogicException('Unknown VS-003 case.');
        }

        $definition = json_decode((string) $case->definition, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($definition) || array_is_list($definition)) {
            throw new LogicException('Pinned VS-003 case definition is malformed.');
        }
        if (! hash_equals((string) $case->digest, $this->digest($definition))) {
            throw new LogicException('Pinned VS-003 case digest verification failed.');
        }
        if (
            ($definition['case_id'] ?? null) !== $caseId
            || ($definition['expected'] ?? null) !== $case->expected_outcome
            || ! in_array($case->expected_outcome, config('vs003.outcomes'), true)
        ) {
            throw new LogicException('Pinned VS-003 case metadata is inconsistent.');
        }
        $dataset = DB::table('vs003_telemetry_dataset_revisions')
            ->where('id', $case->dataset_revision_id)
            ->where('state', 'published')
            ->first();
        if ($dataset === null) {
            throw new LogicException('Pinned VS-003 dataset is unavailable.');
        }
        if ((string) $dataset->timezone !== 'UTC') {
            throw new LogicException('VS-003 supports only the declared UTC dataset baseline.');
        }

        $allEvents = json_decode((string) $dataset->events, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($allEvents) || ! array_is_list($allEvents)) {
            throw new LogicException('Pinned VS-003 events are malformed.');
        }
        if (! hash_equals((string) $dataset->digest, $this->digest($allEvents))) {
            throw new LogicException('Pinned VS-003 dataset digest verification failed.');
        }
        $eventIds = is_array($definition['event_ids'] ?? null) ? $definition['event_ids'] : [];
        if ($eventIds === [] || count($eventIds) !== count(array_unique($eventIds))) {
            throw new LogicException('Pinned VS-003 case event IDs are empty or duplicated.');
        }
        $events = array_values(array_filter(
            $allEvents,
            static fn (mixed $event): bool => is_array($event) && in_array($event['id'] ?? null, $eventIds, true),
        ));
        if (count($events) !== count($eventIds)) {
            throw new LogicException('Pinned VS-003 case references unavailable events.');
        }

        return [
            'scenario' => $scenario,
            'rule' => $rule,
            'case' => $case,
            'dataset' => $dataset,
            'events' => $this->normalizeEvents($events),
        ];
    }

    /** @param list<array<string,mixed>> $events @return list<array<string,mixed>> */
    private function normalizeEvents(array $events): array
    {
        $normalized = array_map(function (array $event): array {
            if (
                ! is_string($event['id'] ?? null)
                || $event['id'] === ''
                || ! is_int($event['event_id'] ?? null)
                || ! is_string($event['occurred_at'] ?? null)
            ) {
                throw new LogicException('A VS-003 synthetic event lacks required typed fields.');
            }

            try {
                $occurredAt = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $event['occurred_at'], 'UTC');
            } catch (\Throwable) {
                throw new LogicException('A VS-003 synthetic event timestamp is not strict UTC RFC3339.');
            }
            if ($occurredAt === false || $occurredAt->format('Y-m-d\TH:i:s\Z') !== $event['occurred_at']) {
                throw new LogicException('A VS-003 synthetic event timestamp is not strict UTC RFC3339.');
            }

            return [
                'id' => $event['id'],
                'event_id' => $event['event_id'],
                'occurred_at' => $occurredAt->format('Y-m-d\TH:i:s\Z'),
                'computer' => is_string($event['computer'] ?? null) ? $event['computer'] : null,
                'account_sid' => is_string($event['account_sid'] ?? null) ? $event['account_sid'] : null,
                'logon_type' => is_int($event['logon_type'] ?? null) ? $event['logon_type'] : null,
                'source_address' => is_string($event['source_address'] ?? null) ? $event['source_address'] : null,
                'duplicate_of' => is_string($event['duplicate_of'] ?? null) ? $event['duplicate_of'] : null,
                'late' => ($event['late'] ?? false) === true,
                'contradicts' => is_string($event['contradicts'] ?? null) ? $event['contradicts'] : null,
            ];
        }, $events);

        $ids = array_column($normalized, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            throw new LogicException('VS-003 synthetic event IDs must be unique.');
        }
        foreach ($normalized as $event) {
            $duplicateOf = $event['duplicate_of'];
            if ($duplicateOf !== null && ! in_array($duplicateOf, $ids, true)) {
                throw new LogicException('VS-003 duplicate_of reference is outside the pinned case.');
            }
        }

        usort($normalized, static function (array $left, array $right): int {
            return [$left['occurred_at'], $left['id']] <=> [$right['occurred_at'], $right['id']];
        });

        return $normalized;
    }

    /**
     * @param  list<array<string,mixed>>  $events
     * @param  array<string,mixed>  $normalizedInput
     * @return array<string,mixed>
     */
    private function evaluate(
        array $events,
        string $caseId,
        string $runId,
        string $datasetDigest,
        string $scenarioRevisionId,
        string $ruleRevisionId,
        string $baselineRevisionId,
        array $normalizedInput,
    ): array {
        $unsupported = array_values(array_filter(
            $events,
            static fn (array $event): bool => ! in_array($event['event_id'], [4624, 4625], true),
        ));
        $missing = array_values(array_filter(
            $events,
            static fn (array $event): bool => $event['account_sid'] === null || $event['source_address'] === null,
        ));
        $late = array_values(array_filter($events, static fn (array $event): bool => $event['late'] === true));
        $duplicates = array_values(array_filter($events, static fn (array $event): bool => $event['duplicate_of'] !== null));
        $contradictory = array_values(array_filter($events, static fn (array $event): bool => $event['contradicts'] !== null));
        $effectiveEvents = array_values(array_filter($events, static fn (array $event): bool => $event['duplicate_of'] === null));
        $failures = count(array_filter($effectiveEvents, static fn (array $event): bool => $event['event_id'] === 4625));
        $successes = count(array_filter($effectiveEvents, static fn (array $event): bool => $event['event_id'] === 4624));

        $outcome = match (true) {
            $unsupported !== [] => 'UNSUPPORTED_STATE',
            $missing !== [] || $contradictory !== [] => 'INSUFFICIENT_TELEMETRY',
            $late !== [] => 'INCIDENT_CONFIRMED',
            $successes > 0 && $failures > 0 => 'BENIGN_EXPLAINED',
            $failures > 0 => 'SUSPICIOUS',
            default => 'INSUFFICIENT_TELEMETRY',
        };
        $telemetryHealth = match (true) {
            $unsupported !== [] => 'UNSUPPORTED',
            $missing !== [] || $contradictory !== [] || $late !== [] || $duplicates !== [] => 'DEGRADED',
            default => 'HEALTHY',
        };
        $quality = [
            'duplicate_count' => count($duplicates),
            'late_count' => count($late),
            'missing_count' => count($missing),
            'contradictory_count' => count($contradictory),
            'unsupported_count' => count($unsupported),
            'ordering' => 'occurred_at_then_event_id_ascending_UTC',
            'duplicate_policy' => 'retained_in_raw_timeline_excluded_from_detection_counts',
        ];
        $alertState = match ($outcome) {
            'BENIGN_EXPLAINED' => 'NONE',
            'UNSUPPORTED_STATE' => 'UNSUPPORTED',
            default => 'OPEN',
        };
        $severity = match ($outcome) {
            'INCIDENT_CONFIRMED' => 'HIGH',
            'SUSPICIOUS' => 'MEDIUM',
            default => 'LOW',
        };
        $confidence = match ($outcome) {
            'UNSUPPORTED_STATE', 'INSUFFICIENT_TELEMETRY' => 'LOW',
            'BENIGN_EXPLAINED' => 'HIGH',
            default => 'MEDIUM',
        };
        $alternatives = match ($outcome) {
            'BENIGN_EXPLAINED' => ['legitimate_success_after_failures'],
            'SUSPICIOUS', 'INCIDENT_CONFIRMED' => ['legitimate_user_error', 'telemetry_gap'],
            default => ['telemetry_gap'],
        };
        $missingData = [];
        if ($missing !== []) {
            $missingData[] = 'account_sid_or_source_address';
        }
        if ($contradictory !== []) {
            $missingData[] = 'corroborating_event_required';
        }
        if ($unsupported !== []) {
            $missingData[] = 'supported_event_mapping_required';
        }

        $payload = [
            'run_id' => $runId,
            'dataset_digest' => $datasetDigest,
            'case_id' => $caseId,
            'normalized_input' => $normalizedInput,
            'events' => $events,
            'quality' => $quality,
            'telemetry_health' => $telemetryHealth,
            'outcome' => $outcome,
            'alert_state' => $alertState,
            'severity' => $severity,
            'scope' => $outcome === 'INCIDENT_CONFIRMED' ? 'one_synthetic_device' : 'bounded_synthetic_identity',
            'confidence' => $confidence,
            'alternative_hypotheses' => $alternatives,
            'missing_data' => $missingData,
            'detection_rationale' => [
                'failed_logons' => $failures,
                'successful_logons' => $successes,
                'typed_rule' => 'AUTH-ANOMALY-V1',
                'behavior_version' => config('vs003.behavior_version'),
            ],
            'pinned_revisions' => [
                'dataset_revision_id' => $normalizedInput['dataset_revision_id'],
                'scenario_revision_id' => $scenarioRevisionId,
                'rule_set_revision_id' => $ruleRevisionId,
                'enterprise_baseline_revision_id' => $baselineRevisionId,
            ],
            'evidence_origin' => 'SIMULATED',
        ];
        $payload['timeline_digest'] = $this->semanticTraceDigest($payload);

        return $payload;
    }

    private function assertRequestEnvelope(int $seed, string $idempotencyKey, string $actorId): void
    {
        if ($seed < 1 || $seed > 4294967295) {
            throw new LogicException('VS-003 seed is outside the supported unsigned 32-bit range.');
        }
        if ($actorId === '' || ! Str::isUuid($actorId)) {
            throw new LogicException('VS-003 requires a valid actor identifier.');
        }
        if (
            strlen($idempotencyKey) < 12
            || strlen($idempotencyKey) > 200
            || preg_match('/^[A-Za-z0-9:._-]+$/', $idempotencyKey) !== 1
        ) {
            throw new LogicException('VS-003 idempotency key is invalid.');
        }
    }

    /** @return array{run:array<string,mixed>,trace:array<string,mixed>} */
    private function resultForRun(ScenarioRun $run): array
    {
        return [
            'run' => $run->toArray(),
            'trace' => DecisionTrace::query()->where('scenario_run_id', $run->id)->firstOrFail()->tracePayload(),
        ];
    }

    /** @param array<string,mixed> $trace */
    private function semanticTraceDigest(array $trace): string
    {
        unset($trace['timeline_digest'], $trace['run_id']);

        return $this->digest($trace);
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode(
            $this->canonical($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
        }
        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
    }
}
