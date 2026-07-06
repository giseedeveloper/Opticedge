<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeamLeaderDashboardService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class TeamLeaderDashboardController extends Controller
{
    public function __construct(
        private readonly TeamLeaderDashboardService $dashboardService
    ) {}

    /**
     * Team leader overview: agents, quantity assignments, IMEI stats (mirrors web dashboard).
     */
    public function index()
    {
        $user = Auth::user();
        if ($user?->tenant_id !== null) {
            TenantContext::set((int) $user->tenant_id);
        }

        $data = $this->dashboardService->build($user);

        return response()->json(['data' => $data]);
    }
}
