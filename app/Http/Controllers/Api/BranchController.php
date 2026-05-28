<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        $branches = Branch::withCount('purchases')
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'purchases_count' => $b->purchases_count ?? 0,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $branches]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created.',
            'data' => ['id' => $branch->id, 'name' => $branch->name],
        ], 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $branch->update($validated);

        return response()->json(['message' => 'Branch updated.', 'data' => ['id' => $branch->id, 'name' => $branch->name]]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        if ($branch->purchases()->exists()) {
            return response()->json(['message' => 'Branch has purchases. Reassign them first.'], 422);
        }

        $branch->delete();

        return response()->json(['message' => 'Branch deleted.']);
    }
}
