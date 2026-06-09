<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentTransferController extends Controller
{
    /**
     * Show the form for creating a payment transfer
     */
    public function create()
    {
        $channels = PaymentOption::orderBy('name')->get();
        return view('admin.payment-options.transfer', compact('channels'));
    }

    /**
     * Store a newly created payment transfer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_channel_id' => 'required|integer|exists:payment_options,id',
            'to_channel_id' => 'required|integer|exists:payment_options,id|different:from_channel_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Get the channels
        $fromChannel = PaymentOption::findOrFail($validated['from_channel_id']);
        $toChannel = PaymentOption::findOrFail($validated['to_channel_id']);

        // Validate sufficient balance
        if ($fromChannel->balance < $validated['amount']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient balance in ' . $fromChannel->name . '. Available: ' . number_format($fromChannel->balance, 2) . ' TZS');
        }

        try {
            DB::beginTransaction();

            // Create transfer record using Eloquent model
            PaymentTransfer::create([
                'from_channel_id' => $validated['from_channel_id'],
                'to_channel_id' => $validated['to_channel_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Update channel balances
            $fromChannel->decrement('balance', $validated['amount']);
            $toChannel->increment('balance', $validated['amount']);

            DB::commit();

            return redirect()->route('admin.payment-options.index')
                ->with('success', 'Transfer of ' . number_format($validated['amount'], 2) . ' TZS from ' . $fromChannel->name . ' to ' . $toChannel->name . ' completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing transfer: ' . $e->getMessage());
        }
    }

    /**
     * Show transfer history
     */
    public function history(Request $request)
    {
        $query = PaymentTransfer::with('fromChannel', 'toChannel', 'user')
            ->latest('created_at');

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transfers = $query->get();

        // Calculate statistics
        $totalTransferred = PaymentTransfer::query()
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->sum('amount');

        $totalCount = PaymentTransfer::query()
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->count();

        return view('admin.payment-options.transfer-history', compact('transfers', 'totalTransferred', 'totalCount'));
    }
}
