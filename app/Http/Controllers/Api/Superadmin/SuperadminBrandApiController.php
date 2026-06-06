<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuperadminBrandApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 30), 1), 100);

        $paginator = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Category $b) => $this->serialize($b))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $brandTable = (new Category)->getTable();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:'.$brandTable.',name',
            'image_base64' => 'nullable|string',
        ]);

        $data = ['name' => $validated['name'], 'is_platform' => true];
        if (! empty($validated['image_base64'])) {
            $path = $this->storeBase64Image($validated['image_base64']);
            if ($path) {
                $data['image'] = $path;
            }
        }

        $brand = Category::create($data);

        return response()->json([
            'message' => 'Brand created.',
            'data' => $this->serialize($brand->loadCount('products')),
        ], 201);
    }

    public function update(Request $request, Category $brand): JsonResponse
    {
        $brandTable = (new Category)->getTable();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:'.$brandTable.',name,'.$brand->id,
            'image_base64' => 'nullable|string',
        ]);

        $data = ['name' => $validated['name'], 'is_platform' => true];
        if (! empty($validated['image_base64'])) {
            $path = $this->storeBase64Image($validated['image_base64']);
            if ($path) {
                $data['image'] = $path;
            }
        }

        $brand->update($data);

        return response()->json([
            'message' => 'Brand updated.',
            'data' => $this->serialize($brand->fresh()->loadCount('products')),
        ]);
    }

    public function destroy(Category $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a brand that has models.',
            ], 422);
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted.']);
    }

    private function storeBase64Image(string $base64): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $extension = strtolower($matches[1]);
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $extension = 'png';
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return null;
        }

        $filename = 'categories/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($filename, $binary);

        return $filename;
    }

    private function serialize(Category $brand): array
    {
        $imageUrl = $brand->image
            ? Storage::disk('public')->url($brand->image)
            : null;

        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'image' => $brand->image,
            'image_url' => $imageUrl,
            'is_platform' => (bool) $brand->is_platform,
            'products_count' => $brand->products_count ?? $brand->products()->count(),
            'created_at' => $brand->created_at?->toIso8601String(),
        ];
    }
}
