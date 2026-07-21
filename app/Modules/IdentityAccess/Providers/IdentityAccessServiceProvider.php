<?php

namespace App\Modules\IdentityAccess\Providers;

use App\Modules\IdentityAccess\Console\CreateOwnerCommand;
use Illuminate\Support\ServiceProvider;

class IdentityAccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateOwnerCommand::class]);
        }
    }
}
