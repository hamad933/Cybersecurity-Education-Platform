<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;
use App\Application\Today\Ports\TodayOrchestrationProviderInterface;

class TodayOrchestrationCorrectionTest extends TestCase
{
    public function test_today_route_renders_unavailable_state_when_no_provider_bound(): void
    {
        // Act: We access the / route
        $response = $this->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->has('continueSession.status')
                ->where('continueSession.status', 'UNAVAILABLE')
                ->where('nextAction.status', 'UNAVAILABLE')
                ->where('progressProjection.status', 'UNAVAILABLE')
                ->etc()
            )
        );
    }

    public function test_today_route_renders_available_state_when_provider_bound(): void
    {
        // Arrange
        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): array { return ['status' => 'AVAILABLE', 'data' => ['title' => 'Session', 'href' => '#', 'domainLabel' => 'Domain', 'actionLabel' => 'Go', 'currentStep' => 'Step 1']]; }
            public function getNextAction(): array { return ['status' => 'AVAILABLE', 'data' => ['title' => 'Action', 'description' => 'Desc', 'href' => '#', 'domainLabel' => 'Domain']]; }
            public function getRationale(): array { return ['status' => 'AVAILABLE', 'data' => ['text' => 'Rationale', 'targetCompetency' => 'Comp', 'unlockedCapabilities' => [], 'prerequisiteChain' => []]]; }
            public function getAttentionItems(): array { return ['status' => 'AVAILABLE', 'data' => []]; }
            public function getRecentContext(): array { return ['status' => 'AVAILABLE', 'data' => []]; }
            public function getProgressProjection(): array { return ['status' => 'AVAILABLE', 'data' => ['milestoneTitle' => 'Milestone', 'statusSummary' => 'Summary', 'verifiedCount' => 1, 'totalCount' => 1]]; }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'AVAILABLE')
                ->where('continueSession.data.title', 'Session')
                ->etc()
            )
        );
    }
}
