<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MasteryState;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\Learning\Models\ReviewTrigger;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

final class Vs001LearningService
{
    /** @return array{practice:array<string,mixed>,latestAttempt:?array<string,mixed>} */
    public function practiceWorkspace(string $actorId): array
    {
        $practice = MicroPractice::query()->where('practice_id', 'MP-KU-AD-02-001')->latest('revision')->firstOrFail();

        return [
            'practice' => $practice->toArray(),
            'latestAttempt' => PracticeAttempt::query()->where('actor_id', $actorId)->latest()->first()?->toArray(),
        ];
    }

    /**
     * @param  array{selected_outcome:string,decisive_step_id:string,decisive_ace_id:string,relevant_requested_mask:string,remaining_mask:string,rationale:string}  $answer
     * @return array{attempt:array<string,mixed>,failure_class:?string}
     */
    public function submitPractice(string $actorId, array $answer): array
    {
        $practice = MicroPractice::query()->where('practice_id', 'MP-KU-AD-02-001')->latest('revision')->firstOrFail();
        $definition = $practice->definitionPayload();
        $key = $definition['answer_key'] ?? null;
        if (! is_array($key) || ! is_array($key['rationale_concepts'] ?? null)) {
            throw new InvalidArgumentException('Versioned micro-practice answer key is unavailable.');
        }

        $rationaleValid = $this->rationaleMatchesConcepts($answer['rationale'], $key['rationale_concepts']);
        $failureClass = $this->practiceFailure($answer, $key, $rationaleValid);
        $attempt = PracticeAttempt::query()->create([
            'micro_practice_id' => $practice->id,
            'actor_id' => $actorId,
            'case_id' => $definition['case_id'],
            'answer' => $answer,
            'outcome' => $failureClass === null ? 'correct' : 'incorrect',
            'rationale_valid' => $rationaleValid,
            'failure_class' => $failureClass,
        ]);
        if ($failureClass !== null) {
            $this->scheduleTrigger(
                $actorId,
                $failureClass,
                (string) $definition['case_id'],
                'practice_attempt',
                (string) $attempt->id,
                (string) $practice->id,
                "Actual versioned practice evaluation failed: {$failureClass}.",
            );
        }

        return ['attempt' => $attempt->toArray(), 'failure_class' => $failureClass];
    }

    /**
     * @param  list<array<string,mixed>>  $acceptedEvidence
     * @param  list<string>  $matchingReplayRunIds
     * @return array<string,mixed>
     */
    public function evaluateMastery(string $actorId, array $acceptedEvidence, array $matchingReplayRunIds): array
    {
        $rule = MasteryRuleRevision::query()->where('rule_id', config('vs001.mastery_rule_id'))->where('state', 'approved')->latest('revision')->firstOrFail();
        $hasProvenance = $acceptedEvidence !== [] && collect($acceptedEvidence)->every(
            fn (array $record): bool => is_array($record['source_claim_ids'] ?? null)
                && $record['source_claim_ids'] !== []
                && ($record['origin'] ?? null) === 'SIMULATED'
                && ($record['actor_id'] ?? null) === $actorId,
        );
        $hasPositive = collect($acceptedEvidence)->contains(fn (array $record): bool => ($record['result'] ?? null) === 'ALLOW');
        $hasNegative = collect($acceptedEvidence)->contains(fn (array $record): bool => ($record['result'] ?? null) === 'DENY');
        $hasReplay = collect($acceptedEvidence)->contains(fn (array $record): bool => in_array($record['run_id'] ?? null, $matchingReplayRunIds, true));
        $hasUnsupported = collect($acceptedEvidence)->contains(fn (array $record): bool => ($record['result'] ?? null) === 'UNSUPPORTED_STATE');
        $mastered = $hasPositive && $hasNegative && $hasReplay && $hasProvenance && $hasUnsupported;
        $ids = collect($acceptedEvidence)->pluck('id')->sort()->values()->all();
        $evaluation = [
            'rule_revision_id' => $rule->id,
            'actor_id' => $actorId,
            'accepted_evidence_ids' => $ids,
            'positive' => $hasPositive,
            'negative' => $hasNegative,
            'replay' => $hasReplay,
            'provenance' => $hasProvenance,
            'unsupported_handling' => $hasUnsupported,
        ];
        $state = MasteryState::query()->updateOrCreate(
            ['actor_id' => $actorId, 'knowledge_unit_id' => config('vs001.knowledge_unit_id')],
            ['mastery_rule_revision_id' => $rule->id, 'status' => $mastered ? 'MASTERED' : ($acceptedEvidence === [] ? 'NOT_MASTERED' : 'IN_PROGRESS'), 'evidence_record_ids' => $ids, 'evaluation_digest' => $this->digest($evaluation), 'evaluated_at' => now()],
        );

        if ($acceptedEvidence !== [] && ! $hasProvenance) {
            $first = $acceptedEvidence[0];
            $this->scheduleTrigger($actorId, 'missing_provenance', (string) ($first['case_id'] ?? ''), 'evidence', (string) ($first['id'] ?? ''), (string) ($first['rule_set_revision_id'] ?? ''), 'Accepted evidence is missing required actor-bound provenance.');
        }

        return $state->toArray();
    }

    /**
     * Derives a failure from trusted simulator/evidence observations; callers cannot select a failure class.
     *
     * @param  array<string,mixed>  $observation
     * @return array<string,mixed>|null
     */
    public function recordObservedFailure(string $actorId, string $caseId, string $sourceType, string $sourceId, string $ruleRevisionId, array $observation): ?array
    {
        $failureClass = match (true) {
            array_key_exists('replay_match', $observation) && $observation['replay_match'] === false => 'replay_mismatch',
            array_key_exists('provenance_present', $observation) && $observation['provenance_present'] === false => 'missing_provenance',
            array_key_exists('retention_passed', $observation) && $observation['retention_passed'] === false => 'failed_retention',
            isset($observation['expected_group_attribute'], $observation['actual_group_attribute'])
                && $observation['expected_group_attribute'] !== $observation['actual_group_attribute'] => 'wrong_group_attribute',
            isset($observation['expected_outcome'], $observation['actual_outcome'])
                && $observation['expected_outcome'] !== $observation['actual_outcome'] => 'incorrect_decision',
            default => null,
        };

        if ($failureClass === null) {
            return null;
        }

        return $this->scheduleTrigger($actorId, $failureClass, $caseId, $sourceType, $sourceId, $ruleRevisionId, "Derived from actual {$sourceType} result: {$failureClass}.");
    }

    /** @return array{mastery:?array<string,mixed>,triggers:list<array<string,mixed>>} */
    public function masteryWorkspace(string $actorId): array
    {
        return [
            'mastery' => MasteryState::query()->where('actor_id', $actorId)->where('knowledge_unit_id', config('vs001.knowledge_unit_id'))->first()?->toArray(),
            'triggers' => ReviewTrigger::query()->where('actor_id', $actorId)->latest()->limit(8)->get()->map(fn (ReviewTrigger $trigger): array => $trigger->toArray())->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $answer
     * @param  array<string,mixed>  $key
     */
    private function practiceFailure(array $answer, array $key, bool $rationaleValid): ?string
    {
        if (trim($answer['rationale']) === '') {
            return 'rationale_missing';
        }
        if ($answer['selected_outcome'] === 'UNSUPPORTED_STATE' && $answer['selected_outcome'] !== ($key['selected_outcome'] ?? null)) {
            return 'unsupported_state_guess';
        }
        if ($answer['selected_outcome'] !== ($key['selected_outcome'] ?? null)) {
            return 'incorrect_decision';
        }
        if ($answer['decisive_step_id'] !== ($key['decisive_step_id'] ?? null) || $answer['decisive_ace_id'] !== ($key['decisive_ace_id'] ?? null)) {
            return 'missed_decisive_ace';
        }
        if ($answer['relevant_requested_mask'] !== ($key['relevant_requested_mask'] ?? null) || $answer['remaining_mask'] !== ($key['remaining_mask'] ?? null)) {
            return 'requested_mask_error';
        }
        if (! $rationaleValid) {
            return 'incorrect_decision';
        }

        return null;
    }

    /** @param list<list<string>> $concepts */
    private function rationaleMatchesConcepts(string $rationale, array $concepts): bool
    {
        $normalized = mb_strtolower(trim($rationale));
        if ($normalized === '') {
            return false;
        }

        foreach ($concepts as $alternatives) {
            if ($alternatives === []) {
                return false;
            }
            $matched = false;
            foreach ($alternatives as $term) {
                if (str_contains($normalized, mb_strtolower($term))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string,mixed> */
    private function scheduleTrigger(string $actorId, string $failureClass, string $caseId, string $sourceType, string $sourceId, string $ruleRevisionId, string $reason): array
    {
        if (! in_array($failureClass, config('vs001.failure_classes'), true)) {
            throw new InvalidArgumentException('Unknown review failure class.');
        }
        $query = ReviewTrigger::query()
            ->where('actor_id', $actorId)
            ->where('knowledge_unit_id', config('vs001.knowledge_unit_id'))
            ->where('failure_class', $failureClass)
            ->where('case_id', $caseId)
            ->whereIn('status', ['open', 'scheduled']);
        if (($existing = $query->first()) !== null) {
            return $existing->toArray();
        }

        try {
            $trigger = ReviewTrigger::query()->create([
                'actor_id' => $actorId,
                'knowledge_unit_id' => config('vs001.knowledge_unit_id'),
                'case_id' => $caseId,
                'failure_class' => $failureClass,
                'source_reference' => 'KU-AD-02#failure-based-review',
                'source_type' => $sourceType,
                'source_id' => $sourceId !== '' ? $sourceId : null,
                'rule_revision_id' => $ruleRevisionId !== '' ? $ruleRevisionId : null,
                'schedule_reason' => $reason,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDay(),
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
            $trigger = $query->firstOrFail();
        }

        return $trigger->toArray();
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
