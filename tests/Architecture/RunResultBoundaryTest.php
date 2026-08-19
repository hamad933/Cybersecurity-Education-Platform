<?php

namespace Tests\Architecture;

use App\Modules\Simulator\RunResult\RunResultVocabulary;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class RunResultBoundaryTest extends TestCase
{
    #[Test]
    public function run_result_namespace_contains_no_external_runtime_connector_path(): void
    {
        $source = $this->runResultSource();

        foreach (['DockerRuntime', 'Kubernetes', 'Vmware', 'Hypervisor', 'RemoteRange', 'CloudExecutor', 'ProviderAdapterRegistry', 'SiemConnector', 'ActiveDirectoryConnector', 'SshConnector', 'WinRmConnector'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
        $this->assertStringContainsString('INTERNAL_HIGH_FIDELITY_V1', $source);
    }

    #[Test]
    public function run_result_namespace_stops_at_candidate_evidence_handoff_boundary(): void
    {
        $source = $this->runResultSource();

        foreach (["DB::table('evidence_", 'EvidenceRecord', 'EvidenceReview', 'ReviewDecision', 'MasteryState'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
        $this->assertStringContainsString('simulation_candidate_evidence_handoffs', $source);
        $this->assertStringContainsString('RUN_RESULT_CANDIDATE_EVIDENCE_HANDOFF', $source);
    }

    #[Test]
    public function governed_run_and_result_vocabularies_remain_separate_and_exact(): void
    {
        $this->assertSame(['Standalone Lab Run', 'Scenario Run'], RunResultVocabulary::RUN_TYPES);
        $this->assertSame(['PREPARING', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED', 'STOPPED', 'FAILED'], RunResultVocabulary::LIFECYCLES);
        $this->assertSame(['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'], RunResultVocabulary::RESULT_OUTCOMES);
        $this->assertSame([], array_values(array_intersect(RunResultVocabulary::LIFECYCLES, RunResultVocabulary::RESULT_OUTCOMES)));
    }

    #[Test]
    public function result_revision_migration_is_additive_and_does_not_rewrite_the_frozen_result_table(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_19_020000_add_simulation_run_result_revisions.php')) ?: '';

        $this->assertStringContainsString("Schema::create('simulation_run_result_revisions'", $migration);
        $this->assertStringNotContainsString("Schema::table('simulation_run_results'", $migration);
        $this->assertStringNotContainsString("Schema::dropIfExists('simulation_run_results'", $migration);
        $this->assertStringContainsString('simulation_run_result_revisions_immutable', $migration);
    }

    private function runResultSource(): string
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Modules/Simulator/RunResult')));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = file_get_contents($file->getPathname()) ?: '';
            }
        }

        return implode("\n", $files);
    }
}
