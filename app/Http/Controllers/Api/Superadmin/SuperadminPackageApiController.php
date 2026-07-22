<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperadminPackageApiController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::withCount('tenants')->orderBy('name')->get()
            ->map(fn (Package $p) => $this->serialize($p));

        return response()->json(['data' => $packages]);
    }

    public function show(int $id): JsonResponse
    {
        $package = Package::withCount('tenants')->findOrFail($id);

        return response()->json(['data' => $this->serialize($package)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePackage($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['name']);

        $package = Package::create($this->mapPackageAttributes($validated, $request));

        return response()->json([
            'message' => 'Package created.',
            'data' => $this->serialize($package->loadCount('tenants')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $package = Package::findOrFail($id);
        $validated = $this->validatePackage($request, $package->id);
        $package->update($this->mapPackageAttributes($validated, $request, $package->slug));

        return response()->json([
            'message' => 'Package updated.',
            'data' => $this->serialize($package->fresh()->loadCount('tenants')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $package = Package::findOrFail($id);

        if ($package->tenants()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a package assigned to vendors.',
            ], 422);
        }

        $package->delete();

        return response()->json(['message' => 'Package deleted.']);
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
            'features_json' => 'nullable',
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
            'is_active' => $request->boolean('is_active', true),
            'features_json' => $request->has('features')
                ? $this->buildFeatures($request)
                : $this->parseFeaturesJson($validated['features_json'] ?? null),
        ];
    }

    /**
     * Build the features_json map from a submitted `features` object/array,
     * limited to the canonical Package::FEATURES catalog.
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

    /**
     * @return array<string, mixed>|null
     */
    private function parseFeaturesJson(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) === '') {
            return null;
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    private function serialize(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'price' => (float) $package->price,
            'profit' => (float) $package->profit,
            'interval' => $package->interval,
            'interval_label' => $package->intervalLabel(),
            'trial_days' => $package->trial_days,
            'max_users' => $package->max_users,
            'max_agents' => $package->max_agents,
            'max_admins' => $package->max_admins,
            'description' => $package->description,
            'features_json' => $package->features_json,
            'features' => $package->enabledFeatureLabels(),
            'is_active' => (bool) $package->is_active,
            'tenants_count' => $package->tenants_count ?? $package->tenants()->count(),
            'formatted_price' => $package->formattedPrice(),
        ];
    }
}
