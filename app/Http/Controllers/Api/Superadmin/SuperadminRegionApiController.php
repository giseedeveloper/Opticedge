<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperadminRegionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 40), 1), 100);

        $paginator = Region::orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Region $r) => $this->serialize($r))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
        ]);

        $region = Region::create([
            'name' => $validated['name'],
            'is_platform' => true,
        ]);

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
            return response()->json([
                'message' => 'Cannot delete a region linked to users.',
            ], 422);
        }

        $region->delete();

        return response()->json(['message' => 'Region deleted.']);
    }

    private function serialize(Region $region): array
    {
        return [
            'id' => $region->id,
            'name' => $region->name,
            'is_platform' => (bool) $region->is_platform,
            'created_at' => $region->created_at?->toIso8601String(),
        ];
    }
}
