<?php

namespace App\Services;

use App\Models\User;
use App\Support\PlatformAuthSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    /**
     * Resolve a Google user from a mobile ID token (Google Sign-In).
     */
    public function userFromIdToken(string $idToken): SocialiteUser
    {
        return Socialite::driver('google')->stateless()->userFromToken($idToken);
    }

    /**
     * Find or create a platform guest from Google profile data.
     */
    public function findOrCreateGuest(SocialiteUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $email = strtolower(trim((string) $googleUser->getEmail()));

        $existing = User::withoutGlobalScopes()
            ->where(function ($query) use ($googleId, $email) {
                $query->where('google_id', $googleId)
                    ->orWhere('email', $email);
            })
            ->first();

        if ($existing) {
            return $this->linkGoogleAccount($existing, $googleUser);
        }

        return User::withoutGlobalScopes()->create([
            'name' => trim((string) ($googleUser->getName() ?: Str::before($email, '@'))),
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(Str::password(32)),
            'role' => 'guest',
            'status' => 'active',
            'tenant_id' => null,
            'email_verified_at' => PlatformAuthSettings::requiresEmailVerificationOnLogin() ? null : now(),
        ]);
    }

    private function linkGoogleAccount(User $user, SocialiteUser $googleUser): User
    {
        $updates = [];

        if (empty($user->google_id)) {
            $updates['google_id'] = (string) $googleUser->getId();
        }

        if (empty($user->avatar) && $googleUser->getAvatar()) {
            $updates['avatar'] = $googleUser->getAvatar();
        }

        if (! $user->hasVerifiedEmail() && ! PlatformAuthSettings::requiresEmailVerificationOnLogin()) {
            $updates['email_verified_at'] = now();
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return $user->fresh();
    }

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function issueSanctumSession(User $user): array
    {
        if (($user->status ?? 'active') !== 'active') {
            throw new \RuntimeException('Your account is not active.');
        }

        $user->tokens()->where('name', 'optic-app')->delete();
        $token = $user->createToken('optic-app')->plainTextToken;

        $user->loadMissing('tenant:id,brand_name,slug');

        $payload = $user->only(['id', 'name', 'email', 'role', 'tenant_id', 'status', 'business_name', 'avatar']);
        if ($user->tenant) {
            $payload['brand_name'] = $user->tenant->brand_name;
            $slug = trim((string) ($user->tenant->slug ?? ''));
            if ($slug !== '') {
                $payload['tenant_slug'] = $slug;
            }
        }

        return [
            'token' => $token,
            'user' => $payload,
        ];
    }
}
