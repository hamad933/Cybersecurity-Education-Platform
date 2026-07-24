<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MasteryState;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\Learning\Models\ReviewTrigger;
use InvalidArgumentException;

final class Vs003LearningService
{
    /**
     * @param  array<string,string>  $answer
     * @return array{attempt:array<string,mixed>,failure_class:?string}
     */
    public function submitPractice(string $actorId, array $answer): array
    {
        $practice = MicroPractice::query()
            ->where('practice_id', config('vs003.practice_id'))
            ->latest('revision')
            ->firstOrFail();
        $definition = $practice->definitionPayload();
        $key = is_array($definition['answer_key'] ?? null) ? $definition['answer_key'] : [];

        $failure = match (true) {
            ($answer['outcome'] ?? null) !== ($key['outcome'] ?? null) => 'wrong_triage',
            ($answer['telemetry_health'] ?? null) !== ($key['telemetry_health'] ?? null) => 'telemetry_quality_missed',
            ($answer['alternative_hypothesis'] ?? null) !== ($key['alternative_hypothesis'] ?? null) => 'alternative_hypothesis_missed',
            default => null,
        };
        $attempt = PracticeAttempt::query()->create([
            'micro_practice_id' => $practice->id,
            'actor_id' => $actorId,
            'case_id' => $definition['case_id'],
            'answer' => $answer,
            'outcome' => $failure === null ? 'correct' : 'incorrect',
            'rationale_valid' => $failure === null,
            'failure_class' => $failure,
        ]);

        if ($failure !== null) {
            $this->trigger(
                $actorId,
                $failure,
                (string) $attempt->case_id,
                'practice_attempt',
                (string) $attempt->id,
                (string) $practice->id,
                'Actual VS-003 practice failure: '.$failure.'.',
            );
        }

        return ['attempt' => $attempt->toArray(), 'failure_class' => $failure];
    }

    /**
     * @param  list<array<string,mixed>>  $evidence
     * @param  array<string,mixed>  $simulationFacts
     * @param  array<string,mixed>  $evidenceFacts
     * @return array<string,mixed>
     */
    public function evaluateMastery(
        string $actorId,
        array $evidence,
        array $simulationFacts,
        array $evidenceFacts,
    ): array {
        $rule = MasteryRuleRevision::query()
            ->where('rule_id', config('vs003.mastery_rule_id'))
            ->where('state', 'approved')
            ->latest('revision')
            ->firstOrFail();
        $records = collect($evidence);
        $sameActorAndProvenance = $evidence !== [] && $records->every(
            fn (array $record): bool => ($record['actor_id'] ?? null) === $actorId
                && ($record['origin'] ?? null) === 'SIMULATED'
                && ($record['capability_id'] ?? null) === config('vs003.capability_id')
                && ($record['knowledge_unit_id'] ?? null) === config('vs003.knowledge_unit_id')
                && ($record['locked'] ?? false) === true
                && is_array($record['source_claim_ids'] ?? null)
                && $record['source_claim_ids'] !== []
                && $this->evidenceDigestIsValid($record),
        );
        $outcomes = $records->pluck('result')->unique()->values()->all();
        $practice = MicroPractice::query()
            ->where('practice_id', config('vs003.practice_id'))
            ->latest('revision')
            ->firstOrFail();
        $correctPractice = PracticeAttempt::query()
            ->where('actor_id', $actorId)
            ->where('micro_practice_id', $practice->id)
            ->where('outcome', 'correct')
            ->whereNull('failure_class')
            ->exists();
        $requiredOutcomes = [
            'SUSPICIOUS',
            'INCIDENT_CONFIRMED',
            'INSUFFICIENT_TELEMETRY',
            'UNSUPPORTED_STATE',
        ];
        $requiredOutcomesPresent = collect($requiredOutcomes)->every(
            static fn (string $outcome): bool => in_array($outcome, $outcomes, true),
        );
        $verification = is_array($evidenceFacts['verification'] ?? null)
            ? $evidenceFacts['verification']
            : null;
        $verifiedForActor = $verification !== null
            && ($verification['actor_id'] ?? null) === $actorId
            && ($verification['passed'] ?? false) === true;

        $checks = [
            'same_actor_and_provenance' => $sameActorAndProvenance,
            'correct_practice' => $correctPractice,
            'required_outcomes' => $requiredOutcomesPresent,
            'correct_triage' => ($simulationFacts['correct_triage'] ?? false) === true,
            'alternative_hypothesis' => ($simulationFacts['alternative_hypothesis_recorded'] ?? false) === true,
            'custody_preserved' => ($evidenceFacts['custody_preserved'] ?? false) === true,
            'approved_containment' => ($evidenceFacts['approved_containment'] ?? false) === true,
            'published_control' => ($evidenceFacts['published_control'] ?? false) === true,
            'verification_passed' => ($evidenceFacts['verification_passed'] ?? false) === true && $verifiedForActor,
        ];
        $mastered = ! in_array(false, $checks, true);

        if ($evidence !== [] && ! $sameActorAndProvenance) {
            $sourceId = (string) ($records->first()['id'] ?? $rule->id);
            $this->trigger(
                $actorId,
                'missing_provenance',
                'VS3-MASTERY',
                'mastery_evaluation',
                $sourceId,
                (string) $rule->id,
                'Mastery evaluation found evidence without complete actor-bound provenance.',
            );
        }
        if (($evidenceFacts['published_control'] ?? false) === true && ! $verifiedForActor) {
            $this->trigger(
                $actorId,
                'control_verification_missed',
                'VS3-MASTERY',
                'mastery_evaluation',
                (string) $rule->id,
                (string) $rule->id,
                'A published simulated control exists without a successful actor-bound verification replay.',
            );
        }

        $status = $mastered ? 'MASTERED' : ($evidence === [] ? 'NOT_MASTERED' : 'IN_PROGRESS');
        $evaluation = [
            'actor_id' => $actorId,
            'mastery_rule_revision_id' => (string) $rule->id,
            'checks' => $checks,
            'outcomes' => $outcomes,
            'evidence_record_ids' => $records->pluck('id')->sort()->values()->all(),
            'triage_record_ids' => $simulationFacts['triage_record_ids'] ?? [],
            'verification_replay_id' => $verification['id'] ?? null,
        ];

        return MasteryState::query()->updateOrCreate(
            [
                'actor_id' => $actorId,
                'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
            ],
            [
                'mastery_rule_revision_id' => $rule->id,
                'status' => $status,
                'evidence_record_ids' => $evaluation['evidence_record_ids'],
                'evaluation_digest' => $this->digest($evaluation),
                'evaluated_at' => now(),
            ],
        )->toArray() + ['checks' => $checks];
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        $practice = MicroPractice::query()
            ->where('practice_id', config('vs003.practice_id'))
            ->latest('revision')
            ->firstOrFail();
        $attempts = PracticeAttempt::query()
            ->where('actor_id', $actorId)
            ->where('micro_practice_id', $practice->id)
            ->latest()
            ->limit(10)
            ->get();
        $mastery = MasteryState::query()
            ->where('actor_id', $actorId)
            ->where('knowledge_unit_id', config('vs003.knowledge_unit_id'))
            ->first();
        $reviews = ReviewTrigger::query()
            ->where('actor_id', $actorId)
            ->where('knowledge_unit_id', config('vs003.knowledge_unit_id'))
            ->latest()
            ->get();

        return [
            'practice' => [
                'id' => (string) $practice->id,
                'revision' => (int) $practice->revision,
                'definition' => $practice->definitionPayload(),
            ],
            'attempts' => $attempts->map(fn (PracticeAttempt $attempt): array => $attempt->toArray())->all(),
            'mastery' => $mastery?->toArray(),
            'review_triggers' => $reviews->map(fn (ReviewTrigger $review): array => $review->toArray())->all(),
        ];
    }

    /** @return array<string,mixed> */
    private function trigger(
        string $actorId,
        string $failure,
        string $caseId,
        string $sourceType,
        string $sourceId,
        string $ruleId,
        string $reason,
    ): array {
        if (! in_array($failure, config('vs003.failure_classes'), true)) {
            throw new InvalidArgumentException('Unknown VS-003 review failure class.');
        }

        $identity = [
            'actor_id' => $actorId,
            'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
            'failure_class' => $failure,
            'case_id' => $caseId,
        ];
        $existing = ReviewTrigger::query()
            ->where($identity)
            ->whereIn('status', ['open', 'scheduled'])
            ->first();
        if ($existing !== null) {
            return $existing->toArray();
        }

        return ReviewTrigger::query()->create($identity + [
            'source_reference' => 'KU-D09-002#failure-based-review',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'rule_revision_id' => $ruleId,
            'schedule_reason' => $reason,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ])->toArray();
    }

    /** @param array<string,mixed> $record */
    private function evidenceDigestIsValid(array $record): bool
    {
        $payload = [];
        foreach ([
            'origin',
            'actor_id',
            'capability_id',
            'knowledge_unit_id',
            'scenario_revision_id',
            'rule_set_revision_id',
            'enterprise_baseline_revision_id',
            'run_id',
            'case_id',
            'input_digest',
            'trace_digest',
            'result',
            'limitations',
            'source_claim_ids',
            'locked',
        ] as $key) {
            if (! array_key_exists($key, $record)) {
                return false;
            }
            $payload[$key] = $record[$key];
        }

        $stored = $record['content_digest'] ?? null;

        return is_string($stored) && hash_equals($stored, $this->digest($payload));
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
