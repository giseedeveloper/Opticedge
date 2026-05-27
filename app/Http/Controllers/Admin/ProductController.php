<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->with('category:id,name')
            ->latest()
            ->get();
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images' => 'nullable|array|max:5',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if (! $image->isValid()) {
                    continue;
                }
                if (count($imagePaths) >= 5) {
                    break;
                }
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
        }

        $productData = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'brand' => 'Samsung',
            'price' => 0,
            'rating' => 5.0,
            'stock_quantity' => 0,
            'description' => $validated['description'] ?? null,
            'images' => $imagePaths,
            'is_platform' => false,
        ];
        if (auth()->user()?->tenant_id) {
            $productData['created_by_tenant_id'] = auth()->user()->tenant_id;
        }

        Product::create($productData);

        return redirect()->route('admin.products.index')->with('success', 'Model added successfully.');
    }
    /**
     * Show product list items (IMEI numbers) for this model.
     */
    public function showImei(Product $product)
    {
        $product->load(['productListItems' => function ($q) {
            $q->with('category:id,name')->orderBy('imei_number');
        }]);

        return view('admin.products.imei', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = \App\Models\Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'rating' => 'required|numeric|min:0|max:5',
            'description' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images' => 'nullable|array|max:5', // Max 5 files per upload
        ]);

        // Check if upload failed due to server limits
        if ($request->hasFile('images') === false && $request->header('Content-Length') > 2 * 1024 * 1024) {
            $maxSize = ini_get('upload_max_filesize');
            Log::error('Product update failed: Image upload exceeded server limit.', [
                'product_id' => $product->id,
                'content_length' => $request->header('Content-Length'),
                'max_allowed' => $maxSize
            ]);
            return back()->withInput()->withErrors(['images' => "One or more files exceeded the server upload limit of {$maxSize}."]);
        }

        // Logic: if new images are uploaded, determine strategy. 
        // For simplicity: Append new images to existing ones, up to 5.
        // If users want to delete, they would typically need a separate delete action or "replace all" logic.
        // Assuming "append" for now.
        
        $imagePaths = $product->images ? $product->images : [];
        
        // Ensure imagePaths is an array (handle potential casting issues if any)
        if (!is_array($imagePaths)) {
            $imagePaths = [];
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if (!$image->isValid()) continue;
                
                // Check if total images do not exceed 5
                if (count($imagePaths) >= 5) {
                    break;
                }
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'rating' => $request->rating,
            'stock_quantity' => $request->stock_quantity,
            'description' => $request->description,
            'images' => $imagePaths,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Stock updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->isPlatformCatalog()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Cannot delete platform-managed models.');
        }

        if ($product->purchases()->exists()) {
            return redirect()->route('admin.products.index')->with('error', 'Cannot delete this product because it has linked purchases.');
        }

        if (OrderItem::where('product_id', $product->id)->exists()) {
            return redirect()->route('admin.products.index')->with('error', 'Cannot delete this product because it appears in customer orders.');
        }

        if (CartItem::where('product_id', $product->id)->exists()) {
            return redirect()->route('admin.products.index')->with('error', 'Cannot delete this product while it is in shopping carts.');
        }

        if ($product->productListItems()->exists()) {
            return redirect()->route('admin.products.index')->with('error', 'Cannot delete this product because it has IMEI / stock list entries. Remove or reassign those first.');
        }

        if (is_array($product->images)) {
            foreach ($product->images as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
