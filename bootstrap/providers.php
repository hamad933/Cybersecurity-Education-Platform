<?php

use App\Modules\IdentityAccess\Providers\IdentityAccessServiceProvider;
use App\Modules\Platform\Providers\PlatformServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    PlatformServiceProvider::class,
    IdentityAccessServiceProvider::class,
];
