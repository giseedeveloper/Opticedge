<?php

namespace App\Http\Controllers\TeamLeader;

use App\Http\Controllers\Controller;
use App\Models\AgentDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use App\Services\AgentDeviceReturnService;
use App\Services\TeamLeaderDeviceReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceReturnController extends Controller
{
    public function indexIncoming()
    {
        $tlId = (int) Auth::id();
        $returns = AgentDeviceReturn::query()
            ->with(['fromAgent', 'toTeamLeader', 'items'])
            ->where('to_team_leader_id', $tlId)
            ->latest()
            ->get();

        return view('team-leader.return-requests-incoming', compact('returns'));
    }

    public function indexOutgoing()
    {
        $tlId = (int) Auth::id();
        $returns = TeamLeaderDeviceReturn::query()
            ->with(['fromTeamLeader', 'toRegionalManager', 'items'])
            ->where('from_team_leader_id', $tlId)
            ->latest()
            ->get();

        return view('team-leader.return-requests-outgoing', compact('returns'));
    }

    public function acceptIncoming(Request $request, AgentDeviceReturn $agentReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentDeviceReturnService::class)->acceptByRecipient(
                $agentReturn,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return accepted. Devices are back in your inventory.');
    }

    public function declineIncoming(Request $request, AgentDeviceReturn $agentReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentDeviceReturnService::class)->declineByRecipient(
                $agentReturn,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request declined.');
    }

    public function cancelOutgoing(TeamLeaderDeviceReturn $tlReturn)
    {
        try {
            app(TeamLeaderDeviceReturnService::class)->cancelByTeamLeader($tlReturn, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request cancelled.');
    }
}
