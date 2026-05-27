<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categoryTable = (new Category)->getTable();
        $productTable = (new Product)->getTable();

        // products_count = catalog SKUs in this brand (not filtered by denormalized stock_quantity).
        // available_stock_count = unsold IMEI rows for this brand (by product_list.category_id or
        // product linked to this category). Avoids negative totals when models.stock_quantity is wrong
        // after deletes/sales while physical units are zero.
        $categories = Category::query()
            ->withCount('products')
            ->addSelect([
                'available_stock_count' => ProductListItem::query()
                    ->selectRaw('COUNT(*)')
                    ->whereNull('product_list.sold_at')
                    ->where(function ($query) use ($categoryTable, $productTable) {
                        $query->whereColumn('product_list.category_id', $categoryTable.'.id')
                            ->orWhereIn(
                                'product_list.product_id',
                                Product::query()
                                    ->select($productTable.'.id')
                                    ->whereColumn($productTable.'.category_id', $categoryTable.'.id')
                            );
                    }),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        // Check for server size limit drop
        if ($request->hasFile('image') === false && $request->header('Content-Length') > 2 * 1024 * 1024) {
             $maxSize = ini_get('upload_max_filesize');
             Log::error('Category creation failed: Image upload exceeded server limit.', [
                 'content_length' => $request->header('Content-Length'),
                 'max_allowed' => $maxSize,
                 'user_id' => auth()->id()
             ]);
             return back()->withInput()->withErrors(['image' => "The uploaded file exceeded the server upload limit of {$maxSize}."]);
        }

        $data = ['name' => $request->name, 'is_platform' => false];
        if (auth()->user()?->tenant_id) {
            $data['created_by_tenant_id'] = auth()->user()->tenant_id;
        }
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Brand created successfully.');
    }

    public function show(Category $category)
    {
        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($request->hasFile('image') === false && $request->header('Content-Length') > 2 * 1024 * 1024) {
             $maxSize = ini_get('upload_max_filesize');
             Log::error('Category update failed: Image upload exceeded server limit.', [
                 'category_id' => $category->id,
                 'content_length' => $request->header('Content-Length'),
                 'max_allowed' => $maxSize
             ]);
             return back()->withInput()->withErrors(['image' => "The uploaded file exceeded the server upload limit of {$maxSize}."]);
        }

        $data = ['name' => $request->name];
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists? (Optional but good practice)
            // if ($category->image) { Storage::disk('public')->delete($category->image); }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->isPlatformCatalog()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete platform-managed brands.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Brand deleted successfully.');
    }
}
