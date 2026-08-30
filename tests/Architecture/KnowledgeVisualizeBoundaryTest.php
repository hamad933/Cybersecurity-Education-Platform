<?php

namespace Tests\Architecture;

use Tests\TestCase;

class KnowledgeVisualizeBoundaryTest extends TestCase
{
    public function test_it_verifies_no_persistence_logic_leaked_into_visualize(): void
    {
        // V-05 STALE ARCHITECTURE PROHIBITION: Protect only current bounds from speculative persistence.
        // It does not permanently forbid *Map*.php models for future implementations.
        $serviceFile = file_get_contents(app_path('Modules/Curriculum/Application/CurriculumKnowledgeService.php'));
        
        $this->assertStringNotContainsString('Map::create', $serviceFile, 'VIS-MAP-01 must not introduce map creation inside curriculum read scope.');
        $this->assertStringNotContainsString('Map::update', $serviceFile, 'VIS-MAP-01 must not introduce map update inside curriculum read scope.');
    }
}
