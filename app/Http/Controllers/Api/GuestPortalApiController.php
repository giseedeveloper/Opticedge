<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestPortalApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'avatar' => $user->avatar,
                'message' => 'Your account is registered. A vendor administrator will assign you as an agent, team leader, or regional manager.',
            ],
        ]);
    }
}
