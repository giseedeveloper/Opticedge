<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopRecord;
use Illuminate\Http\JsonResponse;

class AdminShopRecordsApiController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = ShopRecord::query()
            ->with('product:id,name')
            ->latest('date')
            ->limit(300)
            ->get()
            ->map(fn (ShopRecord $r) => [
                'id' => $r->id,
                'date' => optional($r->date)->toDateString(),
                'product_name' => $r->product?->name,
                'opening_stock' => (int) ($r->opening_stock ?? 0),
                'quantity_sold' => (int) ($r->quantity_sold ?? 0),
                'transfer_quantity' => (int) ($r->transfer_quantity ?? 0),
            ]);

        return response()->json(['data' => $rows]);
    }
}
