<?php

namespace Tests\Unit\Curriculum;

use App\Modules\Curriculum\Application\Visualize\VisualizationProjection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VisualizationProjectionTest extends TestCase
{
    public function test_projection_preserves_canonical_relationships_without_duplicating_them_into_map(): void
    {
        $projection = (new VisualizationProjection)->project(
            'KU-001',
            [
                ['id' => 'capability:CAP-001', 'kind' => 'capability', 'label' => 'CAP-001', 'technical_label' => 'CAP-001'],
                ['id' => 'ku:KU-001', 'kind' => 'knowledge_unit', 'label' => 'وحدة اختبار', 'technical_label' => 'KU-001'],
            ],
            [[
                'id' => 'placement:1',
                'from' => 'capability:CAP-001',
                'to' => 'ku:KU-001',
                'type' => 'canonical_placement',
                'revision' => 4,
                'lifecycle' => ['state' => 'active'],
            ]],
            [
                'id' => 'map:alpha',
                'visual_positions' => [
                    'capability:CAP-001' => ['x' => 120, 'y' => 40],
                    'unknown:node' => ['x' => 999, 'y' => 999],
                ],
            ],
            [
                'coverage' => ['capability:CAP-001' => 0],
            ],
        );

        self::assertSame(['Tree', 'Path', 'Graph', 'Canvas'], $projection['view']['implemented']);
        self::assertSame('map:alpha', $projection['map']['id']);
        self::assertSame(['capability:CAP-001', 'ku:KU-001'], $projection['map']['canonical_node_ids']);
        self::assertArrayNotHasKey('nodes', $projection['map']);
        self::assertArrayNotHasKey('relationships', $projection['map']);
        self::assertArrayNotHasKey('unknown:node', $projection['map']['visual_positions']);
        self::assertSame('canonical_placement', $projection['graph']['edges'][0]['type']);
        self::assertSame('canonical_curriculum_projection', $projection['graph']['source']);

        self::assertSame(['coverage'], $projection['overlay']['available']);
        self::assertTrue($projection['overlay']['layers']['coverage']['available']);
        self::assertSame(['capability:CAP-001' => 0], $projection['overlay']['layers']['coverage']['observations']);
        self::assertFalse($projection['overlay']['layers']['mastery']['available']);
        self::assertArrayNotHasKey('observations', $projection['overlay']['layers']['mastery']);
    }

    public function test_moving_canvas_node_changes_only_visual_position_state(): void
    {
        $projector = new VisualizationProjection;
        $projection = $projector->project(
            'KU-001',
            [
                ['id' => 'capability:CAP-001'],
                ['id' => 'ku:KU-001'],
            ],
            [[
                'from' => 'capability:CAP-001',
                'to' => 'ku:KU-001',
            ]],
        );

        $movedMap = $projector->moveVisualNode($projection['map'], 'capability:CAP-001', 44.5, 18.0);

        self::assertSame($projection['map']['canonical_node_ids'], $movedMap['canonical_node_ids']);
        self::assertSame(['x' => 44.5, 'y' => 18.0], $movedMap['visual_positions']['capability:CAP-001']);
        self::assertArrayNotHasKey('relationships', $movedMap);
        self::assertSame('capability:CAP-001', $projection['graph']['edges'][0]['from']);
        self::assertSame('ku:KU-001', $projection['graph']['edges'][0]['to']);
    }

    public function test_visual_position_cannot_reference_node_outside_canonical_scope(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new VisualizationProjection)->moveVisualNode(
            ['canonical_node_ids' => ['ku:KU-001'], 'visual_positions' => []],
            'capability:CAP-404',
            1,
            2,
        );
    }
}
