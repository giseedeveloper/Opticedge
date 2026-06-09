<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;

class PurchaseController extends Controller
{
    public function serializePurchase(Purchase $p): array
    {
        $qty = (int) ($p->quantity ?? 0);
        $unit = (float) ($p->unit_price ?? 0);
        $total = (float) ($p->total_amount ?? ($qty * $unit));
        $paid = (float) ($p->paid_amount ?? 0);
        $pending = max(0, $total - $paid);

        $productName = $p->product?->name ?? 'N/A';
        if ($p->relationLoaded('lines') && $p->lines->isNotEmpty()) {
            $names = $p->lines->map(fn ($l) => $l->product?->name)->filter()->unique()->values();
            if ($names->isNotEmpty()) {
                $productName = $names->implode(', ');
            }
        }

        return [
            'id' => $p->id,
            'name' => $p->name ?? 'Purchase #' . $p->id,
            // Legacy summary fields used by Stocks page.
            'limit' => $qty,
            'available' => (int) ($p->limit_remaining ?? 0),
            'available_status' => $p->limit_status ?? '–',
            'status' => $p->payment_status ?? '–',
            // Extended details aligned with website purchases table.
            'date' => $p->date,
            'branch_id' => $p->branch_id,
            'branch_name' => $p->branch?->name,
            'distributor_name' => $p->distributor_name,
            'product_name' => $productName,
            'product_category_name' => $p->product?->category?->name ?? null,
            'note' => $p->note ?? null,
            'lines' => ($p->relationLoaded('lines') && $p->lines->isNotEmpty())
                ? $p->lines->map(function ($line) {
                    return [
                        'product_id' => $line->product_id,
                        'model' => $line->product?->name,
                        'quantity' => (int) $line->quantity,
                        'unit_price' => (float) $line->unit_price,
                        'sell_price' => $line->sell_price !== null ? (float) $line->sell_price : null,
                        'limit_remaining' => (int) $line->limit_remaining,
                    ];
                })->values()->all()
                : null,
            'quantity' => $qty,
            'added' => (int) ($p->product_list_items_count ?? 0),
            'unit_price' => $unit,
            'total_amount' => $total,
            'paid_date' => $p->paid_date,
            'paid_amount' => $paid,
            'pending_amount' => $pending,
            'sell_price' => $p->sell_price !== null ? (float) $p->sell_price : null,
            'payment_status' => $p->payment_status ?? '–',
            'payment_option_id' => $p->payment_option_id,
            'payment_option_name' => $p->paymentOption?->name,
            'payment_receipt_image' => $p->payment_receipt_image,
            'payment_receipt_url' => $p->payment_receipt_image ? asset('storage/' . $p->payment_receipt_image) : null,
            'created_at' => $p->created_at?->toISOString(),
            'updated_at' => $p->updated_at?->toISOString(),
        ];
    }

    /**
     * List purchases for admin app.
     * Includes stock summary fields plus purchase information shown on website purchases page.
     */
    public function index()
    {
        $purchases = Purchase::stockPurchases()
            ->with(['product.category', 'lines.product.category', 'stock', 'branch'])
            ->withCount('productListItems')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($p) => $this->serializePurchase($p))
            ->values()
            ->all();

        return response()->json(['data' => $purchases]);
    }

    /**
     * One purchase details for mobile app.
     */
    public function show(int $id)
    {
        $purchase = Purchase::with([
            'product.category',
            'lines.product.category',
            'stock',
            'branch',
            'paymentOption',
            'payments.paymentOption',
        ])->findOrFail($id);

        $data = $this->serializePurchase($purchase);
        $data['payments'] = $purchase->payments
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

    /**
     * List product_list items for a purchase: model, category name, imei_number.
     */
    public function items(int $id)
    {
        $purchase = Purchase::findOrFail($id);
        $items = $purchase->productListItems()
            ->with('category:id,name')
            ->orderBy('model')
            ->orderBy('imei_number')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'model' => $item->model ?? '–',
                    'category' => $item->category?->name ?? '–',
                    'imei_number' => $item->imei_number ?? '–',
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

    /**
     * List purchases with limit_status = 'pending' and limit_remaining > 0 (for admin app Add Product dropdown).
     * stock_id can be null; returns purchase name and category/model from the purchase's product.
     */
    public function forAddProduct()
    {
        $purchases = Purchase::stockPurchases()->with(['product.category', 'lines.product.category', 'stock', 'branch'])
            ->where('limit_status', 'pending')
            ->where('limit_remaining', '>', 0)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($p) {
                $product = $p->product;
                $category = $product?->category;

                $models = [];
                if ($p->lines->isNotEmpty()) {
                    foreach ($p->lines as $line) {
                        $lp = $line->product;
                        if (! $lp || (int) $line->limit_remaining <= 0) {
                            continue;
                        }
                        $models[] = [
                            'product_id' => $lp->id,
                            'category_id' => $lp->category_id,
                            'category_name' => $lp->category?->name ?? '–',
                            'model' => $lp->name,
                            'limit_remaining' => (int) $line->limit_remaining,
                            'unit_price' => (float) $line->unit_price,
                            'sell_price' => $line->sell_price !== null ? (float) $line->sell_price : null,
                        ];
                    }
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name ?? 'Purchase #' . $p->id,
                    'stock_id' => $p->stock_id,
                    'stock_name' => $p->stock?->name,
                    'branch_id' => $p->branch_id,
                    'branch_name' => $p->branch?->name,
                    'category_id' => $product?->category_id,
                    'category_name' => $category?->name ?? '–',
                    'model' => $product?->name ?? '–',
                    'requires_product_id' => $p->lines->isNotEmpty(),
                    'models' => $models !== [] ? $models : null,
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $purchases]);
    }

    /**
     * Get all purchase images (gallery) for mobile app image selection.
     * Returns all product images from all purchases, grouped by purchase.
     */
    public function imagesGallery()
    {
        $purchases = Purchase::with(['product', 'lines.product'])
            ->get()
            ->flatMap(function ($purchase) {
                $rows = collect();

                $pushProduct = function ($product) use ($purchase, &$rows) {
                    if (! $product || empty($product->images)) {
                        return;
                    }
                    $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                    if (! is_array($images)) {
                        return;
                    }
                    foreach ($images as $imagePath) {
                        $rows->push([
                            'id' => $purchase->id . '_' . $product->id . '_' . md5((string) $imagePath),
                            'purchase_id' => $purchase->id,
                            'purchase_name' => $purchase->name ?? 'Purchase #' . $purchase->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'image_path' => $imagePath,
                            'image_url' => asset('storage/' . $imagePath),
                        ]);
                    }
                };

                $pushProduct($purchase->product);
                foreach ($purchase->lines as $line) {
                    $pushProduct($line->product);
                }

                return $rows;
            })
            ->values()
            ->all();

        return response()->json(['data' => $purchases]);
    }
}
