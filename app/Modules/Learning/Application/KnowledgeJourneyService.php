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
        $emptyAssessments = [
            'state' => 'NO_CANONICAL_ASSESSMENT_PERSISTENCE_IN_CURRENT_ARCHITECTURE',
            'integration_state' => 'AUTHORITATIVE_ASSESSMENT_CONTRACT_REQUIRED',
            'semantic_owner' => null,
            'fake_fallback_allowed' => false,
            'executable' => false,
            'href' => null,
            'definitions' => [],
            'results' => [],
        ];

        if ($knowledgeUnitId === null) {
            return [
                'state' => 'NO_CANONICAL_KNOWLEDGE_UNIT',
                'items' => [],
                'labs' => [],
                'assessments' => $emptyAssessments,
                'next' => ['state' => 'NO_PRACTICE_AVAILABLE', 'practice_id' => null],
                'activity' => $this->activitySummary(0, 0, 0, 0, null),
            ];
        }

        // Get only the latest revision of each practice
        $practices = MicroPractice::query()
            ->where('knowledge_unit_id', $knowledgeUnitId)
            ->orderBy('practice_id')
            ->orderByDesc('revision')
            ->get()
            ->unique('practice_id')
            ->values();

        if ($practices->isEmpty()) {
            return [
                'state' => 'NO_PRACTICE_ACTIVITY_DEFINED',
                'items' => [],
                'labs' => [],
                'assessments' => $emptyAssessments,
                'next' => ['state' => 'NO_PRACTICE_AVAILABLE', 'practice_id' => null],
                'today_projection' => [
                    'knowledge_unit_id' => $knowledgeUnitId,
                    'recommended_practice_id' => null,
                    'source' => 'persisted_learning_activity',
                    'mastery_included' => false,
                ],
                'activity' => $this->activitySummary(0, 0, 0, 0, null),
            ];
        }

        $attempts = PracticeAttempt::query()
            ->where('actor_id', $actorId)
            ->whereIn('micro_practice_id', $practices->pluck('id'))
            ->latest('created_at')
            ->get()
            ->groupBy('micro_practice_id');

        $labs = [];
        $nextPracticeId = null;

        $items = $practices->map(function (MicroPractice $practice) use ($attempts, &$labs, &$nextPracticeId): array {
            /** @var Collection<int, PracticeAttempt> $practiceAttempts */
            $practiceAttempts = $attempts->get((string) $practice->id, collect());
            $latest = $practiceAttempts->first();
            $correctCount = $practiceAttempts->where('outcome', 'correct')->count();

            $definitionValue = $practice->getAttribute('definition');
            /** @var array<string, mixed> $definition */
            $definition = is_array($definitionValue) && ! array_is_list($definitionValue)
                ? $definitionValue
                : [];
            $labReference = $this->labReference($definition['lab_reference'] ?? null);
            if ($labReference !== null) {
                $labs[$labReference['id']] = [
                    ...$labReference,
                    'preview_state' => 'REFERENCE_ONLY_FROM_LEARNING_DEFINITION',
                    'canonical_owner' => 'simulation_enterprise',
                    'prepare_run_handoff' => [
                        'target_workspace' => 'simulation_enterprise',
                        'target_area' => 'labs',
                        'state' => 'PARENT_INTEGRATION_REQUIRED',
                        'reason' => 'W03_AUTHORITATIVE_HANDOFF_ROUTE_AND_PAYLOAD_UNAVAILABLE',
                        'executable' => false,
                        'href' => null,
                    ],
                ];
            }

            $activityCompleted = $correctCount > 0;
            if (! $activityCompleted && $nextPracticeId === null) {
                $nextPracticeId = $practice->practice_id;
            }

            return [
                'id' => (string) $practice->id,
                'practice_id' => (string) $practice->practice_id,
                'revision' => (int) $practice->revision,
                'capability_id' => (string) $practice->capability_id,
                'definition' => $definition,
                'attempt_count' => $practiceAttempts->count(),
                'successful_attempt_count' => $correctCount,
                'latest_outcome' => $latest instanceof PracticeAttempt ? (string) $latest->outcome : null,
                'latest_activity_at' => $latest?->created_at?->toIso8601String(),
                'activity_state' => $practiceAttempts->isEmpty() ? 'NOT_STARTED' : ($activityCompleted ? 'COMPLETED' : 'IN_PROGRESS'),
                'recent_attempts' => $practiceAttempts->take(5)->map(fn ($a) => [
                    'case_id' => $a->case_id,
                    'outcome' => $a->outcome,
                    'created_at' => $a->created_at?->toIso8601String(),
                ])->all(),
                'activity_completed' => $activityCompleted,
                'completion_semantics' => 'practice_activity_only_not_mastery',
            ];
        })->values()->all();

        $attemptCount = array_sum(array_column($items, 'attempt_count'));
        $completedCount = count(array_filter($items, static fn (array $item): bool => $item['activity_completed']));
        $startedCount = count(array_filter($items, static fn (array $item): bool => $item['attempt_count'] > 0));
        $latestActivity = collect($items)->pluck('latest_activity_at')->filter()->sortDesc()->first();

        return [
            'state' => 'PRACTICE_ACTIVITY_AVAILABLE',
            'items' => $items,
            'labs' => array_values($labs),
            'assessments' => $emptyAssessments,
            'next' => [
                'state' => $nextPracticeId !== null ? 'CONTINUE_PRACTICE' : 'PRACTICE_ACTIVITY_COMPLETE',
                'practice_id' => $nextPracticeId,
                'completion_is_mastery' => false,
            ],
            'today_projection' => [
                'knowledge_unit_id' => $knowledgeUnitId,
                'recommended_practice_id' => $nextPracticeId,
                'source' => 'persisted_learning_activity',
                'mastery_included' => false,
            ],
            'activity' => $this->activitySummary(
                count($items),
                $attemptCount,
                $completedCount,
                $startedCount,
                is_string($latestActivity) ? $latestActivity : null,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function activitySummary(
        int $practiceCount,
        int $attemptCount,
        int $completedPracticeCount,
        int $startedPracticeCount,
        ?string $latestActivityAt,
    ): array {
        return [
            'attempt_count' => $attemptCount,
            'completed_practice_count' => $completedPracticeCount,
            'started_practice_count' => $startedPracticeCount,
            'practice_count' => $practiceCount,
            'completion_is_mastery' => false,
            'latest_activity_at' => $latestActivityAt,
            'semantic_scope' => 'journey_activity_only',
        ];
    }

    /** @return array{id: string, title_ar?: string, title_en?: string, summary_ar?: string}|null */
    private function labReference(mixed $value): ?array
    {
        if (! is_array($value) || ! is_string($value['id'] ?? null) || trim($value['id']) === '') {
            return null;
        }

        $reference = ['id' => trim($value['id'])];
        foreach (['title_ar', 'title_en', 'summary_ar'] as $key) {
            if (is_string($value[$key] ?? null) && trim($value[$key]) !== '') {
                $reference[$key] = trim($value[$key]);
            }
        }

        return $reference;
    }
}
