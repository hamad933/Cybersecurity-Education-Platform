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
            return ['items' => [], 'activity' => $this->activitySummary(0, 0, null)];
        }

        $practices = MicroPractice::query()
            ->where('knowledge_unit_id', $knowledgeUnitId)
            ->orderBy('practice_id')
            ->orderByDesc('revision')
            ->get();

        if ($practices->isEmpty()) {
            return ['items' => [], 'activity' => $this->activitySummary(0, 0, null)];
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
            $latest = $practiceAttempts->first();
            $correctCount = $practiceAttempts->where('outcome', 'correct')->count();

            return [
                'id' => (string) $practice->id,
                'practice_id' => (string) $practice->practice_id,
                'revision' => (int) $practice->revision,
                'capability_id' => (string) $practice->capability_id,
                'attempt_count' => $practiceAttempts->count(),
                'successful_attempt_count' => $correctCount,
                'latest_outcome' => $latest instanceof PracticeAttempt ? (string) $latest->outcome : null,
                'latest_activity_at' => $latest?->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $attemptCount = array_sum(array_column($items, 'attempt_count'));
        $completedCount = count(array_filter($items, static fn (array $item): bool => $item['successful_attempt_count'] > 0));
        $latestActivity = collect($items)->pluck('latest_activity_at')->filter()->sortDesc()->first();

        return [
            'items' => $items,
            'activity' => $this->activitySummary($attemptCount, $completedCount, is_string($latestActivity) ? $latestActivity : null),
        ];
    }

    /** @return array<string, mixed> */
    private function activitySummary(int $attemptCount, int $completedPracticeCount, ?string $latestActivityAt): array
    {
        return [
            'attempt_count' => $attemptCount,
            'completed_practice_count' => $completedPracticeCount,
            'latest_activity_at' => $latestActivityAt,
            'semantic_scope' => 'journey_activity_only',
        ];
    }
}
