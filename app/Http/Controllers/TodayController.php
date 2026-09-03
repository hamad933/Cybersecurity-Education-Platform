<?php

namespace App\Http\Controllers;

use App\Application\Today\Values\OrchestrationNode;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

        $orchestration = [
            'registeredDomainEntries' => $registeredDomainEntries,
            'expectedDomainEntries' => count(self::DOMAIN_ENTRY_PATHS),
            'continueSession' => OrchestrationNode::unavailable()->toArray(),
            'recommendation' => OrchestrationNode::unavailable()->toArray(),
            'attentionItems' => OrchestrationNode::unavailable()->toArray(),
            'recentContext' => OrchestrationNode::unavailable()->toArray(),
            'progressProjection' => OrchestrationNode::unavailable()->toArray(),
        ];

        try {
            $provider = app(\App\Application\Today\Ports\TodayOrchestrationProviderInterface::class);

            $orchestration['continueSession'] = $this->safeFetch(fn () => $provider->getContinueSession())->toArray();
            $orchestration['recommendation'] = $this->safeFetch(fn () => $provider->getRecommendation())->toArray();
            $orchestration['attentionItems'] = $this->safeFetch(fn () => $provider->getAttentionItems())->toArray();
            $orchestration['recentContext'] = $this->safeFetch(fn () => $provider->getRecentContext())->toArray();
            $orchestration['progressProjection'] = $this->safeFetch(fn () => $provider->getProgressProjection())->toArray();

        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // REPORT DEPENDENCY: TodayOrchestrationProviderInterface must be bound by a cross-domain layer outside writeScope.
            // All projections remain UNAVAILABLE as initialized
        }

        return Inertia::render('Today/Index', [
            'orchestration' => $orchestration,
        ]);
    }

    /**
     * @param callable $fetcher
     * @return OrchestrationNode
     */
    private function safeFetch(callable $fetcher): OrchestrationNode
    {
        try {
            return $fetcher();
        } catch (\Throwable $e) {
            $diagnosticId = 'ERR-' . strtoupper(Str::random(8));
            report($e); // Log the raw exception server-side

            return OrchestrationNode::error('تعذر معالجة هذه البيانات بسبب خطأ داخلي.', $diagnosticId);
        }
    }
}
