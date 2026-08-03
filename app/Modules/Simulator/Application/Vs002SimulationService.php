<?php

namespace App\Modules\Simulator\Application;

use App\Modules\Enterprise\Application\EnterpriseBaselineService;
use App\Modules\Simulator\Authorization\WebAuthorizationDecisionEngine;
use App\Modules\Simulator\Models\AuthorizationPolicyRevision;
use App\Modules\Simulator\Models\DecisionTrace;
use App\Modules\Simulator\Models\EndpointContractRevision;
use App\Modules\Simulator\Models\ReplayRecord;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\ScenarioRun;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use LogicException;

final class Vs002SimulationService
{
    public function __construct(private readonly EnterpriseBaselineService $enterprise) {}

    /** @return array<string,mixed> */
    public function latestPlan(string $caseId, int $seed): array
    {
        $scenario = ScenarioRevision::query()->where('scenario_id', config('vs002.scenario_id'))->where('state', 'published')->latest('revision')->firstOrFail();
        $case = $this->caseFromScenario($scenario, $caseId);
        $policy = AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->where('revision', (int) $case['policy_revision'])->first();
        if ($policy === null) {
            throw new LogicException('The case requires a remediation policy revision that has not been created.');
        }
        $contract = EndpointContractRevision::query()->where('contract_id', config('vs002.endpoint_contract_id'))->where('state', 'published')->latest('revision')->firstOrFail();

        return $this->plan($scenario, $policy, $contract, $caseId, $seed, $case['input'], $this->orderedActions());
    }

    /** @return array<string,mixed> */
    public function replayPlan(string $originalRunId): array
    {
        $original = ScenarioRun::query()->findOrFail($originalRunId);
        $scenario = ScenarioRevision::query()->find($original->scenario_revision_id);
        $rules = SimulatorRuleRevision::query()->find($original->rule_set_revision_id);
        $policy = AuthorizationPolicyRevision::query()->find($original->policy_revision_id);
        $contract = EndpointContractRevision::query()->find($original->endpoint_contract_revision_id);
        if ($scenario === null || $rules === null || $policy === null || $contract === null) {
            throw new LogicException('Pinned historical web/API revision is unavailable.');
        }
        $plan = $this->plan(
            $scenario,
            $policy,
            $contract,
            (string) $original->case_id,
            (int) $original->seed,
            $original->normalizedInputPayload(),
            $original->orderedActionList(),
            is_string($original->remediation_revision_id) ? $original->remediation_revision_id : null,
            is_string($original->verification_of_run_id) ? $original->verification_of_run_id : null,
        );
        if (! hash_equals((string) $original->input_digest, $plan['input_digest'])) {
            throw new LogicException('Pinned historical web/API input no longer matches its digest.');
        }

        return $plan + [
            'original_run_id' => (string) $original->id,
            'original_trace_digest' => (string) $original->trace_digest,
            'learner_actor_id' => (string) $original->actor_id,
        ];
    }

    /** @return array<string,mixed> */
    public function verificationPlan(string $vulnerableRunId, string $remediationPolicyRevisionId): array
    {
        $original = ScenarioRun::query()->findOrFail($vulnerableRunId);
        $scenario = ScenarioRevision::query()->findOrFail($original->scenario_revision_id);
        $contract = EndpointContractRevision::query()->findOrFail($original->endpoint_contract_revision_id);
        $policy = AuthorizationPolicyRevision::query()->whereKey($remediationPolicyRevisionId)->where('mode', 'secure')->firstOrFail();

        return $this->plan(
            $scenario,
            $policy,
            $contract,
            (string) $original->case_id,
            (int) $original->seed,
            $original->normalizedInputPayload(),
            $original->orderedActionList(),
            (string) $policy->id,
            (string) $original->id,
        ) + ['learner_actor_id' => (string) $original->actor_id];
    }

    /**
     * @param  array<string,mixed>  $plan
     * @return array{run:array<string,mixed>,trace:array<string,mixed>}
     */
    public function execute(array $plan, string $learnerActorId, string $baselineDigest, string $idempotencyKey): array
    {
        $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $this->existingResult($existing, $plan, $learnerActorId);
        }

        try {
            $run = ScenarioRun::query()->create([
                'scenario_revision_id' => $plan['scenario_revision_id'],
                'rule_set_revision_id' => $plan['rule_set_revision_id'],
                'enterprise_baseline_revision_id' => $plan['enterprise_baseline_revision_id'],
                'policy_revision_id' => $plan['policy_revision_id'],
                'endpoint_contract_revision_id' => $plan['endpoint_contract_revision_id'],
                'actor_id' => $learnerActorId,
                'case_id' => $plan['case_id'],
                'seed' => $plan['seed'],
                'status' => 'running',
                'ordered_actions' => $plan['ordered_actions'],
                'normalized_input' => $plan['input'],
                'input_digest' => $plan['input_digest'],
                'request_digest' => $plan['request_digest'],
                'baseline_digest_before' => $baselineDigest,
                'idempotency_key' => $idempotencyKey,
                'remediation_revision_id' => $plan['remediation_revision_id'],
                'verification_of_run_id' => $plan['verification_of_run_id'],
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
            $existing = ScenarioRun::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();

            return $this->existingResult($existing, $plan, $learnerActorId);
        }

        $trace = (new WebAuthorizationDecisionEngine)->evaluate($plan['input'], [
            'scenario_revision_id' => $plan['scenario_revision_id'],
            'rule_set_revision_id' => $plan['rule_set_revision_id'],
            'policy_revision_id' => $plan['policy_revision_id'],
            'endpoint_contract_revision_id' => $plan['endpoint_contract_revision_id'],
            'enterprise_baseline_revision_id' => $plan['enterprise_baseline_revision_id'],
            'run_id' => (string) $run->id,
            'seed' => $plan['seed'],
            'case_id' => $plan['case_id'],
            'ordered_actions' => $plan['ordered_actions'],
            'policy_mode' => $plan['policy_mode'],
            'policy_rules' => $plan['policy_rules'],
            'rule_behavior_version' => $plan['rule_behavior_version'],
            'baseline_facts' => $this->enterprise->webAuthorizationFacts($plan['enterprise_baseline_revision_id'], $plan['input']),
            'contract_method' => $plan['contract_method'],
            'route_template' => $plan['route_template'],
            'contract_action' => $plan['contract_action'],
            'response_shape_id' => $plan['response_shape_id'],
            'allowed_response_fields' => $plan['allowed_response_fields'],
            'source_claim_ids' => config('vs002.required_claim_ids'),
            'remediation_revision_id' => $plan['remediation_revision_id'],
            'verification_of_run_id' => $plan['verification_of_run_id'],
        ]);
        DecisionTrace::query()->create(['scenario_run_id' => $run->id, 'trace' => $trace, 'output_digest' => $trace['trace_digest']]);
        $run->forceFill([
            'status' => 'completed',
            'request_id' => $trace['request_id'],
            'correlation_id' => $trace['correlation_id'],
            'baseline_digest_after' => $baselineDigest,
            'outcome' => $trace['decision'],
            'trace_digest' => $trace['trace_digest'],
            'completed_at' => now(),
        ])->save();

        return ['run' => $run->fresh()->toArray(), 'trace' => $trace];
    }

    /** @return array<string,mixed> */
    public function createRemediation(): array
    {
        $existing = AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->where('revision', 2)->first();
        if ($existing !== null) {
            return $existing->toArray();
        }
        $vulnerable = AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->where('revision', 1)->firstOrFail();
        $rules = [
            'behavior_version' => 'web_authorization_v1',
            'default' => 'DENY',
            'subject_source' => 'server_session',
            'allow' => ['resource_owner_matches_subject', 'explicit_server_admin_role'],
            'client_role_authoritative' => false,
            'response_shape' => config('vs002.allowed_response_fields'),
        ];

        return AuthorizationPolicyRevision::query()->create([
            'policy_id' => config('vs002.policy_id'),
            'revision' => 2,
            'state' => 'published',
            'mode' => 'secure',
            'rules' => $rules,
            'source_claim_ids' => ['WEB-AUTH-002', 'WEB-AUTH-003', 'WEB-AUTH-004'],
            'digest' => $this->digest($rules),
            'remediates_revision_id' => $vulnerable->id,
            'published_at' => now(),
        ])->toArray();
    }

    /** @return array<string,mixed> */
    public function recordReplay(string $originalRunId, string $replayRunId, string $originalDigest, string $replayDigest): array
    {
        return ReplayRecord::query()->create([
            'original_run_id' => $originalRunId,
            'replay_run_id' => $replayRunId,
            'digest_match' => hash_equals($originalDigest, $replayDigest),
            'original_digest' => $originalDigest,
            'replay_digest' => $replayDigest,
        ])->toArray();
    }

    /** @return list<string> */
    public function matchingReplayRunIds(): array
    {
        return ReplayRecord::query()->where('digest_match', true)->get(['original_run_id', 'replay_run_id'])
            ->flatMap(fn (ReplayRecord $record): array => [(string) $record->original_run_id, (string) $record->replay_run_id])->unique()->values()->all();
    }

    /** @return array<string,mixed> */
    public function workspace(): array
    {
        $scenario = ScenarioRevision::query()->where('scenario_id', config('vs002.scenario_id'))->latest('revision')->firstOrFail();
        $runs = ScenarioRun::query()->where('scenario_revision_id', $scenario->id)->latest()->limit(20)->get();
        $traces = DecisionTrace::query()->whereIn('scenario_run_id', $runs->pluck('id'))->get()->keyBy('scenario_run_id');

        return [
            'scenario' => $scenario->toArray(),
            'policies' => AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->orderBy('revision')->get()->map(fn (AuthorizationPolicyRevision $policy): array => $policy->toArray())->all(),
            'contract' => EndpointContractRevision::query()->where('contract_id', config('vs002.endpoint_contract_id'))->latest('revision')->firstOrFail()->toArray(),
            'runs' => $runs->map(fn (ScenarioRun $run): array => $run->toArray() + ['trace' => $traces->get($run->id)?->tracePayload()])->all(),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function policyRevisions(): array
    {
        return AuthorizationPolicyRevision::query()->where('policy_id', config('vs002.policy_id'))->orderBy('revision')->get()
            ->map(fn (AuthorizationPolicyRevision $policy): array => $policy->toArray())->all();
    }

    /** @return array<string,mixed> */
    public function verificationFacts(string $vulnerableRunId, string $verificationRunId, string $remediationPolicyRevisionId): array
    {
        $vulnerable = ScenarioRun::query()->findOrFail($vulnerableRunId);
        $verification = ScenarioRun::query()->findOrFail($verificationRunId);
        $policy = AuthorizationPolicyRevision::query()->whereKey($remediationPolicyRevisionId)->where('mode', 'secure')->firstOrFail();
        if ($vulnerable->outcome !== 'ALLOW'
            || $verification->outcome !== 'DENY'
            || (string) $vulnerable->actor_id !== (string) $verification->actor_id
            || (string) $verification->verification_of_run_id !== (string) $vulnerable->id
            || (string) $verification->policy_revision_id !== (string) $policy->id
            || (string) $verification->baseline_digest_before !== (string) $verification->baseline_digest_after
            || (string) $vulnerable->baseline_digest_before !== (string) $vulnerable->baseline_digest_after) {
            throw new LogicException('Verification does not prove an isolated vulnerable-to-fixed transition.');
        }

        return [
            'actor_id' => (string) $vulnerable->actor_id,
            'vulnerable_run_id' => (string) $vulnerable->id,
            'vulnerable_trace_digest' => (string) $vulnerable->trace_digest,
            'remediation_policy_revision_id' => (string) $policy->id,
            'verification_run_id' => (string) $verification->id,
            'verification_trace_digest' => (string) $verification->trace_digest,
            'verification_outcome' => (string) $verification->outcome,
        ];
    }

    /**
     * @param  array<string,mixed>  $input
     * @param  list<string>  $orderedActions
     * @return array<string,mixed>
     */
    private function plan(ScenarioRevision $scenario, AuthorizationPolicyRevision $policy, EndpointContractRevision $contract, string $caseId, int $seed, array $input, array $orderedActions, ?string $remediationRevisionId = null, ?string $verificationOfRunId = null): array
    {
        $ruleRevision = SimulatorRuleRevision::query()->findOrFail($scenario->rule_set_revision_id);
        $rulePayload = $ruleRevision->getAttribute('rules');
        if (! is_array($rulePayload)) {
            throw new LogicException('Pinned simulator rule payload is unavailable.');
        }
        $inputDigest = $this->digest($input);
        $request = [
            'scenario_revision_id' => (string) $scenario->id,
            'rule_set_revision_id' => (string) $scenario->rule_set_revision_id,
            'enterprise_baseline_revision_id' => (string) $scenario->enterprise_baseline_revision_id,
            'policy_revision_id' => (string) $policy->id,
            'endpoint_contract_revision_id' => (string) $contract->id,
            'case_id' => $caseId,
            'seed' => $seed,
            'ordered_actions' => $orderedActions,
            'input_digest' => $inputDigest,
            'remediation_revision_id' => $remediationRevisionId,
            'verification_of_run_id' => $verificationOfRunId,
        ];

        return $request + [
            'input' => $input,
            'policy_mode' => (string) $policy->mode,
            'policy_rules' => $policy->rulePayload(),
            'rule_behavior_version' => $rulePayload['behavior_version'] ?? null,
            'contract_method' => (string) $contract->method,
            'route_template' => (string) $contract->route_template,
            'contract_action' => (string) $contract->requested_action,
            'response_shape_id' => (string) $contract->response_shape_id,
            'allowed_response_fields' => $contract->allowedResponseFields(),
            'request_digest' => $this->digest($request),
        ];
    }

    /** @return array<string,mixed> */
    private function caseFromScenario(ScenarioRevision $scenario, string $caseId): array
    {
        $case = collect($scenario->caseDefinitions())->first(fn (array $candidate): bool => ($candidate['case_id'] ?? null) === $caseId);
        if (! is_array($case) || ! is_array($case['input'] ?? null) || ! is_int($case['policy_revision'] ?? null)) {
            throw new InvalidArgumentException('Unknown or invalid VS-002 case identifier.');
        }

        return $case;
    }

    /**
     * @param  array<string,mixed>  $plan
     * @return array{run:array<string,mixed>,trace:array<string,mixed>}
     */
    private function existingResult(ScenarioRun $existing, array $plan, string $learnerActorId): array
    {
        if (! hash_equals((string) $existing->request_digest, (string) $plan['request_digest']) || (string) $existing->actor_id !== $learnerActorId) {
            throw new IdempotencyConflict('Idempotency key already exists for a different actor or VS-002 payload.');
        }
        $trace = DecisionTrace::query()->where('scenario_run_id', $existing->id)->firstOrFail();

        return ['run' => $existing->toArray(), 'trace' => $trace->tracePayload()];
    }

    /** @return list<string> */
    private function orderedActions(): array
    {
        return ['normalize_http_boundary', 'authenticate_server_context', 'lookup_resource', 'authorize_subject_action_resource', 'serialize_approved_shape', 'emit_bounded_finding'];
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
