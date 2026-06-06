<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminRegionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $query = Region::query()->orderBy('name');

        if (Schema::hasColumn('regions', 'created_by_tenant_id') && $tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('created_by_tenant_id', $tenantId)
                    ->orWhere(function ($q2) {
                        if (Schema::hasColumn('regions', 'is_platform')) {
                            $q2->where('is_platform', false)->whereNull('created_by_tenant_id');
                        }
                    });
            });
        }

        $regions = $query->get()->map(fn (Region $r) => $this->serialize($r));

        return response()->json(['data' => $regions]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
        ]);

        $attrs = ['name' => $validated['name']];
        if (Schema::hasColumn('regions', 'is_platform')) {
            $attrs['is_platform'] = false;
        }
        if (Schema::hasColumn('regions', 'created_by_tenant_id') && auth()->user()?->tenant_id) {
            $attrs['created_by_tenant_id'] = auth()->user()->tenant_id;
        }

        $region = Region::create($attrs);

        return response()->json([
            'message' => 'Region added.',
            'data' => $this->serialize($region),
        ], 201);
    }

    public function update(Request $request, Region $region): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,'.$region->id,
        ]);

        $region->update($validated);

        return response()->json([
            'message' => 'Region updated.',
            'data' => $this->serialize($region->fresh()),
        ]);
    }

    public function destroy(Region $region): JsonResponse
    {
        if ($region->users()->exists()) {
            return response()->json(['message' => 'Cannot delete a region linked to users.'], 422);
        }

        $region->delete();

        return response()->json(['message' => 'Region deleted.']);
    }

    private function serialize(Region $region): array
    {
        return [
            'id' => $region->id,
            'name' => $region->name,
            'is_platform' => (bool) ($region->is_platform ?? false),
            'created_at' => $region->created_at?->toISOString(),
        ];
    }
}
