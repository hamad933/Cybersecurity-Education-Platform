<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TodayWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_owner_renders_today_with_real_orchestration_hierarchy(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Today/Index')
                ->where('orchestration.expectedDomainEntries', 4)
                ->has('orchestration.registeredDomainEntries')
                ->where('orchestration.continueSession', null)
                ->where('orchestration.nextAction', null)
                ->where('orchestration.rationale', null)
                ->where('orchestration.attentionItems', [])
                ->where('orchestration.recentContext', [])
                ->where('orchestration.progressProjection', null));
    }

    public function test_root_route_keeps_the_existing_authentication_compatibility_name(): void
    {
        $this->assertSame(url('/'), route('dashboard'));
    }

    public function test_mastery_is_not_gamified_or_rendered_as_percentage_in_payload(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)->get('/');
        $response->assertOk();

        $pageProps = $response->getOriginalContent()->getData()['page']['props'];
        $orchestration = $pageProps['orchestration'];

        $this->assertArrayNotHasKey('mastery_percent', $orchestration);
        $this->assertArrayNotHasKey('masteryPercent', $orchestration);
        $this->assertArrayNotHasKey('xp', $orchestration);
        $this->assertArrayNotHasKey('points', $orchestration);
        $this->assertArrayNotHasKey('gamification', $orchestration);
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
