<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentTransfer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentOptionController extends Controller
{
    /**
     * List payment options (channels) for admin. Includes hidden for full admin view.
     */
    public function index()
    {
        $options = PaymentOption::orderBy('name')->get()->map(function ($opt) {
            return [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
                'opening_balance' => (float) $opt->opening_balance,
                'is_hidden' => (bool) $opt->is_hidden,
            ];
        });

        return response()->json(['data' => $options]);
    }

    /**
     * Visible channels only (for agents: down payments on credit sales).
     */
    public function indexVisible()
    {
        $options = PaymentOption::visible()->orderBy('name')->get()->map(function ($opt) {
            return [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
            ];
        });

        return response()->json(['data' => $options]);
    }

    /**
     * Agent sale configuration:
     *  - regular_channels: all visible non-Watu channels (for the Sell tab picker)
     *  - watu_channel: admin-configured default Watu channel (auto-used in the Watu tab)
     */
    public function agentSaleConfig()
    {
        $allVisible = PaymentOption::visible()->orderBy('name')->get();

        $regularChannels = $allVisible
            ->filter(fn ($opt) => ! $opt->isWatuAgentCreditChannel())
            ->map(fn ($opt) => ['id' => $opt->id, 'name' => $opt->name, 'type' => $opt->type])
            ->values();

        // Admin-configured Watu default
        $watuIdRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        $watuId = is_numeric($watuIdRaw) ? (int) $watuIdRaw : null;
        $watuChannel = $watuId ? $allVisible->firstWhere('id', $watuId) : null;

        // Fallback: first visible channel whose name contains "watu"
        if (! $watuChannel) {
            $watuChannel = $allVisible->first(fn ($opt) => $opt->isWatuAgentCreditChannel());
        }

        return response()->json([
            'data' => [
                'regular_channels' => $regularChannels,
                'watu_channel' => $watuChannel
                    ? ['id' => $watuChannel->id, 'name' => $watuChannel->name]
                    : null,
            ],
        ]);
    }

    public function show(int $id)
    {
        $opt = PaymentOption::findOrFail($id);
        return response()->json([
            'data' => [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
                'opening_balance' => (float) $opt->opening_balance,
                'is_hidden' => (bool) $opt->is_hidden,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mobile,bank,cash',
            'name' => 'required|string|max:255',
        ]);

        $validated['opening_balance'] = 0;
        $validated['balance'] = 0;
        $opt = PaymentOption::create($validated);

        return response()->json([
            'message' => 'Payment option created successfully.',
            'data' => [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
                'opening_balance' => (float) $opt->opening_balance,
                'is_hidden' => (bool) $opt->is_hidden,
            ],
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $opt = PaymentOption::findOrFail($id);
        $validated = $request->validate([
            'type' => 'required|in:mobile,bank,cash',
            'name' => 'required|string|max:255',
            'add_amount' => 'nullable|numeric|min:0',
        ]);
        $addAmount = (float) ($validated['add_amount'] ?? 0);
        unset($validated['add_amount']);
        $opt->update($validated);
        if ($addAmount > 0) {
            $opt->increment('balance', $addAmount);
        }
        $opt->refresh();
        return response()->json([
            'message' => 'Payment option updated successfully.',
            'data' => [
                'id' => $opt->id,
                'name' => $opt->name,
                'type' => $opt->type,
                'balance' => (float) $opt->balance,
                'opening_balance' => (float) $opt->opening_balance,
                'is_hidden' => (bool) $opt->is_hidden,
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $opt = PaymentOption::findOrFail($id);
        $opt->delete();
        return response()->json(['message' => 'Payment option deleted successfully.']);
    }

    public function toggleVisibility(int $id)
    {
        $opt = PaymentOption::findOrFail($id);
        $opt->update(['is_hidden' => ! $opt->is_hidden]);
        $opt->refresh();
        return response()->json([
            'message' => $opt->is_hidden ? 'Channel hidden.' : 'Channel is now visible.',
            'data' => [
                'id' => $opt->id,
                'is_hidden' => (bool) $opt->is_hidden,
            ],
        ]);
    }

    public function shrinkBalance(Request $request, int $id)
    {
        $opt = PaymentOption::findOrFail($id);
        $validated = $request->validate([
            'shrink_amount' => 'required|numeric|min:0.01',
        ]);

        $shrinkAmount = (float) $validated['shrink_amount'];
        $currentBalance = (float) ($opt->balance ?? 0);

        if ($currentBalance < $shrinkAmount) {
            return response()->json([
                'message' => 'Insufficient channel balance to shrink that amount.',
            ], 422);
        }

        $opt->decrement('balance', $shrinkAmount);
        $opt->refresh();

        return response()->json([
            'message' => 'Channel balance reduced successfully.',
            'data' => [
                'id' => $opt->id,
                'balance' => (float) $opt->balance,
            ],
        ]);
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_channel_id' => 'required|integer|exists:payment_options,id',
            'to_channel_id' => 'required|integer|exists:payment_options,id|different:from_channel_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $from = PaymentOption::findOrFail((int) $validated['from_channel_id']);
        $to = PaymentOption::findOrFail((int) $validated['to_channel_id']);
        $amount = (float) $validated['amount'];
        if ((float) $from->balance < $amount) {
            return response()->json([
                'message' => 'Insufficient balance in source channel.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $from, $to, $amount, $request) {
            PaymentTransfer::create([
                'from_channel_id' => $validated['from_channel_id'],
                'to_channel_id' => $validated['to_channel_id'],
                'amount' => $amount,
                'description' => $validated['description'] ?? null,
                'user_id' => $request->user()?->id,
            ]);
            $from->decrement('balance', $amount);
            $to->increment('balance', $amount);
        });

        return response()->json(['message' => 'Transfer completed successfully.'], 201);
    }

    public function transferHistory(Request $request)
    {
        $query = PaymentTransfer::with('fromChannel:id,name', 'toChannel:id,name', 'user:id,name')
            ->latest('created_at');
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }
        $page = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'data' => $page->getCollection()->map(function ($t) {
                return [
                    'id' => $t->id,
                    'from_channel' => $t->fromChannel?->name,
                    'to_channel' => $t->toChannel?->name,
                    'amount' => (float) $t->amount,
                    'description' => $t->description,
                    'user' => $t->user ? ['id' => $t->user->id, 'name' => $t->user->name] : null,
                    'created_at' => $t->created_at?->toIso8601String(),
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
