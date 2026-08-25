<?php

namespace Tests\Architecture;

use App\Modules\Enterprise\Application\DatabaseSimulationEnterpriseStateReader;
use App\Modules\Enterprise\Application\SimulationEnterpriseState;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

final class SimulationEnterpriseOwnershipBoundaryTest extends TestCase
{
    #[Test]
    public function dependency_direction_remains_simulator_to_enterprise(): void
    {
        $this->assertSame(['MOD-ENT', 'MOD-CUR', 'MOD-PLT'], config('platform.modules.MOD-SIM.dependencies'));
        $this->assertSame(['MOD-PLT'], config('platform.modules.MOD-ENT.dependencies'));
    }

    #[Test]
    public function enterprise_exposes_a_model_free_read_only_application_contract_for_simulator(): void
    {
        $contract = new ReflectionClass(SimulationEnterpriseStateReader::class);

        $this->assertTrue($contract->isInterface());
        $this->assertSame('App\\Modules\\Enterprise\\Application', $contract->getNamespaceName());
        $this->assertTrue($contract->hasMethod('findPublishedBaselineForSimulation'));
        $this->assertTrue($contract->hasMethod('findPublishedBaselineTargetForSimulation'));
        $this->assertTrue($contract->hasMethod('listForSimulationWorkspace'));

        $method = $contract->getMethod('findForSimulation');
        $returnType = $method->getReturnType();

        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame(SimulationEnterpriseState::class, $returnType->getName());
        $this->assertTrue($returnType->allowsNull());

        $file = $contract->getFileName();
        if (! is_string($file)) {
            $this->fail('Simulation Enterprise application boundary source file was not found.');
        }

        $source = file_get_contents($file);
        if (! is_string($source)) {
            $this->fail('Simulation Enterprise application boundary source could not be read.');
        }

        $this->assertStringNotContainsString('App\\Modules\\Enterprise\\Models', $source);
        $this->assertStringNotContainsString('App\\Modules\\Simulator', $source);
        $this->assertStringNotContainsString('Illuminate\\Support\\Facades\\DB', $source);
        $this->assertStringNotContainsString('Illuminate\\Support\\Facades\\Schema', $source);
        $this->assertStringNotContainsString('createEnterprise', $source);
        $this->assertStringNotContainsString('publishDigitalTwinRevision', $source);
        $this->assertStringNotContainsString('publishBaseline', $source);
    }

    #[Test]
    public function container_resolves_the_reader_to_the_mod_ent_database_implementation(): void
    {
        $reader = $this->app->make(SimulationEnterpriseStateReader::class);

        $this->assertInstanceOf(DatabaseSimulationEnterpriseStateReader::class, $reader);

        $implementation = new ReflectionClass($reader);
        $this->assertSame('App\\Modules\\Enterprise\\Application', $implementation->getNamespaceName());
        $this->assertTrue($implementation->implementsInterface(SimulationEnterpriseStateReader::class));
    }

    #[Test]
    public function simulator_production_sources_contain_no_raw_enterprise_table_access(): void
    {
        $tables = [
            'simulation_enterprises',
            'simulation_digital_twins',
            'simulation_digital_twin_revisions',
            'simulation_baselines',
        ];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path('Modules/Simulator')),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $source = file_get_contents($path);
            if (! is_string($source)) {
                $this->fail("Simulator source could not be read: {$path}");
            }

            foreach ($tables as $table) {
                $this->assertStringNotContainsString(
                    $table,
                    $source,
                    "Simulator production source accesses MOD-ENT-owned table {$table}: {$path}",
                );
            }
        }
    }

    #[Test]
    public function boundary_state_transfers_snapshot_identities_and_digests_without_orm_objects(): void
    {
        $state = new SimulationEnterpriseState(
            enterprise: ['id' => 'ent-1', 'state' => 'active'],
            digitalTwin: ['id' => 'twin-1', 'provenance' => 'SIMULATED'],
            digitalTwinRevision: ['id' => 'revision-2', 'revision' => 2, 'digest' => 'twin-digest'],
            baseline: ['id' => 'base-3', 'state' => 'sealed', 'digest' => 'baseline-digest'],
        );

        $this->assertSame('ent-1', $state->enterprise['id']);
        $this->assertSame('twin-1', $state->digitalTwin['id']);
        $this->assertSame('SIMULATED', $state->digitalTwin['provenance']);
        $this->assertSame('revision-2', $state->digitalTwinRevision['id']);
        $this->assertSame('twin-digest', $state->digitalTwinRevision['digest']);
        $this->assertSame('base-3', $state->baseline['id']);
        $this->assertSame('baseline-digest', $state->baseline['digest']);
        $this->assertIsArray($state->enterprise);
        $this->assertIsArray($state->digitalTwin);
        $this->assertIsArray($state->digitalTwinRevision);
        $this->assertIsArray($state->baseline);
    }
}
