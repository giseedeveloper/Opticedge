<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $models = Product::query()
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
            ->get();

        return view('superadmin.models.index', compact('models', 'search'));
    }

    public function create()
    {
        $brands = Category::orderBy('name')->get();

        return view('superadmin.models.create', compact('brands'));
    }

    public function store(Request $request)
    {
        $brandTable = (new Category)->getTable();
        $validated = $request->validate([
            'category_id' => 'required|exists:'.$brandTable.',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Product::create([
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

        return redirect()->route('superadmin.models.index')->with('success', 'Model added.');
    }

    public function edit(Product $model)
    {
        $brands = Category::orderBy('name')->get();

        return view('superadmin.models.edit', compact('model', 'brands'));
    }

    public function update(Request $request, Product $model)
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

        return redirect()->route('superadmin.models.index')->with('success', 'Model updated.');
    }

    public function destroy(Product $model)
    {
        if ($model->purchases()->exists() || $model->productListItems()->exists()) {
            return redirect()->route('superadmin.models.index')
                ->with('error', 'Cannot delete a model with stock or purchase history.');
        }

        $model->delete();

        return redirect()->route('superadmin.models.index')->with('success', 'Model deleted.');
    }
}
