<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentProductTransfer;
use App\Services\AgentProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductTransferController extends Controller
{
    public function index()
    {
        $agentId = (int) Auth::id();
        $transfers = AgentProductTransfer::query()
            ->with(['fromAgent', 'toAgent', 'items'])
            ->where(function ($q) use ($agentId) {
                $q->where('from_agent_id', $agentId)->orWhere('to_agent_id', $agentId);
            })
            ->latest()
            ->get();

        return view('agent.transfers-index', compact('transfers'));
    }

    public function cancel(AgentProductTransfer $transfer)
    {
        try {
            app(AgentProductTransferService::class)->cancelOwn($transfer, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer cancelled.');
    }

    public function accept(Request $request, AgentProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer accepted. Devices are now assigned to you.');
    }

    public function decline(Request $request, AgentProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->declineByRecipient(
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
