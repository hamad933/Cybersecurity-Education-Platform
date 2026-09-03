<?php

namespace App\Modules\Curriculum\Application\Visualize;

use InvalidArgumentException;

final class VisualizationProjection
{
    /** @var list<string> */
    public const VIEWS = ['Tree', 'Path', 'Graph', 'Canvas'];

    /**
     * @param  list<array<string, mixed>>  $canonicalNodes
     * @param  list<array<string, mixed>>  $canonicalRelationships
     * @param  array<string, mixed>|null  $savedMap
     * @param  array<string, mixed>  $overlaySignals
     * @return array<string, mixed>
     */
    public function project(
        string $scopeId,
        array $canonicalNodes,
        array $canonicalRelationships,
        ?array $savedMap = null,
        array $overlaySignals = [],
        string $worldRecipe = 'bounded_curriculum_neighborhood_v1',
    ): array {
        $nodeIds = $this->canonicalNodeIds($canonicalNodes);
        $edgeIds = $this->canonicalEdgeIds($canonicalRelationships);
        $this->assertRelationshipsReferenceCanonicalNodes($canonicalRelationships, $nodeIds);
        $mapResolution = $this->resolveMap($savedMap, $scopeId, $nodeIds, $worldRecipe);

        return [
            'map' => [
                'saved' => $mapResolution['saved'],
                'id' => $mapResolution['id'],
                'name' => $mapResolution['name'],
                'state' => $mapResolution['state'],
                'state_label' => $mapResolution['state_label'],
                'reason' => $mapResolution['reason'],
                'scope' => [
                    'kind' => 'knowledge_unit',
                    'id' => $scopeId,
                ],
                'world' => [
                    'recipe' => $worldRecipe,
                    'membership' => $nodeIds,
                ],
                'default_view' => $mapResolution['default_view'],
                'canonical_node_ids' => $nodeIds,
                'visual_positions' => $this->filterVisualPositions($mapResolution['visual_positions'], $nodeIds),
            ],
            'view' => [
                'implemented' => self::VIEWS,
                'not_implemented' => [],
                'default' => $mapResolution['default_view'],
            ],
            'overlay' => (new OverlayProjector)->project($overlaySignals, $nodeIds, $edgeIds),
            'graph' => [
                'nodes' => $canonicalNodes,
                'edges' => $canonicalRelationships,
                'source' => 'canonical_curriculum_typed_projection',
                'recipe' => $worldRecipe,
            ],
        ];
    }

    /**
     * A saved identity is accepted only when its supplied world contract
     * resolves to this exact governed projection. This prevents stamping a
     * Map ID onto an unrelated current object.
     *
     * @param  array<string, mixed>|null  $savedMap
     * @param  list<string>  $nodeIds
     * @return array{saved: bool, id: string|null, name: string|null, state: string, state_label: string, reason: string|null, default_view: string, visual_positions: array<mixed>}
     */
    private function resolveMap(?array $savedMap, string $scopeId, array $nodeIds, string $worldRecipe): array
    {
        $mapId = $savedMap['id'] ?? null;
        if (! is_string($mapId) || $mapId === '') {
            return [
                'saved' => false,
                'id' => null,
                'name' => null,
                'state' => 'UNSAVED_PROJECTION',
                'state_label' => 'عرض مشتق غير محفوظ',
                'reason' => null,
                'default_view' => 'Tree',
                'visual_positions' => [],
            ];
        }

        $scope = $savedMap['scope'] ?? null;
        $world = $savedMap['world'] ?? null;
        $membership = is_array($world) ? ($world['membership'] ?? null) : null;
        $recipe = is_array($world) ? ($world['recipe'] ?? null) : null;
        $validScope = is_array($scope)
            && ($scope['kind'] ?? null) === 'knowledge_unit'
            && ($scope['id'] ?? null) === $scopeId;
        $validWorld = is_array($membership)
            && array_is_list($membership)
            && is_string($recipe)
            && $recipe === $worldRecipe
            && $this->sameMembership($membership, $nodeIds);

        if (! $validScope || ! $validWorld) {
            return [
                'saved' => false,
                'id' => null,
                'name' => null,
                'state' => 'SAVED_MAP_REJECTED',
                'state_label' => 'تعذّر استعادة الخريطة المحفوظة',
                'reason' => 'SAVED_WORLD_MISMATCH',
                'default_view' => 'Tree',
                'visual_positions' => [],
            ];
        }

        $defaultView = $savedMap['default_view'] ?? null;

        return [
            'saved' => true,
            'id' => $mapId,
            'name' => is_string($savedMap['name'] ?? null) ? $savedMap['name'] : null,
            'state' => 'SAVED',
            'state_label' => 'خريطة محفوظة',
            'reason' => null,
            'default_view' => is_string($defaultView) && in_array($defaultView, self::VIEWS, true)
                ? $defaultView
                : 'Tree',
            'visual_positions' => is_array($savedMap['visual_positions'] ?? null)
                ? $savedMap['visual_positions']
                : [],
        ];
    }

    /**
     * @param  list<mixed>  $candidate
     * @param  list<string>  $expected
     */
    private function sameMembership(array $candidate, array $expected): bool
    {
        if (array_filter($candidate, 'is_string') !== $candidate) {
            return false;
        }

        sort($candidate);
        sort($expected);

        return $candidate === $expected;
    }

    /**
     * Updates only representation state. Canonical containment and relationships are never accepted here.
     *
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    public function moveVisualNode(array $map, string $canonicalNodeId, float $x, float $y): array
    {
        $canonicalNodeIds = $map['canonical_node_ids'] ?? [];
        if (! is_array($canonicalNodeIds) || ! in_array($canonicalNodeId, $canonicalNodeIds, true)) {
            throw new InvalidArgumentException('Visual position can only reference a canonical node in the saved map scope.');
        }

        $positions = $map['visual_positions'] ?? [];
        if (! is_array($positions)) {
            $positions = [];
        }

        $positions[$canonicalNodeId] = ['x' => $x, 'y' => $y];
        $map['visual_positions'] = $positions;

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<string>
     */
    private function canonicalNodeIds(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            $id = $node['id'] ?? null;
            if (! is_string($id) || $id === '') {
                throw new InvalidArgumentException('Every visualization node must reference a canonical identifier.');
            }

            if (in_array($id, $ids, true)) {
                throw new InvalidArgumentException('Visualization node identifiers must be unique.');
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $relationships
     * @return list<string>
     */
    private function canonicalEdgeIds(array $relationships): array
    {
        $ids = [];

        foreach ($relationships as $relationship) {
            $id = $relationship['id'] ?? null;
            if (! is_string($id) || $id === '' || in_array($id, $ids, true)) {
                throw new InvalidArgumentException('Visualization relationship identifiers must be present and unique.');
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $relationships
     * @param  list<string>  $nodeIds
     */
    private function assertRelationshipsReferenceCanonicalNodes(array $relationships, array $nodeIds): void
    {
        foreach ($relationships as $relationship) {
            $from = $relationship['from'] ?? null;
            $to = $relationship['to'] ?? null;

            if (! is_string($from) || ! is_string($to)
                || ! in_array($from, $nodeIds, true)
                || ! in_array($to, $nodeIds, true)) {
                throw new InvalidArgumentException('Visualization relationships must reference canonical nodes in scope.');
            }
        }
    }

    /**
     * @param  array<mixed>  $positions
     * @param  list<string>  $nodeIds
     * @return array<string, array{x: float|int, y: float|int}>
     */
    private function filterVisualPositions(array $positions, array $nodeIds): array
    {
        $filtered = [];

        foreach ($nodeIds as $nodeId) {
            $position = $positions[$nodeId] ?? null;
            if (! is_array($position)) {
                continue;
            }

            $x = $position['x'] ?? null;
            $y = $position['y'] ?? null;
            if ((! is_int($x) && ! is_float($x)) || (! is_int($y) && ! is_float($y))) {
                continue;
            }

            $filtered[$nodeId] = ['x' => $x, 'y' => $y];
        }

        return $filtered;
    }
}
