<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
class BrandController extends Controller
{
    public function index()
    {
        $brands = Category::query()->orderBy('name')->paginate(30);

        return view('superadmin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('superadmin.brands.create');
    }

    public function store(Request $request)
    {
        $brandTable = (new Category)->getTable();
        $request->validate([
            'name' => 'required|string|max:255|unique:'.$brandTable.',name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = ['name' => $request->name, 'is_platform' => true];
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('superadmin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Category $brand)
    {
        return view('superadmin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Category $brand)
    {
        $brandTable = (new Category)->getTable();
        $request->validate([
            'name' => 'required|string|max:255|unique:'.$brandTable.',name,'.$brand->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = ['name' => $request->name, 'is_platform' => true];
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $brand->update($data);

        return redirect()->route('superadmin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Category $brand)
    {
        if ($brand->products()->exists()) {
            return redirect()->route('superadmin.brands.index')
                ->with('error', 'Cannot delete a brand that has models.');
        }

        $brand->delete();

        return redirect()->route('superadmin.brands.index')->with('success', 'Brand deleted.');
    }
}
