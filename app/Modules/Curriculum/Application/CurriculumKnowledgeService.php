<?php

namespace App\Modules\Curriculum\Application;

use App\Modules\Curriculum\Application\Visualize\OverlayProjector;
use App\Modules\Curriculum\Application\Visualize\VisualizationProjection;
use App\Modules\Curriculum\Models\CurriculumPlacement;

final class CurriculumKnowledgeService
{
    private const WORLD_RECIPE = 'bounded_curriculum_neighborhood_v1';

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

    /**
     * @return list<array<string, mixed>>
     */
    public function placementsForUnit(?string $knowledgeUnitId): array
    {
        return $knowledgeUnitId === null ? [] : $this->placements([$knowledgeUnitId]);
    }

    /**
     * Builds a typed read projection from current eligible Curriculum records.
     * MAP remains representation state; canonical objects and relationships
     * remain owned by their existing modules.
     *
     * @param  array<string, mixed>|null  $knowledgeUnit
     * @param  array<string, mixed>  $overlaySignals
     * @param  array<string, mixed>|null  $savedMap
     * @param  list<array<string, mixed>>  $catalog
     * @return array<string, mixed>
     */
    public function visualization(
        ?array $knowledgeUnit,
        array $overlaySignals = [],
        ?array $savedMap = null,
        array $catalog = [],
    ): array {
        if ($knowledgeUnit === null || ! is_string($knowledgeUnit['id'] ?? null)) {
            return [
                'map' => [
                    'saved' => false,
                    'id' => null,
                    'name' => null,
                    'state' => 'NO_CANONICAL_SCOPE',
                    'state_label' => 'لا يوجد نطاق قانوني نشط',
                    'reason' => 'NO_CANONICAL_SCOPE',
                    'scope' => null,
                    'world' => null,
                    'default_view' => 'Tree',
                    'canonical_node_ids' => [],
                    'visual_positions' => [],
                ],
                'view' => [
                    'implemented' => VisualizationProjection::VIEWS,
                    'not_implemented' => [],
                    'default' => 'Tree',
                ],
                'overlay' => (new OverlayProjector)->project($overlaySignals),
                'graph' => [
                    'nodes' => [],
                    'edges' => [],
                    'source' => 'canonical_curriculum_typed_projection',
                    'recipe' => self::WORLD_RECIPE,
                ],
            ];
        }

        $unitId = $knowledgeUnit['id'];
        $catalogById = $this->catalogById($catalog, $knowledgeUnit);
        $placements = $this->currentPlacements(array_keys($catalogById));
        $memberIds = $this->boundedWorldMemberIds($unitId, $placements, $catalogById);
        [$nodes, $relationships] = $this->typedGraph($memberIds, $placements, $catalogById);
        $derivedSignals = $this->prerequisiteSignals($relationships);

        return (new VisualizationProjection)->project(
            $unitId,
            $nodes,
            $relationships,
            $savedMap,
            array_replace($derivedSignals, $overlaySignals),
            self::WORLD_RECIPE,
        );
    }

    /**
     * @param  list<string>  $knowledgeUnitIds
     * @return list<array<string, mixed>>
     */
    private function currentPlacements(array $knowledgeUnitIds): array
    {
        $current = [];
        $seen = [];

        foreach ($this->placements($knowledgeUnitIds) as $placement) {
            $key = $placement['capability_id'].'|'.$placement['knowledge_unit_id'];
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            if ($this->isCurrentEligible($placement['lifecycle'])) {
                $current[] = $placement;
            }
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $lifecycle
     */
    private function isCurrentEligible(array $lifecycle): bool
    {
        $state = strtolower((string) ($lifecycle['state'] ?? 'active'));

        return ! in_array($state, ['draft', 'inactive', 'retired', 'superseded', 'withdrawn', 'deleted'], true);
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @param  array<string, mixed>  $active
     * @return array<string, array<string, mixed>>
     */
    private function catalogById(array $catalog, array $active): array
    {
        $indexed = [];
        foreach ($catalog as $item) {
            if (is_array($item) && is_string($item['id'] ?? null)) {
                $indexed[$item['id']] = $item;
            }
        }
        $indexed[$active['id']] = $active;

        return $indexed;
    }

    /**
     * Resolve a bounded, authoritative neighborhood using only current
     * placement, pathway and prerequisite references already present in
     * Curriculum lifecycle data.
     *
     * @param  list<array<string, mixed>>  $placements
     * @param  array<string, array<string, mixed>>  $catalogById
     * @return list<string>
     */
    private function boundedWorldMemberIds(string $activeUnitId, array $placements, array $catalogById): array
    {
        $members = [$activeUnitId => true];
        $activeCapabilities = [];
        $activePathways = [];

        foreach ($placements as $placement) {
            if ($placement['knowledge_unit_id'] !== $activeUnitId) {
                continue;
            }
            $activeCapabilities[$placement['capability_id']] = true;
            $pathway = $placement['lifecycle']['pathway'] ?? null;
            if (is_array($pathway) && is_string($pathway['id'] ?? null) && $pathway['id'] !== '') {
                $activePathways[$pathway['id']] = true;
            }
            foreach ($this->prerequisiteIds($placement['lifecycle']) as $prerequisiteId) {
                if (isset($catalogById[$prerequisiteId])) {
                    $members[$prerequisiteId] = true;
                }
            }
        }

        foreach ($placements as $placement) {
            $lifecycle = $placement['lifecycle'];
            $pathway = $lifecycle['pathway'] ?? null;
            $pathwayId = is_array($pathway) && is_string($pathway['id'] ?? null)
                ? $pathway['id']
                : null;
            $isDependent = in_array($activeUnitId, $this->prerequisiteIds($lifecycle), true);
            if (isset($activeCapabilities[$placement['capability_id']])
                || ($pathwayId !== null && isset($activePathways[$pathwayId]))
                || $isDependent) {
                $members[$placement['knowledge_unit_id']] = true;
            }
        }

        return array_values(array_filter(
            array_keys($members),
            static fn (string $id): bool => isset($catalogById[$id]),
        ));
    }

    /**
     * @param  list<string>  $memberIds
     * @param  list<array<string, mixed>>  $placements
     * @param  array<string, array<string, mixed>>  $catalogById
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function typedGraph(array $memberIds, array $placements, array $catalogById): array
    {
        $memberSet = array_fill_keys($memberIds, true);
        $nodes = [];
        $edges = [];

        foreach ($memberIds as $memberId) {
            $item = $catalogById[$memberId];
            $label = $this->firstString($item, ['title_ar', 'title_en']) ?? $memberId;
            $nodes['ku:'.$memberId] = [
                'id' => 'ku:'.$memberId,
                'kind' => 'knowledge_unit',
                'label' => $label,
                'technical_label' => $memberId,
                'label_source' => $label === $memberId ? 'technical_fallback' : 'canonical',
                'provenance' => 'knowledge_unit',
            ];
        }

        foreach ($placements as $placement) {
            $unitId = $placement['knowledge_unit_id'];
            if (! isset($memberSet[$unitId])) {
                continue;
            }
            $lifecycle = $placement['lifecycle'];
            $revision = $placement['revision'];
            $capabilityId = $placement['capability_id'];
            $capabilityNodeId = 'capability:'.$capabilityId;
            $capabilityLabel = $this->firstString($lifecycle, ['capability_title_ar', 'capability_title_en'])
                ?? $capabilityId;
            $nodes[$capabilityNodeId] = [
                'id' => $capabilityNodeId,
                'kind' => 'capability',
                'label' => $capabilityLabel,
                'technical_label' => $capabilityId,
                'label_source' => $capabilityLabel === $capabilityId ? 'technical_fallback' : 'canonical',
                'provenance' => 'curriculum_placement.capability_id',
            ];

            $parentNodeId = $capabilityNodeId;
            $domainId = is_string($lifecycle['domain_id'] ?? null) ? $lifecycle['domain_id'] : null;
            $clusterId = is_string($lifecycle['cluster_id'] ?? null) ? $lifecycle['cluster_id'] : null;
            if ($domainId !== null && $domainId !== '') {
                $domainNodeId = 'domain:'.$domainId;
                $nodes[$domainNodeId] = $this->technicalNode($domainNodeId, 'domain', $domainId);
                $parentNodeId = $domainNodeId;
            }
            if ($clusterId !== null && $clusterId !== '') {
                $clusterNodeId = 'cluster:'.$clusterId;
                $nodes[$clusterNodeId] = $this->technicalNode($clusterNodeId, 'capability_cluster', $clusterId);
                if ($parentNodeId !== $capabilityNodeId) {
                    $this->addContainmentEdge($edges, $parentNodeId, $clusterNodeId, $revision, $placement['id']);
                }
                $parentNodeId = $clusterNodeId;
            }
            if ($parentNodeId !== $capabilityNodeId) {
                $this->addContainmentEdge($edges, $parentNodeId, $capabilityNodeId, $revision, $placement['id']);
            }
            $this->addContainmentEdge($edges, $capabilityNodeId, 'ku:'.$unitId, $revision, $placement['id']);
        }

        foreach ($placements as $placement) {
            $unitId = $placement['knowledge_unit_id'];
            if (! isset($memberSet[$unitId])) {
                continue;
            }
            foreach ($this->prerequisiteIds($placement['lifecycle']) as $prerequisiteId) {
                if (! isset($memberSet[$prerequisiteId])) {
                    continue;
                }
                $id = $this->edgeId('prerequisite', 'ku:'.$prerequisiteId, 'ku:'.$unitId);
                $edges[$id] = [
                    'id' => $id,
                    'from' => 'ku:'.$prerequisiteId,
                    'to' => 'ku:'.$unitId,
                    'type' => 'prerequisite',
                    'semantic' => 'prerequisite',
                    'revision' => $placement['revision'],
                    'lifecycle' => ['state' => 'current'],
                    'supported_views' => ['Path', 'Graph', 'Canvas'],
                    'provenance' => 'curriculum_placement.lifecycle.prerequisite_ku_ids',
                ];
            }
        }

        return [array_values($nodes), array_values($edges)];
    }

    /**
     * @return array<string, mixed>
     */
    private function technicalNode(string $id, string $kind, string $technicalLabel): array
    {
        return [
            'id' => $id,
            'kind' => $kind,
            'label' => $technicalLabel,
            'technical_label' => $technicalLabel,
            'label_source' => 'technical_fallback',
            'provenance' => 'curriculum_placement.lifecycle',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $edges
     */
    private function addContainmentEdge(
        array &$edges,
        string $from,
        string $to,
        int $revision,
        string $placementId,
    ): void {
        $id = $this->edgeId('containment', $from, $to);
        $edges[$id] = [
            'id' => $id,
            'from' => $from,
            'to' => $to,
            'type' => 'contains',
            'semantic' => 'containment',
            'revision' => $revision,
            'lifecycle' => ['state' => 'current'],
            'supported_views' => ['Tree', 'Graph', 'Canvas'],
            'provenance' => 'curriculum_placement:'.$placementId,
        ];
    }

    private function edgeId(string $type, string $from, string $to): string
    {
        return 'relation:'.$type.':'.substr(hash('sha256', $from.'|'.$to), 0, 16);
    }

    /**
     * @param  list<array<string, mixed>>  $relationships
     * @return array<string, mixed>
     */
    private function prerequisiteSignals(array $relationships): array
    {
        $observations = [];
        foreach ($relationships as $edge) {
            if (($edge['semantic'] ?? null) !== 'prerequisite') {
                continue;
            }
            $observations[] = [
                'id' => 'observation:'.$edge['id'],
                'target' => ['kind' => 'edge', 'id' => $edge['id']],
                'state' => 'required_before',
                'label' => 'متطلب سابق مسجل',
                'supported_views' => ['Tree', 'Path', 'Graph', 'Canvas'],
                'provenance' => [
                    'source' => 'curriculum_placement.lifecycle.prerequisite_ku_ids',
                    'version' => 'placement-revision-'.$edge['revision'],
                ],
            ];
        }

        if ($observations === []) {
            return [];
        }

        return [
            'prerequisite' => [
                'source' => 'curriculum_placement.lifecycle.prerequisite_ku_ids',
                'supported_views' => ['Tree', 'Path', 'Graph', 'Canvas'],
                'observations' => $observations,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $lifecycle
     * @return list<string>
     */
    private function prerequisiteIds(array $lifecycle): array
    {
        $ids = $lifecycle['prerequisite_ku_ids'] ?? null;
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter($ids, 'is_string')));
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $keys
     */
    private function firstString(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (is_string($source[$key] ?? null) && $source[$key] !== '') {
                return $source[$key];
            }
        }

        return null;
    }
}
