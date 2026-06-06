<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\StockController as WebStockController;
use App\Http\Controllers\Api\Concerns\AdaptsWebAdminResponses;
use App\Http\Controllers\Controller;
use App\Models\DistributionSale;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PurchaseImeiRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributionSaleController extends Controller
{
    use AdaptsWebAdminResponses;

    private function serializeSale(DistributionSale $sale): array
    {
        $totalSelling = (float) ($sale->total_selling_value ?? 0);
        $paid = (float) ($sale->paid_amount ?? 0);
        $pending = max(0, $totalSelling - $paid);

        return [
            'id' => $sale->id,
            'dealer_name' => $sale->dealer?->name ?? $sale->dealer_name ?? '–',
            'seller_name' => $sale->seller_name ?? '–',
            'product_name' => $sale->product?->name ?? '–',
            'category_name' => $sale->product?->category?->name ?? '–',
            'quantity_sold' => (int) ($sale->quantity_sold ?? 0),
            'purchase_price' => (float) ($sale->purchase_price ?? 0),
            'selling_price' => (float) ($sale->selling_price ?? 0),
            'total_purchase_value' => (float) ($sale->total_purchase_value ?? 0),
            'total_selling_value' => $totalSelling,
            'paid_amount' => $paid,
            'pending_amount' => $pending,
            'balance' => (float) ($sale->balance ?? 0),
            'commission' => (float) ($sale->commission ?? 0),
            'profit' => (float) ($sale->profit ?? 0),
            'status' => $sale->status ?? 'pending',
            'payment_option_id' => $sale->payment_option_id,
            'payment_option_name' => $sale->paymentOption?->name,
            'date' => $sale->date?->format('Y-m-d'),
            'collection_date' => $sale->collection_date?->format('Y-m-d'),
            'created_at' => $sale->created_at?->toISOString(),
            'updated_at' => $sale->updated_at?->toISOString(),
        ];
    }

    public function index()
    {
        $sales = DistributionSale::with(['product:id,name', 'product.category:id,name', 'dealer:id,name', 'paymentOption:id,name'])
            ->latest('date')
            ->take(100)
            ->get()
            ->map(fn ($sale) => $this->serializeSale($sale));

        return response()->json(['data' => $sales]);
    }

    public function show(int $id)
    {
        $sale = DistributionSale::with([
            'product:id,name,category_id',
            'product.category:id,name',
            'dealer:id,name',
            'paymentOption:id,name',
            'payments.paymentOption:id,name',
        ])->findOrFail($id);

        $data = $this->serializeSale($sale);
        $data['payments'] = $sale->payments
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) ($payment->amount ?? 0),
                    'paid_date' => optional($payment->paid_date)->toDateString(),
                    'payment_option_id' => $payment->payment_option_id,
                    'payment_option_name' => $payment->paymentOption?->name,
                    'created_at' => $payment->created_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->storeDistribution($request),
            201
        );
    }

    public function update(\Illuminate\Http\Request $request, int $id)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->updateDistribution($request, $id)
        );
    }

    public function destroy(int $id)
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->destroyDistribution($id)
        );
    }

    public function invoice(int $id)
    {
        return app(WebStockController::class)->downloadDistributionInvoice($id);
    }

    public function formData(): JsonResponse
    {
        $dealers = User::query()
            ->where('role', 'dealer')
            ->orderBy('name')
            ->get(['id', 'name', 'business_name'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->business_name ?? $u->name,
            ]);

        $purchases = Purchase::stockPurchases()
            ->where(function ($q) {
                $q->whereNotNull('product_id')->orWhereHas('lines');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'name', 'date'])
            ->map(fn (Purchase $p) => [
                'id' => $p->id,
                'name' => $p->name ?? 'Purchase #'.$p->id,
                'date' => optional($p->date)->format('Y-m-d'),
            ]);

        return response()->json([
            'data' => [
                'dealers' => $dealers,
                'purchases' => $purchases,
            ],
        ]);
    }

    public function modelsForPurchase(int $purchaseId): JsonResponse
    {
        $purchase = Purchase::stockPurchases()->findOrFail($purchaseId);

        return app(WebStockController::class)->distributionModelsForPurchase($purchase);
    }

    public function assignableImeis(Request $request): JsonResponse
    {
        return app(WebStockController::class)->distributionAssignableImeis($request);
    }

    public function registerImeis(Request $request, PurchaseImeiRegistrationService $registrationService): JsonResponse
    {
        return app(WebStockController::class)->distributionRegisterImeis($request, $registrationService);
    }
}
