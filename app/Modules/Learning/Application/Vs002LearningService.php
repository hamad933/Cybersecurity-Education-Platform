<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MasteryState;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\Learning\Models\ReviewTrigger;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

final class Vs002LearningService
{
    /** @return array<string,mixed> */
    public function practiceWorkspace(string $actorId): array
    {
        $practice = MicroPractice::query()->where('practice_id', 'MP-KU-D05-004-001')->latest('revision')->firstOrFail();

        return ['practice' => $practice->toArray(), 'latestAttempt' => PracticeAttempt::query()->where('actor_id', $actorId)->where('micro_practice_id', $practice->id)->latest()->first()?->toArray()];
    }

    /**
     * @param  array<string,string>  $answer
     * @return array{attempt:array<string,mixed>,failure_class:?string}
     */
    public function submitPractice(string $actorId, array $answer): array
    {
        $practice = MicroPractice::query()->where('practice_id', 'MP-KU-D05-004-001')->latest('revision')->firstOrFail();
        $definition = $practice->definitionPayload();
        $key = $definition['answer_key'] ?? null;
        if (! is_array($key) || ! is_array($key['rationale_concepts'] ?? null)) {
            throw new InvalidArgumentException('Versioned VS-002 practice answer key is unavailable.');
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
            $this->scheduleTrigger($actorId, $failureClass, (string) $definition['case_id'], 'practice_attempt', (string) $attempt->id, (string) $practice->id, "Actual VS-002 practice evaluation failed: {$failureClass}.");
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
        $rule = MasteryRuleRevision::query()->where('rule_id', config('vs002.mastery_rule_id'))->where('state', 'approved')->latest('revision')->firstOrFail();
        $records = collect($acceptedEvidence);
        $hasProvenance = $acceptedEvidence !== [] && $records->every(fn (array $record): bool => ($record['origin'] ?? null) === 'SIMULATED' && ($record['actor_id'] ?? null) === $actorId && is_array($record['source_claim_ids'] ?? null) && $record['source_claim_ids'] !== []);
        $hasPositive = $records->contains(fn (array $record): bool => ($record['result'] ?? null) === 'ALLOW' && in_array($record['case_id'] ?? null, ['CASE-WEB-001', 'CASE-WEB-005'], true));
        $hasNegative = $records->contains(fn (array $record): bool => ($record['result'] ?? null) === 'DENY');
        $hasFinding = $records->contains(fn (array $record): bool => is_array($record['finding_ids'] ?? null) && $record['finding_ids'] !== []);
        $hasRemediation = $records->contains(fn (array $record): bool => is_string($record['remediation_revision_id'] ?? null));
        $hasVerification = $records->contains(fn (array $record): bool => is_string($record['verification_run_id'] ?? null));
        $hasSafeRendering = $records->contains(fn (array $record): bool => ($record['case_id'] ?? null) === 'CASE-WEB-011');
        $hasReplay = $records->contains(fn (array $record): bool => in_array($record['run_id'] ?? null, $matchingReplayRunIds, true));
        $practice = MicroPractice::query()->where('practice_id', 'MP-KU-D05-004-001')->latest('revision')->firstOrFail();
        $hasAcceptedPractice = PracticeAttempt::query()->where('actor_id', $actorId)->where('micro_practice_id', $practice->id)->where('outcome', 'correct')->exists();
        $mastered = $hasAcceptedPractice && $hasPositive && $hasNegative && $hasFinding && $hasRemediation && $hasVerification && $hasSafeRendering && $hasReplay && $hasProvenance;
        $ids = $records->pluck('id')->sort()->values()->all();
        $evaluation = compact('actorId', 'ids', 'hasAcceptedPractice', 'hasPositive', 'hasNegative', 'hasFinding', 'hasRemediation', 'hasVerification', 'hasSafeRendering', 'hasReplay', 'hasProvenance');
        $state = MasteryState::query()->updateOrCreate(
            ['actor_id' => $actorId, 'knowledge_unit_id' => config('vs002.knowledge_unit_id')],
            ['mastery_rule_revision_id' => $rule->id, 'status' => $mastered ? 'MASTERED' : ($acceptedEvidence === [] ? 'NOT_MASTERED' : 'IN_PROGRESS'), 'evidence_record_ids' => $ids, 'evaluation_digest' => $this->digest($evaluation), 'evaluated_at' => now()],
        );
        if ($acceptedEvidence !== [] && ! $hasProvenance) {
            $first = $acceptedEvidence[0];
            $this->scheduleTrigger($actorId, 'missing_provenance', (string) ($first['case_id'] ?? ''), 'evidence', (string) ($first['id'] ?? ''), (string) ($first['rule_set_revision_id'] ?? ''), 'Accepted VS-002 evidence lacks actor-bound provenance.');
        }

        return $state->toArray();
    }

    /**
     * @param  array<string,mixed>  $observation
     * @return array<string,mixed>|null
     */
    public function recordObservedFailure(string $actorId, string $caseId, string $sourceType, string $sourceId, string $ruleRevisionId, array $observation): ?array
    {
        $failureClass = match (true) {
            ($observation['finding_created'] ?? false) === true => 'ownership_check_missed',
            ($observation['authentication_authorization_confused'] ?? false) === true => 'authentication_authorization_confusion',
            ($observation['client_role_trusted'] ?? false) === true => 'client_role_trusted',
            ($observation['http_decision_match'] ?? true) === false => 'wrong_http_decision',
            ($observation['trust_boundary_identified'] ?? true) === false => 'trust_boundary_missed',
            ($observation['safe_logging'] ?? true) === false => 'unsafe_logging',
            ($observation['remediation_verified'] ?? true) === false => 'remediation_not_verified',
            ($observation['unsupported_state_guessed'] ?? false) === true => 'unsupported_state_guessed',
            ($observation['safe_rendering'] ?? true) === false => 'unsafe_rendering_assumption',
            ($observation['provenance_present'] ?? true) === false => 'missing_provenance',
            ($observation['replay_match'] ?? true) === false => 'replay_mismatch',
            default => null,
        };
        if ($failureClass === null) {
            return null;
        }

        return $this->scheduleTrigger($actorId, $failureClass, $caseId, $sourceType, $sourceId, $ruleRevisionId, "Derived from actual VS-002 {$sourceType} result: {$failureClass}.");
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        return [
            'mastery' => MasteryState::query()->where('actor_id', $actorId)->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->first()?->toArray(),
            'triggers' => ReviewTrigger::query()->where('actor_id', $actorId)->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->latest()->limit(20)->get()->map(fn (ReviewTrigger $trigger): array => $trigger->toArray())->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $answer
     * @param  array<string,mixed>  $key
     */
    private function practiceFailure(array $answer, array $key, bool $rationaleValid): ?string
    {
        if (trim((string) $answer['rationale']) === '') {
            return 'trust_boundary_missed';
        }
        if ($answer['actor'] !== ($key['actor'] ?? null) || $answer['resource_owner'] !== ($key['resource_owner'] ?? null) || $answer['requested_action'] !== ($key['requested_action'] ?? null)) {
            return 'ownership_check_missed';
        }
        if ($answer['missing_trust_boundary'] !== ($key['missing_trust_boundary'] ?? null)) {
            return 'trust_boundary_missed';
        }
        if ($answer['expected_policy_decision'] === 'ALLOW_AUTHENTICATED_ONLY') {
            return 'authentication_authorization_confusion';
        }
        if ($answer['expected_policy_decision'] !== ($key['expected_policy_decision'] ?? null) || $answer['expected_http_response_class'] !== ($key['expected_http_response_class'] ?? null)) {
            return 'wrong_http_decision';
        }
        if ($answer['decisive_rule'] !== ($key['decisive_rule'] ?? null)) {
            return 'ownership_check_missed';
        }
        if ($answer['safe_detection_field'] !== ($key['safe_detection_field'] ?? null)) {
            return 'unsafe_logging';
        }
        if (! $rationaleValid) {
            return 'trust_boundary_missed';
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
        if (! in_array($failureClass, config('vs002.failure_classes'), true)) {
            throw new InvalidArgumentException('Unknown VS-002 review failure class.');
        }
        $query = ReviewTrigger::query()->where('actor_id', $actorId)->where('knowledge_unit_id', config('vs002.knowledge_unit_id'))->where('failure_class', $failureClass)->where('case_id', $caseId)->whereIn('status', ['open', 'scheduled']);
        if (($existing = $query->first()) !== null) {
            return $existing->toArray();
        }
        try {
            $trigger = ReviewTrigger::query()->create([
                'actor_id' => $actorId,
                'knowledge_unit_id' => config('vs002.knowledge_unit_id'),
                'case_id' => $caseId,
                'failure_class' => $failureClass,
                'source_reference' => 'KU-D05-004#failure-based-review',
                'source_type' => $sourceType,
                'source_id' => $sourceId !== '' ? $sourceId : null,
                'rule_revision_id' => $ruleRevisionId !== '' ? $ruleRevisionId : null,
                'schedule_reason' => $reason,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(2),
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
