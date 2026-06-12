<?php

namespace App\Http\Controllers\RegionalManager;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerProductTransfer;
use App\Services\RegionalManagerProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductTransferController extends Controller
{
    public function index()
    {
        $rmId = (int) Auth::id();
        $transfers = RegionalManagerProductTransfer::query()
            ->with(['createdByAdmin', 'toRegionalManager', 'items'])
            ->where('to_regional_manager_id', $rmId)
            ->latest()
            ->get();

        return view('regional-manager.transfers-index', compact('transfers'));
    }

    public function accept(Request $request, RegionalManagerProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer accepted. Devices are now in your inventory.');
    }

    public function decline(Request $request, RegionalManagerProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerProductTransferService::class)->declineByRecipient(
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
