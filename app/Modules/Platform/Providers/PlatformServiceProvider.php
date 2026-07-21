<?php

namespace App\Modules\Platform\Providers;

use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Blobs\LocalBlobStore;
use App\Modules\Platform\Health\DiagnoseCommand;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlobStore::class, LocalBlobStore::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DiagnoseCommand::class]);
        }
    }
}
