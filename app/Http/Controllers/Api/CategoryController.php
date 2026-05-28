<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List categories. For admin list (with_counts=1) includes products_count.
     */
    public function index(Request $request)
    {
        $withCounts = $request->query('with_counts');
        $query = Category::orderBy('name');

        if ($withCounts) {
            $categories = $query->withCount('products')->get(['id', 'name'])->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->name, 'products_count' => $c->products_count ?? 0];
            });
        } else {
            $categories = $query->get(['id', 'name']);
        }

        return response()->json(['data' => $categories]);
    }

    /**
     * Distinct models (products) in a category — same catalog rows as agent catalog, for admin UI.
     */
    public function models(int $category): JsonResponse
    {
        if (! Category::whereKey($category)->exists()) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $products = Product::query()
            ->where('category_id', $category)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $products]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        $data = ['name' => $validated['name'], 'is_platform' => false];
        if ($request->user()?->tenant_id) {
            $data['created_by_tenant_id'] = $request->user()->tenant_id;
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Brand created.',
            'data' => ['id' => $category->id, 'name' => $category->name],
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if ($category->isPlatformCatalog()) {
            return response()->json(['message' => 'Cannot edit platform-managed brands.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,'.$category->id,
        ]);

        $category->update(['name' => $validated['name']]);

        return response()->json(['message' => 'Brand updated.', 'data' => ['id' => $category->id, 'name' => $category->name]]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->isPlatformCatalog()) {
            return response()->json(['message' => 'Cannot delete platform-managed brands.'], 403);
        }
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Brand has models. Remove them first.'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Brand deleted.']);
    }
}
