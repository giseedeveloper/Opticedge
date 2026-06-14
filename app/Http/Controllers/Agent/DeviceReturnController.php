<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentDeviceReturn;
use App\Services\AgentDeviceReturnService;
use Illuminate\Support\Facades\Auth;

class DeviceReturnController extends Controller
{
    public function index()
    {
        $agentId = (int) Auth::id();
        $returns = AgentDeviceReturn::query()
            ->with(['fromAgent', 'toTeamLeader', 'items'])
            ->where('from_agent_id', $agentId)
            ->latest()
            ->get();

        return view('agent.return-requests', compact('returns'));
    }

    public function cancel(AgentDeviceReturn $agentReturn)
    {
        try {
            app(AgentDeviceReturnService::class)->cancelByAgent($agentReturn, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request cancelled.');
    }
}
