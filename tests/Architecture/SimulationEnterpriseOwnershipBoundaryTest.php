<?php

namespace Tests\Architecture;

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
    public function enterprise_exposes_a_model_free_application_contract_for_simulator(): void
    {
        $contract = new ReflectionClass(SimulationEnterpriseStateReader::class);

        $this->assertTrue($contract->isInterface());
        $this->assertSame('App\\Modules\\Enterprise\\Application', $contract->getNamespaceName());

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
    }

    #[Test]
    public function boundary_state_transfers_snapshots_without_orm_objects(): void
    {
        $state = new SimulationEnterpriseState(
            enterprise: ['id' => 'ent-1', 'state' => 'active'],
            digitalTwinRevision: ['id' => 'twin-2', 'revision' => 2],
            baseline: ['id' => 'base-3', 'state' => 'sealed'],
        );

        $this->assertSame('ent-1', $state->enterprise['id']);
        $this->assertSame('twin-2', $state->digitalTwinRevision['id']);
        $this->assertSame('base-3', $state->baseline['id']);
    }
}
