<?php

namespace App\Modules\Enterprise\Application;

/**
 * Read-only application boundary for Simulation consumers of Enterprise-owned state.
 *
 * Implementations remain owned by MOD-ENT and return model-free snapshots only.
 */
interface SimulationEnterpriseStateReader
{
    public function findForSimulation(
        string $enterpriseId,
        string $digitalTwinRevisionId,
        string $baselineId,
    ): ?SimulationEnterpriseState;

    public function findPublishedBaselineForSimulation(
        string $enterpriseId,
        string $baselineId,
    ): ?SimulationEnterpriseState;

    /** @return list<SimulationEnterpriseState> */
    public function listForSimulationWorkspace(): array;
}
