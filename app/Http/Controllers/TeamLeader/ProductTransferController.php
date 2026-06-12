<?php

namespace App\Http\Controllers\TeamLeader;

use App\Http\Controllers\Controller;
use App\Models\TeamLeaderProductTransfer;
use App\Services\TeamLeaderProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductTransferController extends Controller
{
    public function index()
    {
        $tlId = (int) Auth::id();
        $transfers = TeamLeaderProductTransfer::query()
            ->with(['fromRegionalManager', 'toTeamLeader', 'items'])
            ->where('to_team_leader_id', $tlId)
            ->latest()
            ->get();

        return view('team-leader.transfers-index', compact('transfers'));
    }

    public function accept(Request $request, TeamLeaderProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer accepted. Devices are now in your inventory.');
    }

    public function decline(Request $request, TeamLeaderProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(TeamLeaderProductTransferService::class)->declineByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer declined.');
    }
}
