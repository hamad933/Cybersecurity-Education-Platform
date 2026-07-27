<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Learning\Application\DailyQueueService;
use App\Modules\Learning\Models\ReviewTrigger;
use App\Modules\Platform\Search\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SearchQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_is_local_and_daily_queue_explains_ranking(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        app(SearchService::class)->index([
            'document_type' => 'lesson',
            'document_identifier' => 'LESSON-SEARCH-1',
            'title_ar' => 'تحليل المصادقة',
            'title_en' => 'Authentication analysis',
            'body_ar' => 'تحقيق شذوذات تسجيل الدخول',
            'body_en' => 'Investigate logon anomalies',
            'facets' => ['knowledge_unit_id' => 'KU-D09-002'],
        ]);
        $this->assertNotEmpty(app(SearchService::class)->search('authentication'));

        ReviewTrigger::query()->create([
            'actor_id' => $owner->id,
            'knowledge_unit_id' => 'KU-D09-002',
            'case_id' => 'CASE-LOGIN-1',
            'failure_class' => 'MISSED_SCOPE',
            'source_reference' => 'attempt:1',
            'status' => 'open',
            'scheduled_at' => now()->subHour(),
        ]);
        $queue = app(DailyQueueService::class)->forActor((string) $owner->id);
        $this->assertSame('OPEN_FAILURE_REVIEW', $queue[0]['reason_code']);
        $this->assertNotSame('', $queue[0]['reason']);
        $this->assertGreaterThanOrEqual($queue[1]['score'] ?? 0, $queue[0]['score']);
    }
}
