<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * List all stocks with current quantity (from product_list count).
     */
    public function index()
    {
        $stocks = Stock::withCount(['productListItems as quantity_available' => function ($q) {
            $q->whereNull('sold_at');
        }])->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'name' => $stock->name,
                'stock_limit' => $stock->stock_limit,
                'quantity' => $stock->quantity_available ?? $stock->quantity,
                'under_limit' => ($stock->quantity_available ?? $stock->quantity) < $stock->stock_limit,
            ];
        });

        return response()->json(['data' => $stocks]);
    }

    /**
     * Create a new stock with name and limit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stock_limit' => 'required|integer|min:1',
        ]);

        $stock = Stock::create($validated);

        return response()->json([
            'message' => 'Stock created.',
            'data' => ['id' => $stock->id, 'name' => $stock->name, 'stock_limit' => $stock->stock_limit],
        ], 201);
    }

    /**
     * List stocks from purchases that have limit_status = pending (for app add-product).
     */
    public function stocksUnderLimit()
    {
        $stockIds = \App\Models\Purchase::stockPurchases()->where('limit_status', 'pending')
            ->where('limit_remaining', '>', 0)
            ->whereNotNull('stock_id')
            ->pluck('stock_id')
            ->unique()
            ->filter();

        $stocks = Stock::whereIn('id', $stockIds)
            ->withCount(['productListItems as quantity_available' => function ($q) {
                $q->whereNull('sold_at');
            }])
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'name' => $stock->name,
                    'stock_limit' => $stock->stock_limit,
                    'quantity' => $stock->quantity_available ?? $stock->quantity,
                ];
            });

        return response()->json(['data' => $stocks->values()->all()]);
    }

    /**
     * Models and categories for a selected stock (from product_list + purchases).
     * Used by app Add Product: category and model options come from the selected stock.
     */
    public function modelsForStock(int $id)
    {
        $stock = Stock::findOrFail($id);

        $fromList = \App\Models\ProductListItem::where('stock_id', $stock->id)
            ->with('category:id,name')
            ->select('model', 'category_id')
            ->distinct()
            ->get()
            ->map(function ($r) {
                return [
                    'model' => $r->model,
                    'category_id' => $r->category_id,
                    'category_name' => $r->category?->name,
                ];
            });

        $fromPurchases = Purchase::where('stock_id', $stock->id)
            ->with('product:id,category_id,name')
            ->get()
            ->map(function ($p) {
                if (!$p->product) {
                    return null;
                }
                $cat = \App\Models\Category::find($p->product->category_id);

                return [
                    'model' => $p->product->name,
                    'category_id' => $p->product->category_id,
                    'category_name' => $cat?->name,
                ];
            })
            ->filter()
            ->unique('model')
            ->values();

        $combined = $fromList->concat($fromPurchases)->unique('model')->values()->all();

        return response()->json(['data' => $combined]);
    }

    public function show(int $id)
    {
        $stock = Stock::withCount(['productListItems as quantity_available' => function ($q) {
            $q->whereNull('sold_at');
        }])->findOrFail($id);

        $purchases = Purchase::stockPurchases()
            ->where('stock_id', $stock->id)
            ->with(['product', 'branch'])
            ->latest('date')
            ->limit(50)
            ->get()
            ->map(fn ($p) => app(PurchaseController::class)->serializePurchase($p));

        $receipts = Purchase::stockPurchases()
            ->where('stock_id', $stock->id)
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->get()
            ->map(fn ($p) => [
                'purchase_id' => $p->id,
                'name' => $p->name ?? 'Purchase #'.$p->id,
                'date' => $p->date,
                'payment_receipt_url' => asset('storage/'.$p->payment_receipt_image),
            ]);

        return response()->json([
            'data' => [
                'id' => $stock->id,
                'name' => $stock->name,
                'stock_limit' => $stock->stock_limit,
                'quantity' => $stock->quantity_available ?? $stock->quantity,
                'under_limit' => ($stock->quantity_available ?? $stock->quantity) < $stock->stock_limit,
                'purchases' => $purchases,
                'receipts' => $receipts,
            ],
        ]);
    }
}
