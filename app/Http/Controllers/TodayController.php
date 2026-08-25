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

        return Inertia::render('Today/Index', [
            'orchestration' => [
                'registeredDomainEntries' => $registeredDomainEntries,
                'expectedDomainEntries' => count(self::DOMAIN_ENTRY_PATHS),
                'continueSession' => null,
                'nextAction' => null,
                'rationale' => null,
                'attentionItems' => [],
                'recentContext' => [],
                'progressProjection' => null,
            ],
        ]);
    }
}
