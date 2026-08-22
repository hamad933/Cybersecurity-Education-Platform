<?php

namespace App\Modules\Enterprise\Application;

/**
 * Public application boundary for simulation consumers of Enterprise-owned state.
 *
 * Implementations remain owned by MOD-ENT. The current integration baseline does not
 * bind or execute this reader because the W03 simulation_* tables are not integrated.
 */
interface SimulationEnterpriseStateReader
{
    public function findForSimulation(
        string $enterpriseId,
        string $digitalTwinRevisionId,
        string $baselineId,
    ): ?SimulationEnterpriseState;
}
