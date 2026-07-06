<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlatformAuthSettings
{
    public const KEY_REQUIRE_EMAIL_VERIFICATION = 'require_email_verification_on_login';

    public static function requiresEmailVerificationOnLogin(): bool
    {
        $value = Setting::query()
            ->withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('key', self::KEY_REQUIRE_EMAIL_VERIFICATION)
            ->value('value');

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Block login when platform setting requires a verified email.
     *
     * @throws ValidationException
     */
    public static function ensureLoginAllowed(User $user, string $field = 'email'): void
    {
        if (! self::requiresEmailVerificationOnLogin() || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable) {
            // Mail may be misconfigured; still block login with a clear message.
        }

        throw ValidationException::withMessages([
            $field => ['Please verify your email address before signing in. A verification link has been sent to your inbox.'],
        ]);
    }
}
