<?php

namespace App\Modules\Enterprise\Providers;

use App\Modules\Enterprise\Application\DatabaseSimulationEnterpriseStateReader;
use App\Modules\Enterprise\Application\EnterpriseDefinitionAuthoring;
use App\Modules\Enterprise\Application\EnterpriseDefinitionService;
use App\Modules\Enterprise\Application\SimulationEnterpriseStateReader;
use Illuminate\Support\ServiceProvider;

final class EnterpriseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SimulationEnterpriseStateReader::class,
            DatabaseSimulationEnterpriseStateReader::class,
        );
        $this->app->bind(
            EnterpriseDefinitionAuthoring::class,
            EnterpriseDefinitionService::class,
        );
    }
}
