<?php

namespace App\Http\Controllers\RegionalManager;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use App\Services\RegionalManagerDeviceReturnService;
use App\Services\TeamLeaderDeviceReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceReturnController extends Controller
{
    public function indexIncoming()
    {
        $rmId = (int) Auth::id();
        $returns = TeamLeaderDeviceReturn::query()
            ->with(['fromTeamLeader', 'toRegionalManager', 'items'])
            ->where('to_regional_manager_id', $rmId)
            ->latest()
            ->get();

        return view('regional-manager.return-requests-incoming', compact('returns'));
    }

    public function indexOutgoing()
    {
        $rmId = (int) Auth::id();
        $returns = RegionalManagerDeviceReturn::query()
            ->with(['fromRegionalManager', 'items'])
            ->where('from_regional_manager_id', $rmId)
            ->latest()
            ->get();

        return view('regional-manager.return-requests-outgoing', compact('returns'));
    }

    public function acceptIncoming(Request $request, TeamLeaderDeviceReturn $tlReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderDeviceReturnService::class)->acceptByRecipient(
                $tlReturn,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return accepted. Devices are back in your inventory.');
    }

    public function declineIncoming(Request $request, TeamLeaderDeviceReturn $tlReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderDeviceReturnService::class)->declineByRecipient(
                $tlReturn,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request declined.');
    }

    public function cancelOutgoing(RegionalManagerDeviceReturn $rmReturn)
    {
        try {
            app(RegionalManagerDeviceReturnService::class)->cancelByRegionalManager($rmReturn, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request cancelled.');
    }
}
