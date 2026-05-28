<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $user->tokens()->where('name', 'optic-app')->delete();
        $token = $user->createToken('optic-app')->plainTextToken;

        $user->loadMissing('tenant:id,brand_name');

        $payload = $user->only(['id', 'name', 'email', 'role', 'tenant_id']);
        if ($user->tenant) {
            $payload['brand_name'] = $user->tenant->brand_name;
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'agent',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Agent registration submitted. An administrator must activate your account before you can sign in.',
        ], 201);
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

        \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'],
            'role' => 'dealer',
            'status' => 'pending',
        ]);

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
}
