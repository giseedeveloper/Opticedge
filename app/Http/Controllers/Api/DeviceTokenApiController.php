<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceTokenApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:android,ios,web',
        ]);

        $user = $request->user();
        $platform = $validated['platform'] ?? 'android';

        DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $validated['token'],
            ],
            [
                'platform' => $platform,
                'last_used_at' => now(),
            ],
        );

        Log::info('FCM device token registered', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'platform' => $platform,
            'token_prefix' => substr($validated['token'], 0, 12),
        ]);

        return response()->json(['message' => 'Device token registered.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'nullable|string|max:512',
        ]);

        $token = $validated['token'] ?? $request->query('token');

        $query = DeviceToken::query()->where('user_id', $request->user()->id);

        if (filled($token)) {
            $query->where('token', $token);
        }

        $deleted = $query->delete();

        Log::info('FCM device token removed', [
            'user_id' => $request->user()->id,
            'user_email' => $request->user()->email,
            'user_name' => $request->user()->name,
            'user_role' => $request->user()->role,
            'token_prefix' => filled($token) ? substr((string) $token, 0, 12) : null,
            'deleted_count' => $deleted,
        ]);

        return response()->json(['message' => 'Device token removed.']);
    }
}
