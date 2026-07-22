<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_owner_is_created_interactively_and_second_active_owner_is_rejected(): void
    {
        $this->artisan('owner:create')
            ->expectsQuestion('Display name', 'Local Owner')
            ->expectsQuestion('Email address', 'OWNER@example.test')
            ->expectsQuestion('Password (14+ characters, mixed case, number, symbol)', 'VeryStrong!Pass9')
            ->expectsQuestion('Confirm password', 'VeryStrong!Pass9')
            ->expectsOutputToContain('Owner created: Local Owner')
            ->assertSuccessful();

        $owner = OwnerAccount::query()->sole();
        $this->assertSame('owner@example.test', $owner->email);
        $this->assertTrue(Hash::check('VeryStrong!Pass9', $owner->password));
        $this->assertDatabaseHas('audit_records', ['action' => 'owner.created', 'outcome' => 'success']);

        $this->artisan('owner:create')
            ->expectsQuestion('Display name', 'Second Owner')
            ->expectsQuestion('Email address', 'second@example.test')
            ->expectsQuestion('Password (14+ characters, mixed case, number, symbol)', 'AnotherStrong!Pass9')
            ->expectsQuestion('Confirm password', 'AnotherStrong!Pass9')
            ->expectsOutputToContain('An active owner already exists.')
            ->assertFailed();
        $this->assertDatabaseCount('owner_accounts', 1);
    }

    public function test_login_page_guides_first_owner_and_registration_is_absent(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login')->where('ownerExists', false));
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_guest_is_redirected_and_correct_credentials_authenticate(): void
    {
        $owner = $this->owner();
        $this->get('/')->assertRedirect('/login');
        $this->post('/login', ['email' => 'owner@example.test', 'password' => 'VeryStrong!Pass9'])->assertRedirect('/');
        $this->assertAuthenticatedAs($owner);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
        $this->assertDatabaseHas('audit_records', ['action' => 'auth.login', 'outcome' => 'success']);
        $this->assertStringContainsString('session()->regenerate()', file_get_contents(app_path('Modules/IdentityAccess/Http/Controllers/AuthenticatedSessionController.php')));
    }

    public function test_wrong_credentials_are_safely_audited_and_rate_limited(): void
    {
        $this->owner();
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', ['email' => 'owner@example.test', 'password' => 'Wrong!Password9'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'owner@example.test', 'password' => 'Wrong!Password9'])->assertTooManyRequests();
        $record = AuditRecord::query()->where('outcome', 'failure')->firstOrFail();
        $this->assertSame(hash('sha256', 'owner@example.test'), $record->target_identifier);
        $this->assertStringNotContainsString('Wrong!Password9', $record->toJson());
        $this->assertStringNotContainsString('owner@example.test', $record->toJson());
    }

    public function test_logout_invalidates_the_session_and_protected_dashboard_rejects_guest(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
        $this->assertDatabaseHas('audit_records', ['action' => 'auth.logout', 'outcome' => 'success']);
        $source = file_get_contents(app_path('Modules/IdentityAccess/Http/Controllers/AuthenticatedSessionController.php'));
        $this->assertStringContainsString('session()->invalidate()', $source);
        $this->assertStringContainsString('session()->regenerateToken()', $source);
    }

    public function test_release_profile_has_no_authentication_bypass(): void
    {
        $this->assertFalse(config('platform.auth_bypass'));
        $this->assertSame(0, preg_match('/AUTH_BYPASS\s*=\s*true/i', file_get_contents(base_path('.env.example'))));
        $this->assertSame(0, preg_match('/bypass.*middleware/i', collect(glob(base_path('routes/*.php')))->map(fn ($file) => file_get_contents($file))->join("\n")));
    }

    private function owner(): OwnerAccount
    {
        return app(CreateOwner::class)->execute('Local Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
    }
}
