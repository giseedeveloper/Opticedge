<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentSale;
use App\Models\PendingSale;
use App\Models\PaymentOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PendingSaleController extends Controller
{
    public function index()
    {
        $sales = PendingSale::with(['product:id,name', 'product.category:id,name', 'paymentOption:id,name'])
            ->latest('date')
            ->take(100)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'customer_name' => $sale->customer_name ?? '–',
                    'seller_name' => $sale->seller_name ?? '–',
                    'product_name' => $sale->product?->name ?? '–',
                    'category_name' => $sale->product?->category?->name ?? '–',
                    'quantity_sold' => (int) ($sale->quantity_sold ?? 0),
                    'total_selling_value' => (float) ($sale->total_selling_value ?? 0),
                    'profit' => (float) ($sale->profit ?? 0),
                    'payment_option_name' => $sale->paymentOption?->name,
                    'date' => $sale->date?->format('Y-m-d'),
                    'created_at' => $sale->created_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $sales]);
    }

    public function show(int $id)
    {
        $sale = PendingSale::with(['product.category', 'paymentOption'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name ?? '–',
                'seller_name' => $sale->seller_name ?? '–',
                'product_name' => $sale->product?->name ?? '–',
                'category_name' => $sale->product?->category?->name ?? '–',
                'quantity_sold' => (int) ($sale->quantity_sold ?? 0),
                'purchase_price' => (float) ($sale->purchase_price ?? 0),
                'selling_price' => (float) ($sale->selling_price ?? 0),
                'total_purchase_value' => (float) ($sale->total_purchase_value ?? 0),
                'total_selling_value' => (float) ($sale->total_selling_value ?? 0),
                'profit' => (float) ($sale->profit ?? 0),
                'payment_option_id' => $sale->payment_option_id,
                'payment_option_name' => $sale->paymentOption?->name,
                'date' => $sale->date?->format('Y-m-d'),
                'created_at' => $sale->created_at?->toISOString(),
            ],
        ]);
    }

    public function save(Request $request, int $id)
    {
        $validated = $request->validate([
            'payment_option_id' => 'required|exists:payment_options,id',
        ]);

        $pendingSale = PendingSale::findOrFail($id);
        $pendingSale->update($validated);

        $paymentOption = PaymentOption::find((int) $validated['payment_option_id']);
        if ($paymentOption) {
            $paymentOption->increment('balance', (float) ($pendingSale->total_selling_value ?? 0));
        }

        $agentSaleAttrs = [
            'customer_name' => $pendingSale->customer_name,
            'seller_name' => $pendingSale->seller_name,
            'product_id' => $pendingSale->product_id,
            'quantity_sold' => $pendingSale->quantity_sold,
            'purchase_price' => $pendingSale->purchase_price,
            'selling_price' => $pendingSale->selling_price,
            'total_purchase_value' => $pendingSale->total_purchase_value,
            'total_selling_value' => $pendingSale->total_selling_value,
            'profit' => $pendingSale->profit,
            'balance' => 0,
            'date' => $pendingSale->date,
        ];

        if (Schema::hasColumn('agent_sales', 'agent_id') && $pendingSale->seller_id) {
            $agentSaleAttrs['agent_id'] = $pendingSale->seller_id;
        }
        if (Schema::hasColumn('agent_sales', 'payment_option_id') && $pendingSale->payment_option_id) {
            $agentSaleAttrs['payment_option_id'] = $pendingSale->payment_option_id;
        }

        $qty = max(1, (int) ($pendingSale->quantity_sold ?? 1));
        $agentSaleAttrs = app(\App\Services\DefaultAgentCommissionService::class)
            ->applyToCreateAttrs($agentSaleAttrs, 'agent_sales', $qty);
        $agentSale = AgentSale::create($agentSaleAttrs);

        \App\Models\ProductListItem::where('pending_sale_id', $pendingSale->id)
            ->update([
                'agent_sale_id' => $agentSale->id,
                'pending_sale_id' => null,
            ]);

        $pendingSale->delete();

        return response()->json([
            'message' => 'Sale saved successfully.',
            'data' => [
                'agent_sale_id' => $agentSale->id,
            ],
        ]);
    }
}
