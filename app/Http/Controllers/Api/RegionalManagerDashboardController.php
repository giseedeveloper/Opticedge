<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegionalManagerDashboardService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class RegionalManagerDashboardController extends Controller
{
    public function __construct(
        private readonly RegionalManagerDashboardService $dashboardService
    ) {}

    /**
     * Regional manager overview: team stats, IMEI rollups, agents (mirrors web dashboard).
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
