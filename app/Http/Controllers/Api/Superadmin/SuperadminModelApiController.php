<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperadminModelApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $perPage = min(max((int) $request->input('per_page', 30), 1), 100);

        $paginator = Product::query()
            ->with('category:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $like));
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Product $m) => $this->serialize($m))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'search' => $search,
            ],
        ]);
    }

    public function formData(): JsonResponse
    {
        $brands = Category::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => ['brands' => $brands]]);
    }

    public function store(Request $request): JsonResponse
    {
        $brandTable = (new Category)->getTable();
        $validated = $request->validate([
            'category_id' => 'required|exists:'.$brandTable.',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $model = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'brand' => 'Samsung',
            'price' => 0,
            'rating' => 5.0,
            'stock_quantity' => 0,
            'description' => $validated['description'] ?? null,
            'images' => [],
            'is_platform' => true,
        ]);

        $model->load('category:id,name');

        return response()->json([
            'message' => 'Model added.',
            'data' => $this->serialize($model),
        ], 201);
    }

    public function update(Request $request, Product $model): JsonResponse
    {
        $brandTable = (new Category)->getTable();
        $validated = $request->validate([
            'category_id' => 'required|exists:'.$brandTable.',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $model->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_platform' => true,
        ]);

        $model->load('category:id,name');

        return response()->json([
            'message' => 'Model updated.',
            'data' => $this->serialize($model->fresh()),
        ]);
    }

    public function destroy(Product $model): JsonResponse
    {
        if ($model->purchases()->exists() || $model->productListItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a model with stock or purchase history.',
            ], 422);
        }

        $model->delete();

        return response()->json(['message' => 'Model deleted.']);
    }

    private function serialize(Product $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'description' => $model->description,
            'category_id' => $model->category_id,
            'category_name' => $model->category?->name,
            'is_platform' => (bool) $model->is_platform,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
