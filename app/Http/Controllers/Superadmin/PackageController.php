<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('tenants')->orderBy('name')->get();

        return view('superadmin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('superadmin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePackage($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['name']);

        Package::create($this->mapPackageAttributes($validated, $request));

        return redirect()->route('superadmin.packages.index')->with('success', 'Package created.');
    }

    public function edit(Package $package)
    {
        return view('superadmin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $this->validatePackage($request, $package->id);
        $package->update($this->mapPackageAttributes($validated, $request, $package->slug));

        return redirect()->route('superadmin.packages.index')->with('success', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        if ($package->tenants()->exists()) {
            return redirect()->route('superadmin.packages.index')
                ->with('error', 'Cannot delete a package assigned to vendors.');
        }

        $package->delete();

        return redirect()->route('superadmin.packages.index')->with('success', 'Package deleted.');
    }

    private function validatePackage(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|string|max:255|unique:packages,slug';
        if ($ignoreId) {
            $slugRule .= ','.$ignoreId;
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'price' => 'nullable|numeric|min:0',
            'interval' => 'required|string|in:'.implode(',', array_keys(Package::INTERVALS)),
            'description' => 'nullable|string',
            'features_json' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }

    private function uniqueSlug(string $source): string
    {
        $slug = Str::slug($source);
        $base = $slug;
        $i = 1;
        while (Package::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapPackageAttributes(array $validated, Request $request, ?string $existingSlug = null): array
    {
        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $existingSlug ?? Str::slug($validated['name']),
            'price' => $validated['price'] ?? 0,
            'interval' => $validated['interval'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'features_json' => $this->parseFeaturesJson($validated['features_json'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseFeaturesJson(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
