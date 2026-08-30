<?php

namespace Tests\Feature\KnowledgeLearning;

use App\Modules\Curriculum\Application\CurriculumKnowledgeService;
use Tests\TestCase;

class KnowledgeVisualizeCorrectionTest extends TestCase
{
    public function test_it_proves_vis_map_01_stub_and_delegation(): void
    {
        $service = new CurriculumKnowledgeService;

        $result = $service->visualization(null);

        $this->assertIsArray($result['map']);
        $this->assertFalse($result['map']['saved']);
        $this->assertEquals('NO_CANONICAL_SCOPE', $result['map']['state']);
        $this->assertEmpty($result['map']['canonical_node_ids']);
    }

    public function test_it_can_project_canonical_scope(): void
    {
        $service = new CurriculumKnowledgeService;

        $result = $service->visualization(['id' => 'abc', 'title_ar' => 'Test']);

        $this->assertIsArray($result['map']);
        $this->assertFalse($result['map']['saved']);
        $this->assertEquals('UNSAVED_PROJECTION', $result['map']['state']);
    }
}
