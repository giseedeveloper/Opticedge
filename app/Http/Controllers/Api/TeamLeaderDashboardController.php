<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeamLeaderDashboardService;
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
        $data = $this->dashboardService->build(Auth::user());

        return response()->json(['data' => $data]);
    }
}
