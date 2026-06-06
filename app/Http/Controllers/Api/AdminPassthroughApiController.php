<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\StockController as WebStockController;
use App\Http\Controllers\Api\Concerns\AdaptsWebAdminResponses;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPassthroughApiController extends Controller
{
    use AdaptsWebAdminResponses;
    public function index(): JsonResponse
    {
        $serializer = app(PurchaseController::class);
        $purchases = Purchase::passthrough()
            ->with(['product.category', 'lines.product.category', 'stock', 'branch'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (Purchase $p) => $serializer->serializePurchase($p));

        return response()->json(['data' => $purchases]);
    }

    public function show(int $id): JsonResponse
    {
        $serializer = app(PurchaseController::class);
        $purchase = Purchase::passthrough()
            ->with(['product.category', 'lines.product.category', 'stock', 'branch', 'paymentOption'])
            ->findOrFail($id);

        return response()->json(['data' => $serializer->serializePurchase($purchase)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['_passthrough' => true]);

        return $this->adaptWebResponse(
            app(WebStockController::class)->storePurchase($request),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->updatePassthrough($request, $id)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->destroyPassthrough($id)
        );
    }
}
