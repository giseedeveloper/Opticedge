<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVendorApiController extends Controller
{
    public function index(): JsonResponse
    {
        $vendors = Vendor::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Vendor $v) => $this->serialize($v));

        return response()->json(['data' => $vendors]);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return response()->json(['data' => $this->serialize($vendor)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedFields($request);
        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor added.',
            'data' => $this->serialize($vendor),
        ], 201);
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $vendor->update($this->validatedFields($request));

        return response()->json([
            'message' => 'Vendor updated.',
            'data' => $this->serialize($vendor->fresh()),
        ]);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted.']);
    }

    private function validatedFields(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'office_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);
    }

    private function serialize(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
            'office_name' => $vendor->office_name,
            'location' => $vendor->location,
            'created_at' => $vendor->created_at?->toISOString(),
            'updated_at' => $vendor->updated_at?->toISOString(),
        ];
    }
}
