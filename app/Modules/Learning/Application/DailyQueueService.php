<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Models\MasteryState;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\Learning\Models\ReviewTrigger;
use Carbon\CarbonInterface;
use LogicException;

final class DailyQueueService
{
    /** @return list<array<string,mixed>> */
    public function forActor(string $actorId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));
        $items = [];
        $openTriggers = ReviewTrigger::query()
            ->where('actor_id', $actorId)
            ->where('status', 'open')
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
        foreach ($openTriggers as $trigger) {
            $scheduledAt = $trigger->getAttribute('scheduled_at');
            if (! $scheduledAt instanceof CarbonInterface) {
                throw new LogicException('Open review trigger has an invalid scheduled_at value.');
            }
            $overdueHours = max(0, (int) $scheduledAt->diffInHours(now(), false));
            $score = 100 + min($overdueHours, 72);
            $items[] = [
                'kind' => 'review',
                'knowledge_unit_id' => $trigger->knowledge_unit_id,
                'case_id' => $trigger->case_id,
                'score' => $score,
                'reason_code' => 'OPEN_FAILURE_REVIEW',
                'reason' => "Open review for {$trigger->failure_class}; scheduled {$scheduledAt->toIso8601String()}.",
                'source_reference' => $trigger->source_reference,
            ];
        }
        $mastery = MasteryState::query()->where('actor_id', $actorId)->get()->keyBy('knowledge_unit_id');
        foreach (['KU-AD-02', 'KU-D03-001', 'KU-D09-002'] as $knowledgeUnit) {
            $state = $mastery->get($knowledgeUnit);
            if ($state === null || $state->status !== 'mastered') {
                $recentFailures = PracticeAttempt::query()
                    ->where('actor_id', $actorId)
                    ->where('outcome', '!=', 'PASS')
                    ->count();
                $items[] = [
                    'kind' => 'mastery_gap',
                    'knowledge_unit_id' => $knowledgeUnit,
                    'case_id' => null,
                    'score' => 70 + min($recentFailures, 20),
                    'reason_code' => $state === null ? 'NO_MASTERY_EVALUATION' : 'MASTERY_NOT_REACHED',
                    'reason' => $state === null ? 'No actor-bound mastery evaluation exists.' : "Current actor-bound mastery state is {$state->status}.",
                    'source_reference' => 'mastery_states',
                ];
            }
        }
        usort($items, fn (array $left, array $right): int => [$right['score'], $left['knowledge_unit_id']] <=> [$left['score'], $right['knowledge_unit_id']]);

        return array_slice($items, 0, $limit);
    }
}
