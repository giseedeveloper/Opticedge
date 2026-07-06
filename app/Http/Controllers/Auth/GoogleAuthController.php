<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use App\Support\TenantSuspension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(GoogleAuthService $googleAuth): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $user = $googleAuth->findOrCreateGuest($googleUser);

        $blockedReason = TenantSuspension::blocksLoginForUser($user);
        if ($blockedReason !== null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => $blockedReason]);
        }

        Auth::login($user, remember: true);

        return redirect()->to($this->postLoginRoute($user));
    }

    private function postLoginRoute($user): string
    {
        if ($user->role === 'guest') {
            return route('guest.dashboard', absolute: false);
        }

        if ($user->isSuperadmin()) {
            return route('superadmin.dashboard', absolute: false);
        }

        if (in_array($user->role, ['admin', 'subadmin'], true)) {
            return route('admin.dashboard', absolute: false);
        }

        if ($user->role === 'agent') {
            return route('agent.dashboard', absolute: false);
        }

        if ($user->role === 'teamleader') {
            return route('team-leader.dashboard', absolute: false);
        }

        if ($user->role === 'regional_manager') {
            return route('regional-manager.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }
}
