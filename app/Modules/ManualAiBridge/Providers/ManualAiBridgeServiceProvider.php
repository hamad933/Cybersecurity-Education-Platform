<?php

namespace App\Modules\ManualAiBridge\Providers;

use Illuminate\Support\ServiceProvider;

final class ManualAiBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The bridge is intentionally local and manual; no provider client is registered.
    }
}
