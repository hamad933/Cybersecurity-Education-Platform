<?php

use App\Providers\AppServiceProvider;
use App\Modules\IdentityAccess\Providers\IdentityAccessServiceProvider;
use App\Modules\Platform\Providers\PlatformServiceProvider;

return [
    AppServiceProvider::class,
    PlatformServiceProvider::class,
    IdentityAccessServiceProvider::class,
];
