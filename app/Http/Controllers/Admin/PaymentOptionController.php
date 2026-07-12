<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use Illuminate\Http\Request;

class PaymentOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paymentOptions = PaymentOption::latest()->get();
        $channelsSummary = [
            'total_balance' => (float) $paymentOptions->sum(fn ($o) => (float) ($o->balance ?? 0)),
            'visible_balance' => (float) $paymentOptions->where('is_hidden', false)->sum(fn ($o) => (float) ($o->balance ?? 0)),
            'hidden_balance' => (float) $paymentOptions->where('is_hidden', true)->sum(fn ($o) => (float) ($o->balance ?? 0)),
            'count' => $paymentOptions->count(),
        ];

        return view('admin.payment-options.index', compact('paymentOptions', 'channelsSummary'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.payment-options.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,bank,cash',
            'name' => 'required|string|max:255',
        ]);

        $validated['opening_balance'] = 0;
        $validated['balance'] = 0;

        PaymentOption::create($validated);

        return redirect()->route('admin.payment-options.index')
            ->with('success', 'Payment option created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentOption $paymentOption)
    {
        return view('admin.payment-options.edit', compact('paymentOption'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentOption $paymentOption)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,bank,cash',
            'name' => 'required|string|max:255',
            'add_amount' => 'nullable|numeric|min:0',
            'shrink_amount' => 'nullable|numeric|min:0',
        ]);

        $addAmount = (float) ($validated['add_amount'] ?? 0);
        $shrinkAmount = (float) ($validated['shrink_amount'] ?? 0);
        unset($validated['add_amount']);
        unset($validated['shrink_amount']);

        if ($addAmount > 0 && $shrinkAmount > 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Use either Add amount or Shrink amount in one update.']);
        }

        if ($shrinkAmount > 0 && (float) ($paymentOption->balance ?? 0) < $shrinkAmount) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['shrink_amount' => 'Insufficient channel balance to shrink that amount.']);
        }

        $paymentOption->update($validated);

        if ($addAmount > 0) {
            $paymentOption->increment('balance', $addAmount);
        }
        if ($shrinkAmount > 0) {
            $paymentOption->decrement('balance', $shrinkAmount);
        }

        return redirect()->route('admin.payment-options.index')
            ->with('success', 'Payment option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentOption $paymentOption)
    {
        $paymentOption->delete();
        return redirect()->route('admin.payment-options.index')
            ->with('success', 'Payment option deleted successfully.');
    }

    /**
     * Toggle channel visibility (hide/show).
     */
    public function toggleVisibility(PaymentOption $paymentOption)
    {
        $paymentOption->update(['is_hidden' => ! $paymentOption->is_hidden]);
        $message = $paymentOption->is_hidden ? 'Channel hidden.' : 'Channel is now visible.';
        return redirect()->route('admin.payment-options.index')
            ->with('success', $message);
    }

    /**
     * Reduce (shrink) channel balance by a manual amount.
     */
    public function shrinkBalance(Request $request, PaymentOption $paymentOption)
    {
        $validated = $request->validate([
            'shrink_amount' => 'required|numeric|min:0.01',
        ]);

        $shrinkAmount = (float) $validated['shrink_amount'];
        $currentBalance = (float) ($paymentOption->balance ?? 0);

        if ($currentBalance < $shrinkAmount) {
            return redirect()->route('admin.payment-options.index')
                ->withErrors(['error' => 'Insufficient channel balance to shrink that amount.']);
        }

        $paymentOption->decrement('balance', $shrinkAmount);

        return redirect()->route('admin.payment-options.index')
            ->with('success', 'Channel balance reduced successfully.');
    }
}
