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
    ): array {
        $nodeIds = $this->canonicalNodeIds($canonicalNodes);
        $this->assertRelationshipsReferenceCanonicalNodes($canonicalRelationships, $nodeIds);

        $mapId = $savedMap['id'] ?? null;
        $isSaved = is_string($mapId) && $mapId !== '';
        $visualPositions = $savedMap['visual_positions'] ?? [];

        if (! is_array($visualPositions)) {
            $visualPositions = [];
        }

        return [
            'map' => [
                'saved' => $isSaved,
                'id' => $isSaved ? $mapId : null,
                'state' => $isSaved ? 'SAVED' : 'UNSAVED_PROJECTION',
                'scope' => [
                    'kind' => 'knowledge_unit',
                    'id' => $scopeId,
                ],
                'canonical_node_ids' => $nodeIds,
                'visual_positions' => $this->filterVisualPositions($visualPositions, $nodeIds),
            ],
            'view' => [
                'implemented' => self::VIEWS,
                'not_implemented' => [],
            ],
            'overlay' => (new OverlayProjector)->project($overlaySignals),
            'graph' => [
                'nodes' => $canonicalNodes,
                'edges' => $canonicalRelationships,
                'source' => 'canonical_curriculum_projection',
            ],
        ];
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

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
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
