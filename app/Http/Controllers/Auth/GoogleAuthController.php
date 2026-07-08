<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use App\Support\PlatformAuthSettings;
use App\Support\TenantSuspension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const MOBILE_APP_SCHEME = 'app.opticedgesales.com';

    public function redirect(Request $request): RedirectResponse
    {
        if ($request->boolean('mobile')) {
            session(['google_oauth_mobile' => true]);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request, GoogleAuthService $googleAuth): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $user = $googleAuth->findOrCreateGuest($googleUser);

        $blockedReason = TenantSuspension::blocksLoginForUser($user);
        if ($blockedReason !== null) {
            if (session()->pull('google_oauth_mobile')) {
                return $this->mobileRedirectWithError($blockedReason);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => $blockedReason]);
        }

        try {
            PlatformAuthSettings::ensureLoginAllowed($user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Sign-in is not allowed.';

            if (session()->pull('google_oauth_mobile')) {
                return $this->mobileRedirectWithError($message);
            }

            return redirect()
                ->route('login')
                ->withErrors($e->errors());
        }

        if (session()->pull('google_oauth_mobile')) {
            $session = $googleAuth->issueSanctumSession($user);

            return redirect()->away(
                self::MOBILE_APP_SCHEME.'://google-auth?token='.urlencode($session['token'])
            );
        }

        Auth::login($user, remember: true);

        return redirect()->to($this->postLoginRoute($user));
    }

    private function mobileRedirectWithError(string $message): RedirectResponse
    {
        return redirect()->away(
            self::MOBILE_APP_SCHEME.'://google-auth?error='.urlencode($message)
        );
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
