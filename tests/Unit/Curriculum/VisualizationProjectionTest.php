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
                'name' => 'خريطة Alpha',
                'scope' => ['kind' => 'knowledge_unit', 'id' => 'KU-001'],
                'world' => [
                    'recipe' => 'bounded_curriculum_neighborhood_v1',
                    'membership' => ['capability:CAP-001', 'ku:KU-001'],
                ],
                'default_view' => 'Graph',
                'visual_positions' => [
                    'capability:CAP-001' => ['x' => 120, 'y' => 40],
                    'unknown:node' => ['x' => 999, 'y' => 999],
                ],
            ],
            [
                'coverage' => [
                    'source' => 'coverage_read_model',
                    'supported_views' => ['Tree', 'Graph'],
                    'observations' => [[
                        'id' => 'coverage:CAP-001',
                        'target' => ['kind' => 'node', 'id' => 'capability:CAP-001'],
                        'state' => 'observed',
                        'label' => '0 مصادر مرصودة',
                        'supported_views' => ['Tree', 'Graph'],
                        'provenance' => ['source' => 'coverage_read_model', 'version' => '1'],
                    ]],
                ],
            ],
        );

        self::assertSame(['Tree', 'Path', 'Graph', 'Canvas'], $projection['view']['implemented']);
        self::assertSame('map:alpha', $projection['map']['id']);
        self::assertSame('Graph', $projection['map']['default_view']);
        self::assertSame(['capability:CAP-001', 'ku:KU-001'], $projection['map']['canonical_node_ids']);
        self::assertArrayNotHasKey('nodes', $projection['map']);
        self::assertArrayNotHasKey('relationships', $projection['map']);
        self::assertArrayNotHasKey('unknown:node', $projection['map']['visual_positions']);
        self::assertSame('canonical_placement', $projection['graph']['edges'][0]['type']);
        self::assertSame('canonical_curriculum_typed_projection', $projection['graph']['source']);

        self::assertSame(['coverage'], $projection['overlay']['available']);
        self::assertTrue($projection['overlay']['layers']['coverage']['available']);
        self::assertSame('capability:CAP-001', $projection['overlay']['layers']['coverage']['observations'][0]['target']['id']);
        self::assertFalse($projection['overlay']['layers']['mastery']['available']);
        self::assertSame([], $projection['overlay']['layers']['mastery']['observations']);
        self::assertSame('NO_AUTHORITY', $projection['overlay']['layers']['mastery']['reason']);
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
                'id' => 'placement:1',
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

    public function test_saved_map_identity_is_rejected_when_saved_world_does_not_match_projection(): void
    {
        $projection = (new VisualizationProjection)->project(
            'KU-001',
            [
                ['id' => 'capability:CAP-001'],
                ['id' => 'ku:KU-001'],
            ],
            [[
                'id' => 'placement:1',
                'from' => 'capability:CAP-001',
                'to' => 'ku:KU-001',
            ]],
            [
                'id' => 'map:foreign',
                'scope' => ['kind' => 'knowledge_unit', 'id' => 'KU-OTHER'],
                'world' => [
                    'recipe' => 'bounded_curriculum_neighborhood_v1',
                    'membership' => ['ku:KU-OTHER'],
                ],
            ],
        );

        self::assertFalse($projection['map']['saved']);
        self::assertNull($projection['map']['id']);
        self::assertSame('SAVED_MAP_REJECTED', $projection['map']['state']);
        self::assertSame('SAVED_WORLD_MISMATCH', $projection['map']['reason']);
    }
}
