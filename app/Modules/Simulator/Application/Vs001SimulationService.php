<?php

namespace App\Modules\Simulator\Application;

use App\Modules\Simulator\Authorization\WindowsAuthorizationDecisionEngine;
use App\Modules\Simulator\Models\DecisionTrace;
use App\Modules\Simulator\Models\ReplayRecord;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\ScenarioRun;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use LogicException;

final class Vs001SimulationService
{
    /**
     * @return array{scenario_revision_id:string,rule_set_revision_id:string,enterprise_baseline_revision_id:string,case_id:string,seed:int,ordered_actions:list<string>,input:array<string,mixed>,input_digest:string,request_digest:string}
     */
    public function latestPlan(string $caseId, int $seed): array
    {
        $scenario = ScenarioRevision::query()
            ->where('scenario_id', config('vs001.scenario_id'))
            ->where('state', 'published')
            ->latest('revision')
            ->firstOrFail();

        return $this->planFromScenario($scenario, $caseId, $seed, $this->orderedActions());
    }

    /**
     * @return array{original_run_id:string,scenario_revision_id:string,rule_set_revision_id:string,enterprise_baseline_revision_id:string,case_id:string,seed:int,ordered_actions:list<string>,input:array<string,mixed>,input_digest:string,request_digest:string,original_trace_digest:string,actor_id:string}
     */
    public function replayPlan(string $originalRunId): array
    {
        $original = ScenarioRun::query()->findOrFail($originalRunId);
        $scenario = ScenarioRevision::query()->find($original->scenario_revision_id);
        $rules = SimulatorRuleRevision::query()->find($original->rule_set_revision_id);
        if ($scenario === null || $rules === null) {
            throw new LogicException('Pinned historical scenario or rule revision is unavailable.');
        }
        if ((string) $scenario->rule_set_revision_id !== (string) $original->rule_set_revision_id
            || (string) $scenario->enterprise_baseline_revision_id !== (string) $original->enterprise_baseline_revision_id) {
            throw new LogicException('Pinned historical revision linkage does not match the original run.');
        }

        $orderedActions = $original->orderedActionList();
        $plan = $this->planFromScenario($scenario, (string) $original->case_id, (int) $original->seed, $orderedActions);
        if (! hash_equals((string) $original->input_digest, $plan['input_digest'])) {
            throw new LogicException('Pinned historical case input no longer matches the original digest.');
        }

        return $plan + [
            'original_run_id' => (string) $original->id,
            'original_trace_digest' => (string) $original->trace_digest,
            'actor_id' => (string) $original->actor_id,
        ];
    }

    /**
     * @param  array{scenario_revision_id:string,rule_set_revision_id:string,enterprise_baseline_revision_id:string,case_id:string,seed:int,ordered_actions:list<string>,input:array<string,mixed>,input_digest:string,request_digest:string}  $plan
     * @return array{run:array<string,mixed>,trace:array<string,mixed>}
     */
    public function execute(array $plan, string $actorId, string $baselineDigest, string $idempotencyKey): array
    {
        $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $this->existingResult($existing, $plan, $actorId);
        }

        try {
            $run = ScenarioRun::query()->create([
                'scenario_revision_id' => $plan['scenario_revision_id'],
                'rule_set_revision_id' => $plan['rule_set_revision_id'],
                'enterprise_baseline_revision_id' => $plan['enterprise_baseline_revision_id'],
                'actor_id' => $actorId,
                'case_id' => $plan['case_id'],
                'seed' => $plan['seed'],
                'status' => 'running',
                'ordered_actions' => $plan['ordered_actions'],
                'normalized_input' => $plan['input'],
                'input_digest' => $plan['input_digest'],
                'request_digest' => $plan['request_digest'],
                'baseline_digest_before' => $baselineDigest,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
            $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();

            return $this->existingResult($existing, $plan, $actorId);
        }

        $rules = SimulatorRuleRevision::query()->find($plan['rule_set_revision_id']);
        if ($rules === null) {
            throw new LogicException('Pinned simulator rule revision is unavailable.');
        }
        $tracePayload = (new WindowsAuthorizationDecisionEngine)->evaluate($plan['input'], [
            'rule_set_id' => $rules->rule_set_id,
            'rule_set_revision' => $rules->revision,
            'authority_baseline_id' => $rules->authority_baseline_id,
            'scenario_revision_id' => $plan['scenario_revision_id'],
            'run_id' => $run->id,
            'seed' => $plan['seed'],
            'ordered_actions' => $plan['ordered_actions'],
            'source_claim_ids' => config('vs001.required_claim_ids'),
        ]);
        DecisionTrace::query()->create([
            'scenario_run_id' => $run->id,
            'trace' => $tracePayload,
            'output_digest' => $tracePayload['output_digest'],
        ]);
        $run->forceFill([
            'status' => 'completed',
            'baseline_digest_after' => $baselineDigest,
            'outcome' => $tracePayload['final_outcome'],
            'trace_digest' => $tracePayload['output_digest'],
            'completed_at' => now(),
        ])->save();

        return ['run' => $run->fresh()->toArray(), 'trace' => $tracePayload];
    }

    /** @return array<string,mixed> */
    public function recordReplay(string $originalRunId, string $replayRunId, string $originalDigest, string $replayDigest): array
    {
        $record = ReplayRecord::query()->create([
            'original_run_id' => $originalRunId,
            'replay_run_id' => $replayRunId,
            'digest_match' => hash_equals($originalDigest, $replayDigest),
            'original_digest' => $originalDigest,
            'replay_digest' => $replayDigest,
        ]);

        return $record->toArray();
    }

    /** @return list<string> */
    public function matchingReplayRunIds(): array
    {
        return ReplayRecord::query()->where('digest_match', true)
            ->get(['original_run_id', 'replay_run_id'])
            ->flatMap(fn (ReplayRecord $record): array => [(string) $record->original_run_id, (string) $record->replay_run_id])
            ->unique()->values()->all();
    }

    /** @return array{scenario:array<string,mixed>,runs:list<array<string,mixed>>} */
    public function workspace(): array
    {
        $scenario = ScenarioRevision::query()->where('scenario_id', config('vs001.scenario_id'))->latest('revision')->firstOrFail();
        $runs = ScenarioRun::query()->latest()->limit(8)->get();
        $traces = DecisionTrace::query()->whereIn('scenario_run_id', $runs->pluck('id'))->get()->keyBy('scenario_run_id');

        return [
            'scenario' => $scenario->toArray(),
            'runs' => $runs->map(fn (ScenarioRun $run): array => $run->toArray() + ['trace' => $traces->get($run->id)?->trace])->all(),
        ];
    }

    /**
     * @param  list<string>  $orderedActions
     * @return array{scenario_revision_id:string,rule_set_revision_id:string,enterprise_baseline_revision_id:string,case_id:string,seed:int,ordered_actions:list<string>,input:array<string,mixed>,input_digest:string,request_digest:string}
     */
    private function planFromScenario(ScenarioRevision $scenario, string $caseId, int $seed, array $orderedActions): array
    {
        $case = collect($scenario->caseDefinitions())->first(fn (array $candidate): bool => ($candidate['case_id'] ?? null) === $caseId);
        if (! is_array($case) || ! is_array($case['input'] ?? null)) {
            throw new InvalidArgumentException('Unknown VS-001 case identifier.');
        }
        if (SimulatorRuleRevision::query()->find($scenario->rule_set_revision_id) === null) {
            throw new LogicException('Pinned historical rule revision is unavailable.');
        }

        $input = $case['input'];
        $inputDigest = $this->digest($input);
        $request = [
            'scenario_revision_id' => (string) $scenario->id,
            'rule_set_revision_id' => (string) $scenario->rule_set_revision_id,
            'enterprise_baseline_revision_id' => (string) $scenario->enterprise_baseline_revision_id,
            'case_id' => $caseId,
            'seed' => $seed,
            'ordered_actions' => $orderedActions,
            'input_digest' => $inputDigest,
        ];

        return $request + ['input' => $input, 'request_digest' => $this->digest($request)];
    }

    /**
     * @param  array{request_digest:string}  $plan
     * @return array{run:array<string,mixed>,trace:array<string,mixed>}
     */
    private function existingResult(ScenarioRun $existing, array $plan, string $actorId): array
    {
        if (! hash_equals((string) $existing->request_digest, $plan['request_digest']) || (string) $existing->actor_id !== $actorId) {
            throw new IdempotencyConflict('Idempotency key already exists for a different actor or payload.');
        }
        $trace = DecisionTrace::query()->where('scenario_run_id', $existing->id)->firstOrFail();

        return ['run' => $existing->toArray(), 'trace' => $trace->tracePayload()];
    }

    /** @return list<string> */
    private function orderedActions(): array
    {
        return ['load_immutable_revisions', 'normalize_input', 'evaluate_bounded_rules', 'persist_trace', 'issue_simulated_evidence'];
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
