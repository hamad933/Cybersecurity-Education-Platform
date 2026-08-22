<?php

namespace App\Modules\Curriculum\Application;

use App\Modules\Curriculum\Models\CurriculumPlacement;

final class CurriculumKnowledgeService
{
    /**
     * @param  list<string>  $knowledgeUnitIds
     * @return list<array<string, mixed>>
     */
    public function placements(array $knowledgeUnitIds): array
    {
        if ($knowledgeUnitIds === []) {
            return [];
        }

        return CurriculumPlacement::query()
            ->whereIn('knowledge_unit_id', $knowledgeUnitIds)
            ->orderBy('capability_id')
            ->orderBy('knowledge_unit_id')
            ->orderByDesc('revision')
            ->get()
            ->map(static function (CurriculumPlacement $placement): array {
                $lifecycle = $placement->getAttribute('lifecycle');

                return [
                    'id' => (string) $placement->id,
                    'capability_id' => (string) $placement->capability_id,
                    'knowledge_unit_id' => (string) $placement->knowledge_unit_id,
                    'revision' => (int) $placement->revision,
                    'lifecycle' => is_array($lifecycle) ? $lifecycle : [],
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function placementsForUnit(?string $knowledgeUnitId): array
    {
        return $knowledgeUnitId === null ? [] : $this->placements([$knowledgeUnitId]);
    }
}
