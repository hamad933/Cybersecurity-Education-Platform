<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class ProgressEvidenceCorrectionBoundaryTest extends TestCase
{
    #[Test]
    public function simulator_has_no_w04_implementation_or_canonical_evidence_storage_dependency(): void
    {
        foreach ($this->phpFiles(app_path('Modules/Simulator')) as $file) {
            $source = file_get_contents($file);

            $this->assertStringNotContainsString(
                'App\\Modules\\Evidence',
                $source,
                "Simulator imports W04 Evidence implementation: {$file}",
            );
            $this->assertStringNotContainsString(
                'App\\Modules\\Learning',
                $source,
                "Simulator imports Learning implementation: {$file}",
            );
            $this->assertDoesNotMatchRegularExpression(
                "/DB::table\(['\"](?:evidence_candidates|governed_evidence|evidence_review_|evidence_mastery_)/",
                $source,
                "Simulator writes canonical W04 storage: {$file}",
            );
        }
    }

    #[Test]
    public function neutral_result_intake_port_uses_only_dto_primitives_and_the_w04_application_boundary(): void
    {
        $port = file_get_contents(app_path('Application/ProgressEvidenceBridge/ProgressEvidenceResultIntakePort.php'));
        $dto = file_get_contents(app_path('Application/ProgressEvidenceBridge/ResultEvidenceHandoff.php'));

        $this->assertStringContainsString('ProgressEvidenceService', $port);
        $this->assertStringNotContainsString('Modules\\Simulator', $port);
        $this->assertStringNotContainsString('Modules\\Simulator', $dto);
        $this->assertStringNotContainsString('Illuminate\\Support\\Facades\\DB', $port);
        $this->assertStringNotContainsString('simulation_candidate_evidence_handoffs', $port);
        $this->assertStringNotContainsString('simulation_run_results', $port);
    }

    #[Test]
    public function portfolio_backend_uses_one_registry_and_does_not_expose_unmapped_project_or_objective_groupings(): void
    {
        $registry = file_get_contents(app_path(
            'Modules/Evidence/Application/ProgressEvidence/MasteryPortfolio/PortfolioGroupingRegistry.php',
        ));
        $controller = file_get_contents(app_path('Modules/Evidence/Http/Controllers/ProgressEvidenceController.php'));
        $mastery = file_get_contents(app_path(
            'Modules/Evidence/Application/ProgressEvidence/MasteryPortfolio/MasteryPortfolioService.php',
        ));
        $progress = file_get_contents(app_path('Modules/Evidence/Application/ProgressEvidenceService.php'));

        $this->assertStringContainsString("'CAPABILITY' =>", $registry);
        $this->assertStringNotContainsString("'PROJECT' =>", $registry);
        $this->assertStringNotContainsString("'OBJECTIVE' =>", $registry);
        $this->assertStringContainsString('PortfolioGroupingRegistry $portfolioGroupings', $controller);
        $this->assertStringNotContainsString('PORTFOLIO_GROUPINGS', $mastery);
        $this->assertStringNotContainsString('PORTFOLIO_GROUPINGS', $progress);
        $this->assertStringNotContainsString('grouping.*PROJECT', $controller);
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        return array_values(array_filter(
            array_map(static fn ($file): string => $file->getPathname(), iterator_to_array($iterator)),
            static fn (string $file): bool => str_ends_with($file, '.php'),
        ));
    }
}
