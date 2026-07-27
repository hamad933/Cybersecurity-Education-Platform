<?php

namespace App\Modules\Platform\Providers;

use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Blobs\LocalBlobStore;
use App\Modules\Platform\Console\BackupCreateCommand;
use App\Modules\Platform\Console\QueueSmokeCommand;
use App\Modules\Platform\Console\ReleaseCheckCommand;
use App\Modules\Platform\Console\RestoreApplyCommand;
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
            $this->commands([
                DiagnoseCommand::class,
                BackupCreateCommand::class,
                RestoreApplyCommand::class,
                ReleaseCheckCommand::class,
                QueueSmokeCommand::class,
            ]);
        }
    }
}
