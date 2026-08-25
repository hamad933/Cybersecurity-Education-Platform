<?php

namespace App\Providers;

use App\Modules\ManualAiBridge\Application\ManualAiStateReader;
use App\Modules\Platform\SystemOperations\Contracts\ManualAiStateProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ManualAiStateProvider::class,
            ManualAiStateReader::class
        );
        Date::use(CarbonImmutable::class);

        if ((bool) config('platform.force_https', false)) {
            URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
