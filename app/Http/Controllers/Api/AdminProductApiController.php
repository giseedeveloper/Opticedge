<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category:id,name')->orderBy('name');
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        $products = $query->limit(500)->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category_id' => $p->category_id,
            'category_name' => $p->category?->name,
            'price' => (float) ($p->price ?? 0),
            'stock_quantity' => (int) ($p->stock_quantity ?? 0),
            'is_platform' => (bool) ($p->is_platform ?? false),
        ]);

        return response()->json(['data' => $products]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'brand' => 'Samsung',
            'price' => 0,
            'rating' => 5.0,
            'stock_quantity' => 0,
            'description' => $validated['description'] ?? null,
            'images' => [],
            'is_platform' => false,
        ];
        if ($request->user()?->tenant_id) {
            $data['created_by_tenant_id'] = $request->user()->tenant_id;
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Model created.',
            'data' => ['id' => $product->id, 'name' => $product->name],
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if ($product->isPlatformCatalog()) {
            return response()->json(['message' => 'Cannot edit platform-managed models.'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'price' => $validated['price'] ?? $product->price,
            'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
            'description' => $validated['description'] ?? $product->description,
        ]);

        return response()->json(['message' => 'Model updated.', 'data' => ['id' => $product->id]]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->isPlatformCatalog()) {
            return response()->json(['message' => 'Cannot delete platform-managed models.'], 403);
        }
        if ($product->productListItems()->exists()) {
            return response()->json(['message' => 'Model has IMEI entries. Remove those first.'], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Model deleted.']);
    }

    public function imeiList(Product $product): JsonResponse
    {
        $items = ProductListItem::query()
            ->where('product_id', $product->id)
            ->orderBy('imei_number')
            ->limit(500)
            ->get(['id', 'imei_number', 'sold_at'])
            ->map(fn ($i) => [
                'id' => $i->id,
                'imei_number' => $i->imei_number,
                'status' => $i->sold_at ? 'sold' : 'available',
            ]);

        return response()->json(['data' => $items, 'product' => ['id' => $product->id, 'name' => $product->name]]);
    }
}
