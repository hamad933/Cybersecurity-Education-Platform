<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SimulationEnterpriseBoundaryTest extends TestCase
{
    #[Test]
    public function wave_one_runtime_has_no_external_execution_connector_path(): void
    {
        $paths = [
            app_path('Modules/Simulator/Application/SimulationEnterpriseService.php'),
            app_path('Modules/Simulator/Http/Controllers/SimulationEnterpriseController.php'),
            base_path('routes/workspaces/simulation-enterprise.php'),
        ];
        $source = implode("\n", array_map(fn (string $path): string => file_get_contents($path) ?: '', $paths));

        foreach (['DockerRuntime', 'Kubernetes', 'Vmware', 'Hypervisor', 'RemoteRange', 'CloudExecutor', 'ProviderAdapterRegistry', 'SiemConnector', 'ActiveDirectoryConnector', 'SshConnector', 'WinRmConnector'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    #[Test]
    public function w03_does_not_import_or_write_canonical_evidence_or_mastery_stores(): void
    {
        $service = file_get_contents(app_path('Modules/Simulator/Application/SimulationEnterpriseService.php')) ?: '';
        $controller = file_get_contents(app_path('Modules/Simulator/Http/Controllers/SimulationEnterpriseController.php')) ?: '';
        $source = $service."\n".$controller;

        foreach (['EvidenceRecord', 'EvidenceReview', 'ReviewDecision', 'Mastery', "DB::table('evidence_records')"] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
        $this->assertStringContainsString('simulation_candidate_evidence_handoffs', $service);
    }

    #[Test]
    public function operations_is_not_a_sixth_primary_route(): void
    {
        $routes = file_get_contents(base_path('routes/workspaces/simulation-enterprise.php')) ?: '';

        $this->assertStringNotContainsString("Route::get('/operations'", $routes);
        foreach (["Route::get('/'", "Route::get('/scenarios'", "Route::get('/labs'", "Route::get('/runs'", "Route::get('/results'"] as $required) {
            $this->assertStringContainsString($required, $routes);
        }
    }
}
