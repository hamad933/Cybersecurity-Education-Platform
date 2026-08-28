<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    /**
     * @var list<string>
     */
    private const DOMAIN_ENTRY_PATHS = [
        '/knowledge',
        '/simulation',
        '/progress',
        '/system',
    ];

    public function __invoke(): Response
    {
        $registeredGetUris = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (IlluminateRoute $route): bool => in_array('GET', $route->methods(), true))
            ->map(function (IlluminateRoute $route): string {
                $uri = trim($route->uri(), '/');

                return $uri === '' ? '/' : "/{$uri}";
            })
            ->unique();

        $registeredDomainEntries = collect(self::DOMAIN_ENTRY_PATHS)
            ->filter(fn (string $path): bool => $registeredGetUris->contains($path))
            ->count();

        try {
            $provider = app(\App\Application\Today\Ports\TodayOrchestrationProviderInterface::class);
            $orchestration = [
                'registeredDomainEntries' => $registeredDomainEntries,
                'expectedDomainEntries' => count(self::DOMAIN_ENTRY_PATHS),
                'continueSession' => $provider->getContinueSession(),
                'nextAction' => $provider->getNextAction(),
                'rationale' => $provider->getRationale(),
                'attentionItems' => $provider->getAttentionItems(),
                'recentContext' => $provider->getRecentContext(),
                'progressProjection' => $provider->getProgressProjection(),
            ];
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // REPORT DEPENDENCY: TodayOrchestrationProviderInterface must be bound by a cross-domain layer outside writeScope.
            $orchestration = [
                'registeredDomainEntries' => $registeredDomainEntries,
                'expectedDomainEntries' => count(self::DOMAIN_ENTRY_PATHS),
                'continueSession' => ['status' => 'UNAVAILABLE', 'data' => null],
                'nextAction' => ['status' => 'UNAVAILABLE', 'data' => null],
                'rationale' => ['status' => 'UNAVAILABLE', 'data' => null],
                'attentionItems' => ['status' => 'UNAVAILABLE', 'data' => []],
                'recentContext' => ['status' => 'UNAVAILABLE', 'data' => []],
                'progressProjection' => ['status' => 'UNAVAILABLE', 'data' => null],
            ];
        }

        return Inertia::render('Today/Index', [
            'orchestration' => $orchestration,
        ]);
    }
}
