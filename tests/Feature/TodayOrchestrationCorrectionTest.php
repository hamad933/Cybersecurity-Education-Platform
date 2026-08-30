<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Today\Ports\TodayOrchestrationProviderInterface;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TodayOrchestrationCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_route_renders_unavailable_state_when_no_provider_bound(): void
    {
        $owner = $this->owner();

        // Act: We access the / route as an authenticated owner
        $response = $this->actingAs($owner)->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'UNAVAILABLE')
                ->where('continueSession.data', null)
                ->where('nextAction.status', 'UNAVAILABLE')
                ->where('nextAction.data', null)
                ->where('rationale.status', 'UNAVAILABLE')
                ->where('rationale.data', null)
                ->where('attentionItems.status', 'UNAVAILABLE')
                ->where('attentionItems.data', [])
                ->where('recentContext.status', 'UNAVAILABLE')
                ->where('recentContext.data', [])
                ->where('progressProjection.status', 'UNAVAILABLE')
                ->where('progressProjection.data', null)
                ->etc()
            )
        );
    }

    public function test_today_route_renders_available_state_when_provider_bound(): void
    {
        $owner = $this->owner();

        // Arrange
        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): array { return ['status' => 'AVAILABLE', 'data' => ['id' => 'sess-1', 'title' => 'Session Title', 'href' => '/simulation/labs/1', 'domain' => 'simulation', 'domainLabel' => 'المحاكاة والمؤسسات', 'actionLabel' => 'استئناف', 'currentStep' => 'Step 1']]; }
            public function getNextAction(): array { return ['status' => 'AVAILABLE', 'data' => ['id' => 'act-1', 'title' => 'Next Action Title', 'description' => 'Desc', 'href' => '/knowledge/1', 'domain' => 'knowledge', 'domainLabel' => 'المعرفة والتعلّم']]; }
            public function getRationale(): array { return ['status' => 'AVAILABLE', 'data' => ['id' => 'rat-1', 'text' => 'Rationale Text', 'targetCompetency' => 'SEC-01', 'unlockedCapabilities' => ['Cap 1'], 'prerequisiteChain' => ['Pre 1']]]; }
            public function getAttentionItems(): array { return ['status' => 'AVAILABLE', 'data' => [['id' => 'att-1', 'title' => 'Attention 1', 'domain' => 'progress', 'domainLabel' => 'التقدم والأدلة', 'href' => '/progress/1', 'severity' => 'urgent', 'reason' => 'Reason 1']]]; }
            public function getRecentContext(): array { return ['status' => 'AVAILABLE', 'data' => [['id' => 'rec-1', 'title' => 'Recent 1', 'domain' => 'simulation', 'domainLabel' => 'المحاكاة والمؤسسات', 'href' => '/simulation/1', 'timestamp' => '2026-08-28 10:00', 'summary' => 'Summary 1']]]; }
            public function getProgressProjection(): array { return ['status' => 'AVAILABLE', 'data' => ['milestoneTitle' => 'Milestone 1', 'statusSummary' => 'Summary', 'verifiedCount' => 3, 'totalCount' => 5, 'evidenceRequirement' => 'Evidence Req 1']]; }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        // Act
        $response = $this->actingAs($owner)->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'AVAILABLE')
                ->where('continueSession.data.title', 'Session Title')
                ->where('nextAction.status', 'AVAILABLE')
                ->where('nextAction.data.title', 'Next Action Title')
                ->where('rationale.status', 'AVAILABLE')
                ->where('rationale.data.targetCompetency', 'SEC-01')
                ->where('attentionItems.status', 'AVAILABLE')
                ->where('attentionItems.data.0.title', 'Attention 1')
                ->where('recentContext.status', 'AVAILABLE')
                ->where('recentContext.data.0.title', 'Recent 1')
                ->where('progressProjection.status', 'AVAILABLE')
                ->where('progressProjection.data.verifiedCount', 3)
                ->etc()
            )
        );
    }

    public function test_today_route_renders_empty_state_when_provider_returns_empty(): void
    {
        $owner = $this->owner();

        // Arrange
        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): array { return ['status' => 'EMPTY', 'data' => null]; }
            public function getNextAction(): array { return ['status' => 'EMPTY', 'data' => null]; }
            public function getRationale(): array { return ['status' => 'EMPTY', 'data' => null]; }
            public function getAttentionItems(): array { return ['status' => 'EMPTY', 'data' => []]; }
            public function getRecentContext(): array { return ['status' => 'EMPTY', 'data' => []]; }
            public function getProgressProjection(): array { return ['status' => 'EMPTY', 'data' => null]; }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        // Act
        $response = $this->actingAs($owner)->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'EMPTY')
                ->where('continueSession.data', null)
                ->where('nextAction.status', 'EMPTY')
                ->where('nextAction.data', null)
                ->where('rationale.status', 'EMPTY')
                ->where('rationale.data', null)
                ->where('attentionItems.status', 'EMPTY')
                ->where('attentionItems.data', [])
                ->where('recentContext.status', 'EMPTY')
                ->where('recentContext.data', [])
                ->where('progressProjection.status', 'EMPTY')
                ->where('progressProjection.data', null)
                ->etc()
            )
        );
    }


    public function test_today_route_preserves_error_and_stale_states_without_mutation(): void
    {
        $owner = $this->owner();

        // Arrange
        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): array { return ['status' => 'ERROR', 'data' => null, 'message' => 'Provider error']; }
            public function getNextAction(): array { return ['status' => 'STALE', 'data' => ['id' => 'act-stale', 'title' => 'Stale Action', 'description' => 'Desc', 'href' => '/knowledge/1', 'domain' => 'knowledge', 'domainLabel' => 'المعرفة'], 'message' => 'Cache expired']; }
            public function getRationale(): array { return ['status' => 'ERROR', 'data' => null]; }
            public function getAttentionItems(): array { return ['status' => 'STALE', 'data' => []]; }
            public function getRecentContext(): array { return ['status' => 'STALE', 'data' => []]; }
            public function getProgressProjection(): array { return ['status' => 'ERROR', 'data' => null]; }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        // Act
        $response = $this->actingAs($owner)->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'ERROR')
                ->where('continueSession.message', 'Provider error')
                ->where('nextAction.status', 'STALE')
                ->where('nextAction.data.title', 'Stale Action')
                ->where('nextAction.message', 'Cache expired')
                ->where('rationale.status', 'ERROR')
                ->where('attentionItems.status', 'STALE')
                ->where('recentContext.status', 'STALE')
                ->where('progressProjection.status', 'ERROR')
                ->etc()
            )
        );
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute(
            'Local Owner',
            'owner@example.test',
            'VeryStrong!Pass9',
            (string) Str::uuid7(),
        );
    }
}
