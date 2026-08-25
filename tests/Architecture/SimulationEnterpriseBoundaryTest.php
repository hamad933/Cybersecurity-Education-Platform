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

        $this->assertSame(5, substr_count($routes, 'Route::get('));
        $this->assertStringNotContainsString("Route::get('/operations'", $routes);
        foreach (["Route::get('/'", "Route::get('/scenarios'", "Route::get('/labs'", "Route::get('/runs'", "Route::get('/results'"] as $required) {
            $this->assertStringContainsString($required, $routes);
        }
        $this->assertStringContainsString("Route::post('/runs/{run}/operations'", $routes);
        $this->assertStringContainsString("Route::post('/results/{result}/replay-compare'", $routes);
    }

    #[Test]
    public function scenario_contract_is_target_agnostic_and_runtime_state_types_remain_distinct(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_14_010300_create_simulation_enterprise_wave1_tables.php')) ?: '';
        $service = file_get_contents(app_path('Modules/Simulator/Application/SimulationEnterpriseService.php')) ?: '';
        $scenarioBlock = explode("Schema::create('simulation_lab_definitions'", explode("Schema::create('simulation_scenario_definitions'", $migration)[1] ?? '')[0] ?? '';

        $this->assertStringContainsString("jsonb('environment_contract')", $scenarioBlock);
        $this->assertStringNotContainsString("uuid('baseline_id')", $scenarioBlock);
        $this->assertStringNotContainsString("uuid('enterprise_id')", $scenarioBlock);
        $this->assertStringContainsString('Scenario Environment Contract cannot contain fixed execution-target identifiers.', $service);
        $this->assertStringContainsString("Schema::create('simulation_runtime_snapshots'", $migration);
        $this->assertStringContainsString("Schema::create('simulation_runtime_checkpoints'", $migration);
        $this->assertStringContainsString("'source_snapshot_id'", $migration);
        $this->assertStringContainsString("'checkpoints' => \$checkpoints", $service);
        $this->assertStringNotContainsString("DB::table('evidence_records')", $service);
    }
}
