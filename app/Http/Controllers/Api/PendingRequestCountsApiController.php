<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PortalPendingRequestCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingRequestCountsApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $counts = PortalPendingRequestCounts::forUser($request->user());

        return response()->json([
            'data' => $counts,
        ]);
    }
}
