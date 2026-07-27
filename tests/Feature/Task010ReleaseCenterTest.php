<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class Task010ReleaseCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_release_center_is_owner_only_and_reports_manual_ai_policy(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $this->get('/release')->assertRedirect('/login');
        $this->actingAs($owner)->get('/release')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Release/Center')
            ->where('manualAiPolicy.execution', 'MANUAL_ONLY')
            ->where('manualAiPolicy.automatic_provider', false)
            ->has('readiness.checks.audit_chain')
            ->has('dailyQueue'));
    }

    public function test_security_headers_are_applied_to_release_surface(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $response = $this->actingAs($owner)->get('/release');
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Content-Security-Policy');
    }
}
