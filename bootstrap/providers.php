<?php

use App\Modules\Enterprise\Providers\EnterpriseServiceProvider;
use App\Modules\IdentityAccess\Providers\IdentityAccessServiceProvider;
use App\Modules\ManualAiBridge\Providers\ManualAiBridgeServiceProvider;
use App\Modules\Platform\Providers\PlatformServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    PlatformServiceProvider::class,
    IdentityAccessServiceProvider::class,
    ManualAiBridgeServiceProvider::class,
    EnterpriseServiceProvider::class,
];
