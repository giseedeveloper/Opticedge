<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsShopCatalog;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\Request;

class PublicShopApiController extends Controller
{
    use FormatsShopCatalog;

    public function categories()
    {
        $categories = Category::query()->orderBy('name')->get();

        return response()->json([
            'data' => $categories->map(fn ($c) => $this->formatCategory($c))->values(),
        ]);
    }

    public function products(Request $request)
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $products = $this->shopProductsQuery($categoryId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->input('q'), '%_\\').'%';
                $q->where('name', 'like', $term);
            })
            ->paginate(min((int) $request->input('per_page', 24), 48));

        return response()->json([
            'data' => collect($products->items())->map(fn ($p) => $this->formatProduct($p))->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function showProduct(Product $product)
    {
        if (! $this->shopProductsQuery()->where('id', $product->id)->exists()) {
            abort(404);
        }

        $product->load('category');

        $related = $this->shopProductsQuery($product->category_id)
            ->where('id', '!=', $product->id)
            ->take(6)
            ->get();

        return response()->json([
            'data' => $this->formatProduct($product, detailed: true),
            'related' => $related->map(fn ($p) => $this->formatProduct($p))->values(),
        ]);
    }

    public function packages()
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'data' => $packages->map(fn (Package $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'price' => (float) $p->price,
                'interval' => $p->interval,
                'interval_label' => $p->intervalLabel(),
                'description' => $p->description,
                'features' => $p->features_json ?? [],
            ])->values(),
        ]);
    }

    public function authConfig(Request $request)
    {
        $clientId = config('services.google.client_id');
        $enabled = filled($clientId);
        $googleAuthUrl = null;
        if ($enabled) {
            $googleAuthUrl = rtrim($request->getSchemeAndHttpHost(), '/').'/auth/google';
            if ($request->boolean('mobile')) {
                $googleAuthUrl .= (str_contains($googleAuthUrl, '?') ? '&' : '?').'mobile=1';
            }
        }

        return response()->json([
            'data' => [
                'google_sign_in_enabled' => $enabled,
                'google_web_client_id' => $clientId,
                'google_auth_url' => $googleAuthUrl,
            ],
        ]);
    }
}
