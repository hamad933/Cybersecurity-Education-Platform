<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

final class RunResultBoundaryTest extends TestCase
{
    public function test_run_result_capability_does_not_mutate_w04_evidence_or_mastery(): void
    {
        $capabilitySource = file_get_contents(app_path('Modules/Simulator/RunResult/RunResultCapability.php'));
        
        // Exact table check proving we do NOT touch W04 structures directly
        $this->assertStringNotContainsString('learning_mastery', $capabilitySource, 'RunResult capability must not map to W04 Mastery directly.');
        $this->assertStringNotContainsString('evidence_records', $capabilitySource, 'RunResult capability must not map to W04 Evidence exactly.');
        $this->assertStringNotContainsString('evidence_decisions', $capabilitySource, 'RunResult capability must not map to W04 Review exactly.');
        
        // F2: Prohibit DB table writes explicitly hitting handoffs securely replacing brittle regex searches safely
        $this->assertDoesNotMatchRegularExpression("/DB::table\(\s*['\"]simulation_candidate_evidence_handoffs['\"]\s*\)->(?:insert|update|delete|insertOrIgnore)/i", $capabilitySource, 'Cannot compete as a direct candidate evidence handoff writer.');
        
        // Ensure no canonical definitions are mutated by this bounded scope
        $this->assertStringNotContainsString("DB::table('simulation_run_results')->update", $capabilitySource);
        $this->assertStringNotContainsString("DB::table('simulation_runs')->update", $capabilitySource);
    }
}
