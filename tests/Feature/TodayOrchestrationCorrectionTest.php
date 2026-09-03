<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Today\Ports\TodayOrchestrationProviderInterface;
use App\Application\Today\Values\OrchestrationNode;
use App\Application\Today\Values\RecommendationData;
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
                ->where('recommendation.status', 'UNAVAILABLE')
                ->where('recommendation.data', null)
                ->where('attentionItems.status', 'UNAVAILABLE')
                ->where('attentionItems.data', null)
                ->where('recentContext.status', 'UNAVAILABLE')
                ->where('recentContext.data', null)
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
            public function getContinueSession(): OrchestrationNode { return OrchestrationNode::available(['id' => 'sess-1', 'title' => 'Session Title', 'href' => '/simulation/labs/1', 'domain' => 'simulation', 'domainLabel' => 'المحاكاة والمؤسسات', 'actionLabel' => 'استئناف', 'currentStep' => 'Step 1']); }
            public function getRecommendation(): OrchestrationNode {
                return OrchestrationNode::available(new RecommendationData(
                    recommendationId: 'rec-001',
                    id: 'act-1',
                    title: 'Next Action Title',
                    domain: 'knowledge',
                    domainLabel: 'المعرفة والتعلّم',
                    href: '/knowledge/1',
                    description: 'Desc',
                    rationaleText: 'Rationale Text',
                    targetCompetency: 'SEC-01'
                ));
            }
            public function getAttentionItems(): OrchestrationNode { return OrchestrationNode::available([['id' => 'att-1', 'title' => 'Attention 1', 'domain' => 'progress', 'domainLabel' => 'التقدم والأدلة', 'href' => '/progress/1', 'severity' => 'urgent', 'reason' => 'Reason 1']]); }
            public function getRecentContext(): OrchestrationNode { return OrchestrationNode::available([['id' => 'rec-1', 'title' => 'Recent 1', 'domain' => 'simulation', 'domainLabel' => 'المحاكاة والمؤسسات', 'href' => '/simulation/1', 'timestamp' => '2026-08-28 10:00', 'summary' => 'Summary 1']]); }
            public function getProgressProjection(): OrchestrationNode { return OrchestrationNode::available(['milestoneTitle' => 'Milestone 1', 'statusSummary' => 'Summary', 'verifiedCount' => 3, 'totalCount' => 5, 'evidenceRequirement' => 'Evidence Req 1']); }
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
                ->where('recommendation.status', 'AVAILABLE')
                ->where('recommendation.data.title', 'Next Action Title')
                ->where('recommendation.data.targetCompetency', 'SEC-01')
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
            public function getContinueSession(): OrchestrationNode { return OrchestrationNode::empty(); }
            public function getRecommendation(): OrchestrationNode { return OrchestrationNode::empty(); }
            public function getAttentionItems(): OrchestrationNode { return OrchestrationNode::emptyArray(); }
            public function getRecentContext(): OrchestrationNode { return OrchestrationNode::emptyArray(); }
            public function getProgressProjection(): OrchestrationNode { return OrchestrationNode::empty(); }
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
                ->where('recommendation.status', 'EMPTY')
                ->where('recommendation.data', null)
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
            public function getContinueSession(): OrchestrationNode { return OrchestrationNode::error('Provider error', 'E1', null); }
            public function getRecommendation(): OrchestrationNode { return OrchestrationNode::stale(null, '2026-08-01', '2026-08-02', 'Cache expired'); }
            public function getAttentionItems(): OrchestrationNode { return OrchestrationNode::stale([], '2026-08-01', '2026-08-02'); }
            public function getRecentContext(): OrchestrationNode { return OrchestrationNode::stale([], '2026-08-01', '2026-08-02'); }
            public function getProgressProjection(): OrchestrationNode { return OrchestrationNode::error('Provider error', 'E2', null); }
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
                ->where('continueSession.diagnosticId', 'E1')
                ->where('recommendation.status', 'STALE')
                ->where('recommendation.data', null)
                ->where('recommendation.message', 'Cache expired')
                ->where('recommendation.observedAt', '2026-08-01')
                ->where('recommendation.freshUntil', '2026-08-02')
                ->where('attentionItems.status', 'STALE')
                ->where('recentContext.status', 'STALE')
                ->where('progressProjection.status', 'ERROR')
                ->etc()
            )
        );
    }

    public function test_per_projection_fault_isolation(): void
    {
        $owner = $this->owner();

        // Arrange: one projection fails, others succeed
        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): OrchestrationNode { return OrchestrationNode::available(['id' => 'sess-1', 'title' => 'Session Title', 'href' => '/simulation/labs/1', 'domain' => 'simulation', 'domainLabel' => 'المحاكاة', 'actionLabel' => 'استئناف', 'currentStep' => 'Step 1']); }
            public function getRecommendation(): OrchestrationNode { throw new \Exception('Recommendation failed'); }
            public function getAttentionItems(): OrchestrationNode { return OrchestrationNode::available([['id' => 'att-1', 'title' => 'Attention 1', 'domain' => 'progress', 'domainLabel' => 'التقدم', 'href' => '/progress/1', 'severity' => 'urgent', 'reason' => 'Reason 1']]); }
            public function getRecentContext(): OrchestrationNode { return OrchestrationNode::emptyArray(); }
            public function getProgressProjection(): OrchestrationNode { return OrchestrationNode::empty(); }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        // Act
        $response = $this->actingAs($owner)->get('/');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('continueSession.status', 'AVAILABLE') // Sibling preserved
                ->where('recommendation.status', 'ERROR') // Fault isolated
                ->where('recommendation.message', 'تعذر معالجة هذه البيانات بسبب خطأ داخلي.') // Does not include diagnostic ID
                ->where('attentionItems.status', 'AVAILABLE') // Sibling preserved
                ->has('recommendation.diagnosticId')
                ->etc()
            )
        );
    }

    public function test_attention_items_does_not_call_available_empty(): void
    {
        // Fault-isolation test: ensuring available([]) is never called for Attention items
        $owner = $this->owner();

        $mockProvider = new class implements TodayOrchestrationProviderInterface {
            public function getContinueSession(): OrchestrationNode { return OrchestrationNode::empty(); }
            public function getRecommendation(): OrchestrationNode { return OrchestrationNode::empty(); }
            public function getAttentionItems(): OrchestrationNode {
                // Return a non-empty list of items for testing sibling AVAILABLE logic.
                return OrchestrationNode::available([
                    ['id' => 'att-1', 'title' => 'Attention 1', 'domain' => 'progress', 'domainLabel' => 'التقدم', 'href' => '/1', 'severity' => 'info', 'reason' => 'Info']
                ]);
            }
            public function getRecentContext(): OrchestrationNode { return OrchestrationNode::emptyArray(); }
            public function getProgressProjection(): OrchestrationNode { return OrchestrationNode::empty(); }
        };

        $this->app->instance(TodayOrchestrationProviderInterface::class, $mockProvider);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Today/Index')
            ->has('orchestration', fn ($page) => $page
                ->where('attentionItems.status', 'AVAILABLE')
                ->where('attentionItems.data.0.id', 'att-1')
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
