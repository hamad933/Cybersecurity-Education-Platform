<?php

namespace App\Modules\Evidence\Application;

use App\Modules\Evidence\Models\EvidenceDecision;
use App\Modules\Evidence\Models\EvidenceRecord;
use App\Modules\Evidence\Models\FindingVerification;
use App\Modules\Evidence\Models\SecurityFinding;
use App\Modules\Platform\Messaging\OutboxPublisher;
use Illuminate\Support\Facades\DB;
use LogicException;

final class Vs002EvidenceService
{
    public function __construct(private readonly OutboxPublisher $outbox) {}

    /**
     * @param  array<string,mixed>  $run
     * @param  array<string,mixed>  $trace
     * @return array{evidence:array<string,mixed>,findings:list<array<string,mixed>>}
     */
    public function recordRun(string $learnerActorId, array $run, array $trace): array
    {
        $existing = EvidenceRecord::query()->where('run_id', $run['id'])->first();
        if ($existing !== null) {
            return [
                'evidence' => $existing->toArray(),
                'findings' => SecurityFinding::query()->where('scenario_run_id', $run['id'])->get()->map(fn (SecurityFinding $finding): array => $finding->toArray())->all(),
            ];
        }

        return DB::transaction(function () use ($learnerActorId, $run, $trace) {
            $findings = [];
            $candidates = is_array($trace['finding_candidates'] ?? null) ? $trace['finding_candidates'] : [];
            foreach ($candidates as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }
                $finding = SecurityFinding::query()->firstOrCreate(
                    ['id' => $candidate['finding_id']],
                    [
                        'finding_key' => $candidate['finding_key'],
                        'occurrence_key' => $candidate['occurrence_key'],
                        'category' => $candidate['category'],
                        'scenario_run_id' => $run['id'],
                        'actor_id' => $learnerActorId,
                        'target_resource_id' => $trace['target_resource_id'],
                        'policy_revision_id' => $trace['policy_revision_id'],
                        'decisive_missing_check' => $candidate['decisive_missing_check'],
                        'trace_digest' => $trace['trace_digest'],
                        'source_claim_ids' => $trace['source_claim_ids'],
                        'safe_details' => $candidate['safe_details'],
                        'status' => 'open',
                    ],
                );
                $findings[] = $finding->toArray();
            }

            $body = [
                'origin' => 'SIMULATED',
                'actor_id' => $learnerActorId,
                'capability_id' => config('vs002.capability_id'),
                'knowledge_unit_id' => config('vs002.knowledge_unit_id'),
                'scenario_revision_id' => $run['scenario_revision_id'],
                'policy_revision_id' => $run['policy_revision_id'],
                'rule_set_revision_id' => $run['rule_set_revision_id'],
                'endpoint_contract_revision_id' => $run['endpoint_contract_revision_id'],
                'enterprise_baseline_revision_id' => $run['enterprise_baseline_revision_id'],
                'run_id' => $run['id'],
                'case_id' => $run['case_id'],
                'request_case_id' => $run['case_id'],
                'input_digest' => $run['input_digest'],
                'trace_digest' => $trace['trace_digest'],
                'finding_ids' => array_map(fn (array $finding): string => (string) $finding['id'], $findings),
                'remediation_revision_id' => $run['remediation_revision_id'],
                'verification_run_id' => $run['verification_of_run_id'] !== null ? $run['id'] : null,
                'result' => $trace['decision'],
                'limitations' => $trace['limitations'],
                'source_claim_ids' => $trace['source_claim_ids'],
            ];
            $evidence = EvidenceRecord::query()->create($body + ['content_digest' => $this->digest($body)]);
            $this->outbox->publish('vs002.request.completed.v1', 'MOD-EVD', "vs002:evidence:{$run['id']}", (string) $run['id'], [
                'run_id' => $run['id'],
                'learner_actor_id' => $learnerActorId,
                'decision' => $trace['decision'],
                'finding_ids' => $trace['finding_ids'],
                'origin' => 'SIMULATED',
            ]);

            return ['evidence' => $evidence->toArray(), 'findings' => $findings];
        });
    }

    /**
     * @param  array<string,mixed>  $facts
     * @return array<string,mixed>
     */
    public function completeVerification(string $findingId, array $facts): array
    {
        return DB::transaction(function () use ($findingId, $facts) {
            $finding = SecurityFinding::query()->lockForUpdate()->findOrFail($findingId);
            if ((string) $finding->scenario_run_id !== (string) $facts['vulnerable_run_id']
                || (string) $finding->actor_id !== (string) $facts['actor_id']
                || (string) $finding->policy_revision_id === (string) $facts['remediation_policy_revision_id']
                || ($facts['verification_outcome'] ?? null) !== 'DENY') {
                throw new LogicException('Finding verification does not match the vulnerable finding.');
            }
            $payload = [
                'security_finding_id' => (string) $finding->id,
                'actor_id' => $facts['actor_id'],
                'vulnerable_run_id' => $facts['vulnerable_run_id'],
                'vulnerable_trace_digest' => $facts['vulnerable_trace_digest'],
                'remediation_policy_revision_id' => $facts['remediation_policy_revision_id'],
                'verification_run_id' => $facts['verification_run_id'],
                'verification_trace_digest' => $facts['verification_trace_digest'],
            ];
            $verification = FindingVerification::query()->firstOrCreate(
                ['security_finding_id' => $finding->id],
                $payload + ['status' => 'VERIFIED_FIXED', 'verification_digest' => $this->digest($payload), 'verified_at' => now()],
            );
            if ($finding->status !== 'verified_fixed') {
                $finding->forceFill(['status' => 'verified_fixed', 'verified_at' => now()])->save();
            }

            return $verification->toArray();
        });
    }

    /** @return list<array<string,mixed>> */
    public function acceptedForActor(string $actorId): array
    {
        return EvidenceRecord::query()
            ->join('evidence_decisions', 'evidence_decisions.evidence_record_id', '=', 'evidence_records.id')
            ->where('evidence_decisions.decision', 'ACCEPTED')
            ->where('evidence_records.actor_id', $actorId)
            ->where('evidence_records.knowledge_unit_id', config('vs002.knowledge_unit_id'))
            ->select('evidence_records.*')->get()
            ->map(fn (EvidenceRecord $record): array => $record->toArray())->all();
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        $evidence = EvidenceRecord::query()->where('actor_id', $actorId)->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->latest()->limit(30)->get();
        $decisions = EvidenceDecision::query()->whereIn('evidence_record_id', $evidence->pluck('id'))->get()->keyBy('evidence_record_id');
        $findings = SecurityFinding::query()->where('actor_id', $actorId)->latest()->get();
        $verifications = FindingVerification::query()->whereIn('security_finding_id', $findings->pluck('id'))->get()->keyBy('security_finding_id');

        return [
            'evidence' => $evidence->map(fn (EvidenceRecord $record): array => $record->toArray() + ['decision' => $decisions->get($record->id)?->toArray()])->all(),
            'findings' => $findings->map(fn (SecurityFinding $finding): array => $finding->toArray() + ['verification' => $verifications->get($finding->id)?->toArray()])->all(),
        ];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
