<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class ScenarioLabBoundaryTest extends TestCase
{
    #[Test]
    public function scenario_lab_namespace_does_not_create_runs_results_evidence_or_external_runtime_connectors(): void
    {
        $root = app_path('Modules/Simulator/ScenarioLab');
        $forbidden = [
            'SimulationEnterpriseService',
            'RunResult',
            'LabModuleInstance',
            'CandidateEvidence',
            'EvidenceRecord',
            'simulation_runs',
            'simulation_run_results',
            'simulation_candidate_evidence_handoffs',
            'Docker\\',
            'Kubernetes',
            'SSH',
            'WinRM',
        ];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo === false || $file->getExtension() !== 'php') {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $this->assertIsString($contents);
            foreach ($forbidden as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $contents,
                    "ScenarioLab boundary violation in {$file->getPathname()}: {$token}",
                );
            }
        }
    }
}
