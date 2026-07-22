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
            'profit' => 'nullable|numeric|min:0',
            'interval' => 'required|string|in:'.implode(',', array_keys(Package::INTERVALS)),
            'trial_days' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'max_agents' => 'nullable|integer|min:0',
            'max_admins' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
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
            'profit' => $validated['profit'] ?? 0,
            'interval' => $validated['interval'],
            'trial_days' => $validated['trial_days'] ?? null,
            'max_users' => $validated['max_users'] ?? null,
            'max_agents' => $validated['max_agents'] ?? null,
            'max_admins' => $validated['max_admins'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'features_json' => $this->buildFeatures($request),
        ];
    }

    /**
     * Build the features_json map from submitted checkboxes, limited to the
     * canonical Package::FEATURES catalog. Only enabled flags are stored.
     *
     * @return array<string, bool>
     */
    private function buildFeatures(Request $request): array
    {
        $submitted = (array) $request->input('features', []);
        $features = [];

        foreach (array_keys(Package::FEATURES) as $key) {
            if (! empty($submitted[$key])) {
                $features[$key] = true;
            }
        }

        return $features;
    }
}
