<?php

namespace App\Modules\Platform\Health;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(FoundationHealth $health): Response
    {
        $checks = $health->summaryChecks();

        return Inertia::render('Dashboard', ['health' => ['database' => $checks['database'], 'queue' => $checks['queue'], 'storage' => $checks['storage'], 'migrations' => $checks['migrations']]]);
    }
}
