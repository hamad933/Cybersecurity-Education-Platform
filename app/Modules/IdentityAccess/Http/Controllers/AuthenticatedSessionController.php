<?php

namespace App\Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', ['ownerExists' => OwnerAccount::query()->where('is_active', true)->exists()]);
    }

    public function store(Request $request, AuditWriter $audit): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['boolean']]);
        $email = mb_strtolower(trim($credentials['email']));
        $correlation = (string) Str::uuid7();
        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password'], 'is_active' => true], (bool) ($credentials['remember'] ?? false))) {
            $audit->append(['actor_identifier' => null, 'action' => 'auth.login', 'target_type' => 'owner_account', 'target_identifier' => hash('sha256', $email), 'correlation_id' => $correlation, 'outcome' => 'failure', 'safe_metadata' => []]);
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->append(['actor_identifier' => $request->user()->id, 'action' => 'auth.login', 'target_type' => 'owner_account', 'target_identifier' => $request->user()->id, 'correlation_id' => $correlation, 'outcome' => 'success', 'safe_metadata' => []]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditWriter $audit): RedirectResponse
    {
        $ownerId = $request->user()?->id;
        if ($ownerId) {
            $audit->append(['actor_identifier' => $ownerId, 'action' => 'auth.logout', 'target_type' => 'owner_account', 'target_identifier' => $ownerId, 'correlation_id' => (string) Str::uuid7(), 'outcome' => 'success', 'safe_metadata' => []]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
