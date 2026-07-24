<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\StockController as WebStockController;
use App\Http\Controllers\Api\Concerns\AdaptsWebAdminResponses;
use App\Http\Controllers\Controller;
use App\Models\AgentSale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgentSaleController extends Controller
{
    use AdaptsWebAdminResponses;

    /**
     * List agent sales for admin (dashboard or full list).
     * Optional query: limit (default 50, max 200).
     */
    public function index(Request $request)
    {
        $limit = min((int) $request->query('limit', 500), 2000);
        $sales = AgentSale::with(['product.category', 'agent.teamLeader', 'teamLeader', 'paymentOption'])
            ->latest('date')
            ->take($limit)
            ->get()
            ->map(function ($sale) {
                $sellerName = $sale->agent?->name
                    ?? $sale->teamLeader?->name
                    ?? ($sale->seller_name ?: 'Unknown');

                return [
                    'id' => $sale->id,
                    'agent_name' => $sellerName,
                    'seller_type' => $sale->agent_id ? 'agent' : ($sale->team_leader_id ? 'team_leader' : 'agent'),
                    'team_leader_name' => $sale->teamLeader?->name ?? $sale->agent?->teamLeader?->name,
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

        if (app(\App\Services\DefaultAgentCommissionService::class)->lineIsDisbursed('sale', (int) $sale->id)) {
            return response()->json([
                'message' => 'This commission has already been disbursed and cannot be edited.',
            ], 422);
        }

        $sale->commission_paid = (float) $validated['commission_paid'];
        $sale->save();

        return response()->json(['message' => 'Commission updated.']);
    }

    public function store(Request $request)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->storeAgentSale($request),
            201
        );
    }

    public function convertToCredit(Request $request, int $id)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->convertAgentSaleToCredit($request, $id)
        );
    }

    public function destroy(int $id)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->destroyAgentSale($id)
        );
    }

    public function invoice(int $id)
    {
        return app(WebStockController::class)->downloadAgentSaleInvoice($id);
    }
}
