<?php

namespace Tests\Architecture;

use App\Modules\Enterprise\Application\EnterpriseDefinitionAuthoring;
use App\Modules\Enterprise\Application\EnterpriseDefinitionService;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use App\Modules\Simulator\Application\SimulationDefinitionService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

final class SimdefCorrectionBoundaryTest extends TestCase
{
    #[Test]
    public function enterprise_definition_authoring_is_owned_and_resolved_by_the_enterprise_module(): void
    {
        $contract = new ReflectionClass(EnterpriseDefinitionAuthoring::class);
        $implementation = new ReflectionClass(EnterpriseDefinitionService::class);

        $this->assertTrue($contract->isInterface());
        $this->assertSame('App\\Modules\\Enterprise\\Application', $contract->getNamespaceName());
        $this->assertSame('App\\Modules\\Enterprise\\Application', $implementation->getNamespaceName());
        $this->assertTrue($implementation->implementsInterface(EnterpriseDefinitionAuthoring::class));
        $this->assertInstanceOf(EnterpriseDefinitionService::class, $this->app->make(EnterpriseDefinitionAuthoring::class));

        $contractSource = $this->source(app_path('Modules/Enterprise/Application/EnterpriseDefinitionAuthoring.php'));
        $implementationSource = $this->source(app_path('Modules/Enterprise/Application/EnterpriseDefinitionService.php'));
        $this->assertStringNotContainsString('App\\Modules\\Simulator', $contractSource."\n".$implementationSource);
        $this->assertStringNotContainsString('simulation_lab_', $implementationSource);
    }

    #[Test]
    public function simulator_lab_definition_service_uses_the_enterprise_contract_without_raw_canonical_access(): void
    {
        $service = new ReflectionClass(SimulationDefinitionService::class);
        $constructor = $service->getConstructor();

        $this->assertNotNull($constructor);
        $parameter = $constructor->getParameters()[0] ?? null;
        $this->assertNotNull($parameter);
        $this->assertSame(SimulationEnterpriseStateReader::class, (string) $parameter->getType());

        $source = $this->source(app_path('Modules/Simulator/Application/SimulationDefinitionService.php'));
        foreach ([
            'simulation_enterprises',
            'simulation_enterprise_entities',
            'simulation_enterprise_relationships',
            'simulation_device_template_revisions',
            'simulation_digital_twins',
            'simulation_digital_twin_revisions',
            'simulation_baselines',
        ] as $enterpriseTable) {
            $this->assertStringNotContainsString($enterpriseTable, $source);
        }
        $this->assertStringContainsString('findPublishedBaselineForSimulation', $source);
        $this->assertStringContainsString('findPublishedDeviceTemplateRevisionForSimulation', $source);
    }

    #[Test]
    public function correction_schema_encodes_stable_identity_typed_ownership_lifecycle_and_immutable_children(): void
    {
        $migration = $this->source(database_path('migrations/2026_08_29_010000_cep_simdef_correction.php'));
        $migration2 = $this->source(database_path('migrations/2026_08_30_010000_simdef_mno_correction.php'));

        foreach ([
            "Schema::create('simulation_enterprise_entities'",
            "Schema::create('simulation_enterprise_relationships'",
            "Schema::create('simulation_device_templates'",
            "Schema::create('simulation_device_template_revisions'",
            "Schema::create('simulation_labs'",
            "Schema::create('simulation_digital_twin_components'",
            "Schema::create('simulation_digital_twin_relationships'",
            "Schema::create('simulation_lab_task_nodes'",
            "Schema::create('simulation_lab_task_dependencies'",
            'sim_twin_component_scope_check',
            'sim_lab_environment_binding_check',
            'sim_lab_lifecycle_check',
            'prevent_published_twin_child_mutation',
            'prevent_published_lab_child_mutation',
        ] as $required) {
            $this->assertStringContainsString($required, $migration);
        }
        
        foreach ([
            'sim_baseline_revision_owner_fk',
            'sim_twin_revision_parent_fk',
            'sim_twin_rel_ent_rel_pin_fk'
        ] as $required) {
            $this->assertStringContainsString($required, $migration2);
        }
    }

    #[Test]
    public function definition_routes_remain_bounded_posts_beneath_the_five_primary_read_routes(): void
    {
        $routes = $this->source(base_path('routes/workspaces/simulation-enterprise.php'));

        $this->assertSame(5, substr_count($routes, 'Route::get('));
        foreach ([
            "Route::post('/enterprise/{enterprise}/entities'",
            "Route::post('/enterprise/{enterprise}/relationships'",
            "Route::post('/device-template-revisions/{revision}/validate'",
            "Route::post('/digital-twin-revisions/{revision}/publish'",
            "Route::post('/digital-twin-revisions/{revision}/clone'",
            "Route::post('/digital-twin-revisions/{revision}/baselines'",
            "Route::post('/labs/drafts'",
            "Route::post('/labs/{lab}/tasks'",
            "Route::post('/labs/{lab}/task-dependencies'",
            "Route::post('/labs/{lab}/validate'",
            "Route::post('/labs/{lab}/publish'",
            "Route::post('/labs/{lab}/clone'",
        ] as $required) {
            $this->assertStringContainsString($required, $routes);
        }
    }

    #[Test]
    public function corrected_definition_sources_do_not_activate_external_execution_providers(): void
    {
        $source = implode("\n", [
            $this->source(app_path('Modules/Enterprise/Application/EnterpriseDefinitionService.php')),
            $this->source(app_path('Modules/Simulator/Application/SimulationDefinitionService.php')),
            $this->source(app_path('Modules/Simulator/Http/Controllers/SimulationEnterpriseController.php')),
            $this->source(base_path('routes/workspaces/simulation-enterprise.php')),
        ]);

        foreach (['DockerRuntime', 'Kubernetes', 'Vmware', 'RemoteRange', 'CloudExecutor', 'ProviderAdapterRegistry'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);
        if (! is_string($source)) {
            $this->fail("Source file could not be read: {$path}");
        }

        return $source;
    }
}
