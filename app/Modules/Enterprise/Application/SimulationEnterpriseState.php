<?php

namespace App\Modules\Enterprise\Application;

/**
 * Model-free state transferred across the Enterprise -> Simulator application boundary.
 *
 * The arrays intentionally remain application-level snapshots. ORM models and raw table
 * handles stay inside the owning Enterprise module.
 */
final readonly class SimulationEnterpriseState
{
    /**
     * @param  array<string, mixed>  $enterprise
     * @param  array<string, mixed>  $digitalTwinRevision
     * @param  array<string, mixed>  $baseline
     */
    public function __construct(
        public array $enterprise,
        public array $digitalTwinRevision,
        public array $baseline,
    ) {}
}
