<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use Illuminate\Support\Collection;

final class KnowledgeJourneyService
{
    /** @return array<string, mixed> */
    public function forUnit(?string $knowledgeUnitId, string $actorId): array
    {
        if ($knowledgeUnitId === null) {
            return $this->emptyJourney();
        }

        $practices = MicroPractice::query()
            ->where('knowledge_unit_id', $knowledgeUnitId)
            ->orderBy('practice_id')
            ->orderByDesc('revision')
            ->get()
            ->unique('practice_id')
            ->values();

        if ($practices->isEmpty()) {
            return $this->emptyJourney();
        }

        $attempts = PracticeAttempt::query()
            ->where('actor_id', $actorId)
            ->whereIn('micro_practice_id', $practices->pluck('id'))
            ->latest('created_at')
            ->get()
            ->groupBy('micro_practice_id');

        $items = $practices->map(function (MicroPractice $practice) use ($attempts): array {
            /** @var Collection<int, PracticeAttempt> $practiceAttempts */
            $practiceAttempts = $attempts->get((string) $practice->id, collect());

            return $this->practiceItem($practice, $practiceAttempts);
        })->values()->all();

        $attemptCount = array_sum(array_column($items, 'attempt_count'));
        $startedCount = count(array_filter($items, static fn (array $item): bool => $item['attempt_count'] > 0));
        $completedCount = count(array_filter($items, static fn (array $item): bool => $item['activity_completed'] === true));
        $latestActivity = collect($items)->pluck('latest_activity_at')->filter()->sortDesc()->first();
        $next = $this->nextContext($items);

        return [
            'items' => $items,
            'activity' => $this->activitySummary(
                $attemptCount,
                $startedCount,
                $completedCount,
                count($items),
                is_string($latestActivity) ? $latestActivity : null,
            ),
            'next' => $next,
            'today_projection' => $this->todayProjection($knowledgeUnitId, $next),
            'assessments' => $this->assessmentBoundary(),
            'labs' => array_values(array_filter(array_map(
                static fn (array $item): ?array => is_array($item['lab_preview']) ? $item['lab_preview'] : null,
                $items,
            ))),
            'evidence_context' => [
                'state' => 'CONTEXT_ONLY',
                'formal_review' => 'owned_by_progress_evidence',
                'mastery_judgment' => 'owned_by_progress_evidence',
            ],
        ];
    }

    /**
     * @param  Collection<int, PracticeAttempt>  $practiceAttempts
     * @return array<string, mixed>
     */
    private function practiceItem(MicroPractice $practice, Collection $practiceAttempts): array
    {
        $latest = $practiceAttempts->first();
        $correctCount = $practiceAttempts->where('outcome', 'correct')->count();
        $definition = $practice->definitionPayload();
        $activityCompleted = $correctCount > 0;

        return [
            'id' => (string) $practice->id,
            'practice_id' => (string) $practice->practice_id,
            'revision' => (int) $practice->revision,
            'capability_id' => (string) $practice->capability_id,
            'definition' => $this->definitionSummary($definition),
            'attempt_count' => $practiceAttempts->count(),
            'successful_attempt_count' => $correctCount,
            'latest_outcome' => $latest instanceof PracticeAttempt ? (string) $latest->outcome : null,
            'latest_activity_at' => $latest?->created_at?->toIso8601String(),
            'activity_state' => $activityCompleted
                ? 'ACTIVITY_COMPLETED'
                : ($practiceAttempts->isEmpty() ? 'NOT_STARTED' : 'IN_PROGRESS'),
            'activity_completed' => $activityCompleted,
            'completion_semantics' => 'practice_activity_only_not_mastery',
            'recent_attempts' => $practiceAttempts
                ->take(5)
                ->map(static fn (PracticeAttempt $attempt): array => [
                    'id' => (string) $attempt->id,
                    'case_id' => (string) $attempt->case_id,
                    'outcome' => (string) $attempt->outcome,
                    'rationale_valid' => (bool) $attempt->rationale_valid,
                    'failure_class' => $attempt->failure_class !== null ? (string) $attempt->failure_class : null,
                    'created_at' => $attempt->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'lab_preview' => $this->labPreview($definition),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function definitionSummary(array $definition): array
    {
        return [
            'kind' => $this->stringValue($definition, 'kind'),
            'title_ar' => $this->stringValue($definition, 'title_ar'),
            'title_en' => $this->stringValue($definition, 'title_en'),
            'prompt_ar' => $this->stringValue($definition, 'prompt_ar'),
            'prompt_en' => $this->stringValue($definition, 'prompt_en'),
            'difficulty' => $this->stringValue($definition, 'difficulty'),
            'estimated_minutes' => $this->integerValue($definition, 'estimated_minutes'),
            'mode' => $this->stringValue($definition, 'mode'),
            'tags' => $this->stringList($definition['tags'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    private function labPreview(array $definition): ?array
    {
        $reference = $definition['lab_reference'] ?? null;
        if (! is_array($reference) || array_is_list($reference)) {
            return null;
        }

        $id = $this->firstStringValue($reference, ['id', 'lab_id', 'reference_id']);
        if ($id === null) {
            return null;
        }

        return [
            'id' => $id,
            'title_ar' => $this->stringValue($reference, 'title_ar'),
            'title_en' => $this->stringValue($reference, 'title_en'),
            'summary_ar' => $this->stringValue($reference, 'summary_ar'),
            'preview_state' => 'REFERENCE_ONLY_FROM_LEARNING_DEFINITION',
            'canonical_owner' => 'simulation_enterprise',
            'prepare_run_handoff' => [
                'target_workspace' => 'simulation_enterprise',
                'target_area' => 'labs',
                'state' => 'PARENT_INTEGRATION_REQUIRED',
                'href' => null,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function nextContext(array $items): array
    {
        if ($items === []) {
            return [
                'state' => 'NO_PRACTICE_DEFINITIONS',
                'practice_id' => null,
                'reason' => 'no_canonical_learning_activity',
            ];
        }

        foreach ($items as $item) {
            if ($item['activity_completed'] !== true) {
                return [
                    'state' => $item['attempt_count'] > 0 ? 'CONTINUE_PRACTICE' : 'START_PRACTICE',
                    'practice_id' => $item['practice_id'],
                    'reason' => $item['attempt_count'] > 0 ? 'persisted_incomplete_activity' : 'first_unstarted_practice',
                ];
            }
        }

        return [
            'state' => 'REVIEW_ACTIVITY',
            'practice_id' => $items[0]['practice_id'],
            'reason' => 'all_practice_activity_completed_without_mastery_judgment',
        ];
    }

    /**
     * @param  array<string, mixed>  $next
     * @return array<string, mixed>
     */
    private function todayProjection(string $knowledgeUnitId, array $next): array
    {
        return [
            'knowledge_unit_id' => $knowledgeUnitId,
            'recommended_practice_id' => $next['practice_id'],
            'state' => $next['state'],
            'reason' => $next['reason'],
            'source' => 'persisted_learning_activity',
            'projection_ready' => $next['practice_id'] !== null,
            'mastery_included' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function assessmentBoundary(): array
    {
        return [
            'definitions' => [],
            'results' => [],
            'state' => 'NO_CANONICAL_ASSESSMENT_PERSISTENCE_IN_CURRENT_ARCHITECTURE',
            'semantic_owner' => 'learning',
            'fake_fallback_allowed' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyJourney(): array
    {
        $next = $this->nextContext([]);

        return [
            'items' => [],
            'activity' => $this->activitySummary(0, 0, 0, 0, null),
            'next' => $next,
            'today_projection' => [
                'knowledge_unit_id' => null,
                'recommended_practice_id' => null,
                'state' => $next['state'],
                'reason' => $next['reason'],
                'source' => 'persisted_learning_activity',
                'projection_ready' => false,
                'mastery_included' => false,
            ],
            'assessments' => $this->assessmentBoundary(),
            'labs' => [],
            'evidence_context' => [
                'state' => 'CONTEXT_ONLY',
                'formal_review' => 'owned_by_progress_evidence',
                'mastery_judgment' => 'owned_by_progress_evidence',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function activitySummary(
        int $attemptCount,
        int $startedPracticeCount,
        int $completedPracticeCount,
        int $practiceCount,
        ?string $latestActivityAt,
    ): array {
        return [
            'attempt_count' => $attemptCount,
            'practice_count' => $practiceCount,
            'started_practice_count' => $startedPracticeCount,
            'completed_practice_count' => $completedPracticeCount,
            'latest_activity_at' => $latestActivityAt,
            'semantic_scope' => 'journey_activity_only',
            'completion_is_mastery' => false,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function stringValue(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** @param array<string, mixed> $payload */
    private function integerValue(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        return is_int($value) && $value >= 0 ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function firstStringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->stringValue($payload, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(static fn (string $item): string => trim($item))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }
}
