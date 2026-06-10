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
        $sold = $item->sold_at !== null;

        $track = [
            'sold' => $sold,
            'sold_label' => $sold
                ? ($item->agent_sale_id || $item->agent_credit_id ? 'installed' : 'sold')
                : 'in_stock',
            'purchase_name' => $item->purchase?->name,
            'purchase_id' => $item->purchase_id,
            'distributor_name' => $item->purchase?->distributor_name,
            'assigned_agent_name' => $item->agentProductListAssignment?->agent?->name,
            'assigned_agent_email' => $item->agentProductListAssignment?->agent?->email,
        ];

        if ($sold && $item->agent_credit_id && $item->agentCredit) {
            $ac = $item->agentCredit;
            $track['sale_type'] = 'credit';
            $track['customer_name'] = $ac->customer_name;
            $track['customer_phone'] = $ac->customer_phone;
            $track['agent_name'] = $ac->agent?->name;
            $track['payment_status'] = $ac->payment_status;
            $track['paid_amount'] = $ac->paid_amount;
            $track['total_amount'] = $ac->total_amount;
            $track['payment_channel'] = $ac->paymentOption?->name;
        } elseif ($sold && $item->pending_sale_id && $item->pendingSale) {
            $ps = $item->pendingSale;
            $track['sale_type'] = 'pending';
            $track['customer_name'] = $ps->customer_name;
            $track['seller_name'] = $ps->seller_name;
            $track['selling_price'] = $ps->selling_price;
        } elseif ($sold && $item->agent_sale_id && $item->agentSale) {
            $as = $item->agentSale;
            $track['sale_type'] = 'agent_sale';
            $track['customer_name'] = $as->customer_name;
            $track['agent_name'] = $as->agent?->name;
            $track['total_selling_value'] = $as->total_selling_value;
        } elseif ($sold) {
            $track['sale_type'] = 'unknown';
        }

        return array_merge($base, [
            'model' => $item->model,
            'purchase_price' => $item->purchase_price,
            'sell_price' => $item->sell_price,
            'branch_id' => $item->branch_id,
            'agent_name' => $item->agentSale?->agent?->name
                ?? $item->agentProductListAssignment?->agent?->name,
            'agent_credit_id' => $item->agent_credit_id,
            'pending_sale_id' => $item->pending_sale_id,
            'agent_sale_id' => $item->agent_sale_id,
            'created_at' => $item->created_at?->toISOString(),
            'track' => $track,
        ]);
    }
}
