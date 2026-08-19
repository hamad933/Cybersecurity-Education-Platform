<?php

namespace App\Modules\Curriculum\Application;

use App\Modules\Curriculum\Application\Visualize\OverlayProjector;
use App\Modules\Curriculum\Application\Visualize\VisualizationProjection;
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

    /**
     * Builds a representation from canonical curriculum relationships. The returned MAP stores scope,
     * canonical references, and visual positions only; it is never a second canonical object store.
     *
     * @param  array<string, mixed>|null  $knowledgeUnit
     * @param  array<string, mixed>  $overlaySignals
     * @param  array<string, mixed>|null  $savedMap
     * @return array<string, mixed>
     */
    public function visualization(
        ?array $knowledgeUnit,
        array $overlaySignals = [],
        ?array $savedMap = null,
    ): array {
        if ($knowledgeUnit === null || ! is_string($knowledgeUnit['id'] ?? null)) {
            return [
                'map' => [
                    'saved' => false,
                    'id' => null,
                    'state' => 'NO_CANONICAL_SCOPE',
                    'scope' => null,
                    'canonical_node_ids' => [],
                    'visual_positions' => [],
                ],
                'view' => [
                    'implemented' => VisualizationProjection::VIEWS,
                    'not_implemented' => [],
                ],
                'overlay' => (new OverlayProjector)->project($overlaySignals),
                'graph' => [
                    'nodes' => [],
                    'edges' => [],
                    'source' => 'canonical_curriculum_projection',
                ],
            ];
        }

        $unitId = $knowledgeUnit['id'];
        $placements = $this->placementsForUnit($unitId);
        $nodes = [[
            'id' => 'ku:'.$unitId,
            'kind' => 'knowledge_unit',
            'label' => (string) ($knowledgeUnit['title_ar'] ?? $unitId),
            'technical_label' => $unitId,
        ]];

        foreach (collect($placements)->pluck('capability_id')->unique()->values() as $capabilityId) {
            $nodes[] = [
                'id' => 'capability:'.$capabilityId,
                'kind' => 'capability',
                'label' => (string) $capabilityId,
                'technical_label' => (string) $capabilityId,
            ];
        }

        $relationships = array_map(static fn (array $placement): array => [
            'id' => 'placement:'.$placement['id'],
            'from' => 'capability:'.$placement['capability_id'],
            'to' => 'ku:'.$placement['knowledge_unit_id'],
            'type' => 'canonical_placement',
            'revision' => $placement['revision'],
            'lifecycle' => $placement['lifecycle'],
        ], $placements);

        return (new VisualizationProjection)->project(
            $unitId,
            $nodes,
            $relationships,
            $savedMap,
            $overlaySignals,
        );
    }
}
