<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $query->delete();

        return response()->json(['message' => 'Device token removed.']);
    }
}
