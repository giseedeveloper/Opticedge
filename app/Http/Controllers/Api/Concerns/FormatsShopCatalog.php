<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Category;
use App\Models\Product;

trait FormatsShopCatalog
{
    protected function formatCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'image' => $category->image,
        ];
    }

    protected function formatProduct(Product $product, bool $detailed = false): array
    {
        $images = is_array($product->images) ? $product->images : [];

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand,
            'price' => (float) $product->price,
            'rating' => $product->rating,
            'stock_quantity' => (int) $product->stock_quantity,
            'image_url' => $images[0] ?? null,
            'category' => $product->relationLoaded('category') && $product->category
                ? $this->formatCategory($product->category)
                : null,
        ];

        if ($detailed) {
            $data['description'] = $product->description;
            $data['images'] = $images;
        }

        return $data;
    }

    protected function shopProductsQuery(?int $categoryId = null)
    {
        $query = Product::query()
            ->whereHas('purchases', function ($q) {
                $q->where('limit_status', 'complete');
            })
            ->with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->latest();
    }
}
