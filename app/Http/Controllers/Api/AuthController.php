<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use App\Support\TenantSuspension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login: email + password. Returns Sanctum token and user (id, name, email, role).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if (($user->status ?? 'active') !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        $blockedReason = TenantSuspension::blocksLoginForUser($user);
        if ($blockedReason !== null) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => [$blockedReason],
            ]);
        }

        $user->tokens()->where('name', 'optic-app')->delete();
        $token = $user->createToken('optic-app')->plainTextToken;

        $user->loadMissing('tenant:id,brand_name,slug');

        $payload = $user->only(['id', 'name', 'email', 'role', 'tenant_id', 'status', 'business_name']);
        if ($user->tenant) {
            $payload['brand_name'] = $user->tenant->brand_name;
            $slug = trim((string) ($user->tenant->slug ?? ''));
            if ($slug !== '') {
                $payload['tenant_slug'] = $slug;
            }
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ]);
    }

    /**
     * Google Sign-In (mobile/web): exchange Google ID token for Sanctum session.
     * Creates a guest account when the email is new.
     */
    public function loginWithGoogle(Request $request, GoogleAuthService $googleAuth)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $googleUser = $googleAuth->userFromIdToken($request->string('id_token')->toString());
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid or expired Google token.'],
            ]);
        }

        $user = $googleAuth->findOrCreateGuest($googleUser);

        $blockedReason = TenantSuspension::blocksLoginForUser($user);
        if ($blockedReason !== null) {
            throw ValidationException::withMessages([
                'id_token' => [$blockedReason],
            ]);
        }

        $session = $googleAuth->issueSanctumSession($user);

        return response()->json([
            'token' => $session['token'],
            'user' => $session['user'],
            'message' => $user->isGuest()
                ? 'Signed in. Waiting for a vendor administrator to assign your role.'
                : 'Signed in.',
        ]);
    }

    /**
     * Self-registration for customer role (mirrors web register when enabled).
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $token = $user->createToken('optic-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'message' => 'Account created.',
        ], 201);
    }

    public function registerAgent(Request $request)
    {
        return response()->json([
            'message' => 'Agent self-registration has moved to Google Sign-In. Use POST /api/auth/google with your Google ID token to register as a guest, then wait for a vendor admin to assign you.',
        ], 410);
    }

    public function registerDealer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
            'business_name' => 'required|string|max:255',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'],
            'role' => 'dealer',
            'status' => 'pending',
        ]);

        app(\App\Services\NotificationDispatchService::class)->registrationPending($user, 'dealer');

        return response()->json([
            'message' => 'Dealer registration submitted. You will be notified when approved.',
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                ])->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    /**
     * Revoke the current Sanctum token and end the mobile session.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user !== null) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
