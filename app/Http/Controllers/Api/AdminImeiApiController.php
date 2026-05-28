<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminImeiApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $normalized = $q === '' ? '' : preg_replace('/\s+/', '', $q);

        if ($normalized === '' || strlen($normalized) < 3) {
            return response()->json([
                'data' => [],
                'q' => $q,
                'message' => strlen($normalized) < 3 && $normalized !== ''
                    ? 'Enter at least 3 characters.'
                    : null,
            ]);
        }

        $like = '%'.addcslashes($normalized, '%_\\').'%';
        $results = ProductListItem::query()
            ->with(['stock:id,name', 'category:id,name', 'product:id,name'])
            ->where('imei_number', 'like', $like)
            ->orderBy('imei_number')
            ->limit(100)
            ->get()
            ->map(fn (ProductListItem $item) => $this->serializeItem($item));

        return response()->json(['data' => $results, 'q' => $q]);
    }

    public function show(ProductListItem $productListItem): JsonResponse
    {
        $item = $productListItem->load([
            'purchase.paymentOption',
            'stock',
            'category',
            'product',
            'agentProductListAssignment.agent',
            'agentCredit.agent',
            'agentCredit.paymentOption',
            'pendingSale',
            'agentSale.agent',
        ]);

        return response()->json(['data' => $this->serializeItemDetail($item)]);
    }

    private function serializeItem(ProductListItem $item): array
    {
        return [
            'id' => $item->id,
            'imei_number' => $item->imei_number,
            'stock_name' => $item->stock?->name,
            'category_name' => $item->category?->name,
            'product_name' => $item->product?->name,
            'sold_at' => $item->sold_at?->toISOString(),
            'status' => $item->sold_at ? 'sold' : 'available',
        ];
    }

    private function serializeItemDetail(ProductListItem $item): array
    {
        $base = $this->serializeItem($item);

        return array_merge($base, [
            'purchase_price' => $item->purchase_price,
            'sell_price' => $item->sell_price,
            'branch_id' => $item->branch_id,
            'agent_name' => $item->agentSale?->agent?->name
                ?? $item->agentProductListAssignment?->agent?->name,
            'agent_credit_id' => $item->agent_credit_id,
            'pending_sale_id' => $item->pending_sale_id,
            'agent_sale_id' => $item->agent_sale_id,
            'created_at' => $item->created_at?->toISOString(),
        ]);
    }
}
