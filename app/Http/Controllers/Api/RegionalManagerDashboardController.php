<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegionalManagerDashboardService;
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
        $data = $this->dashboardService->build(Auth::user());

        return response()->json(['data' => $data]);
    }
}
