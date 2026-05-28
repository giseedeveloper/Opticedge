<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;

class AdminPassthroughApiController extends Controller
{
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
}
