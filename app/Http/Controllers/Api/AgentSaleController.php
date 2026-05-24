<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentSale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgentSaleController extends Controller
{
    /**
     * List agent sales for admin (dashboard or full list).
     * Optional query: limit (default 50, max 200).
     */
    public function index(Request $request)
    {
        $limit = min((int) $request->query('limit', 50), 200);
        $sales = AgentSale::with(['product.category', 'agent', 'paymentOption'])
            ->latest('date')
            ->take($limit)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'agent_name' => $sale->agent?->name ?? 'Unknown Agent',
                    'customer_name' => $sale->customer_name ?? '–',
                    'product_name' => $sale->product?->name ?? '–',
                    'category_name' => $sale->product?->category?->name ?? '–',
                    'quantity_sold' => (int) ($sale->quantity_sold ?? 0),
                    'purchase_price' => (float) ($sale->purchase_price ?? 0),
                    'selling_price' => (float) ($sale->selling_price ?? 0),
                    'total_purchase_value' => (float) ($sale->total_purchase_value ?? 0),
                    'total_selling_value' => (float) ($sale->total_selling_value ?? 0),
                    'profit' => (float) ($sale->profit ?? 0),
                    'commission_paid' => (float) ($sale->commission_paid ?? 0),
                    'payment_option_id' => $sale->payment_option_id,
                    'payment_option_name' => $sale->paymentOption?->name,
                    'date' => $sale->date ? (is_string($sale->date) ? Carbon::parse($sale->date)->toISOString() : $sale->date->toISOString()) : null,
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $sales]);
    }

    public function updateChannel(Request $request, int $id)
    {
        $validated = $request->validate([
            'payment_option_id' => 'required|exists:payment_options,id',
        ]);

        $sale = AgentSale::findOrFail($id);
        $sale->payment_option_id = (int) $validated['payment_option_id'];
        $sale->save();

        return response()->json(['message' => 'Payment channel updated.']);
    }

    public function updateCommission(Request $request, int $id)
    {
        $validated = $request->validate([
            'commission_paid' => 'required|numeric|min:0',
        ]);

        $sale = AgentSale::findOrFail($id);
        $sale->commission_paid = (float) $validated['commission_paid'];
        $sale->save();

        return response()->json(['message' => 'Commission updated.']);
    }
}
