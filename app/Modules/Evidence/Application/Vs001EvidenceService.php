<?php

namespace App\Modules\Evidence\Application;

use App\Modules\Evidence\Models\EvidenceDecision;
use App\Modules\Evidence\Models\EvidenceRecord;
use App\Modules\Platform\Messaging\OutboxPublisher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class Vs001EvidenceService
{
    public function __construct(private readonly OutboxPublisher $outbox) {}

    /**
     * @param  array<string, mixed>  $run
     * @param  array<string, mixed>  $trace
     * @return array<string, mixed>
     */
    public function recordRunEvidence(string $actorId, array $run, array $trace): array
    {
        $existing = EvidenceRecord::query()->where('run_id', $run['id'])->first();
        if ($existing !== null) {
            return $existing->toArray();
        }

        $body = [
            'origin' => 'SIMULATED',
            'actor_id' => $actorId,
            'capability_id' => config('vs001.capability_id'),
            'knowledge_unit_id' => config('vs001.knowledge_unit_id'),
            'scenario_revision_id' => $run['scenario_revision_id'],
            'rule_set_revision_id' => $run['rule_set_revision_id'],
            'enterprise_baseline_revision_id' => $run['enterprise_baseline_revision_id'],
            'run_id' => $run['id'],
            'case_id' => $run['case_id'],
            'input_digest' => $run['input_digest'],
            'trace_digest' => $trace['output_digest'],
            'result' => $trace['final_outcome'],
            'limitations' => $trace['limitations'],
            'source_claim_ids' => $trace['source_claim_ids'],
        ];
        $record = EvidenceRecord::query()->create($body + ['content_digest' => $this->digest($body)]);

        return $record->toArray();
    }

    /** @return array<string,mixed> */
    public function decide(string $evidenceId, string $decision, string $rationale, string $reviewerActorId): array
    {
        if (! in_array($decision, ['ACCEPTED', 'REJECTED', 'NEEDS_REVIEW'], true) || trim($rationale) === '' || mb_strlen($rationale) > 1000) {
            throw new InvalidArgumentException('Evidence decision is invalid.');
        }

        return DB::transaction(function () use ($evidenceId, $decision, $rationale, $reviewerActorId): array {
            $evidence = EvidenceRecord::query()->lockForUpdate()->findOrFail($evidenceId);
            $record = EvidenceDecision::query()->create([
                'evidence_record_id' => $evidence->id,
                'decision' => $decision,
                'rationale' => trim($rationale),
                'decided_by' => $reviewerActorId,
                'decided_at' => now(),
            ]);
            if ($decision === 'ACCEPTED') {
                $evidence->forceFill(['locked' => true])->save();
            }
            $this->outbox->publish('vs001.evidence.decided.v1', 'MOD-EVD', "evidence:{$evidence->id}:decision", (string) $evidence->run_id, [
                'evidence_id' => $evidence->id,
                'learner_actor_id' => $evidence->actor_id,
                'reviewer_actor_id' => $reviewerActorId,
                'decision' => $decision,
            ]);

            return $record->toArray();
        });
    }

    /** @return list<array<string, mixed>> */
    public function acceptedForActor(string $actorId, string $knowledgeUnitId): array
    {
        return EvidenceRecord::query()
            ->join('evidence_decisions', 'evidence_decisions.evidence_record_id', '=', 'evidence_records.id')
            ->where('evidence_decisions.decision', 'ACCEPTED')
            ->where('evidence_records.actor_id', $actorId)
            ->where('evidence_records.knowledge_unit_id', $knowledgeUnitId)
            ->select('evidence_records.*')
            ->get()
            ->map(fn (EvidenceRecord $record): array => $record->toArray())
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function workspace(string $actorId): array
    {
        $evidence = EvidenceRecord::query()->where('actor_id', $actorId)->latest()->limit(20)->get();
        $decisions = EvidenceDecision::query()->whereIn('evidence_record_id', $evidence->pluck('id'))->get()->keyBy('evidence_record_id');

        return $evidence->map(fn (EvidenceRecord $record): array => $record->toArray() + ['decision' => $decisions->get($record->id)?->toArray()])->all();
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
