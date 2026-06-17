<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerNeed;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TeamLeaderCustomerNeedController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = CustomerNeed::query()
            ->where('team_leader_id', Auth::id())
            ->with(['category:id,name', 'product:id,name', 'branch:id,name'])
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (CustomerNeed $need) => $this->serializeNeed($need))->values()->all(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $need = CustomerNeed::query()
            ->where('team_leader_id', Auth::id())
            ->with(['category:id,name', 'product:id,name', 'branch:id,name'])
            ->findOrFail($id);

        return response()->json(['data' => $this->serializeNeed($need)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:brands,id',
            'product_id' => 'required|exists:models,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:64',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        if ((int) $product->category_id !== (int) $validated['category_id']) {
            return response()->json([
                'message' => 'Selected model does not belong to the chosen category.',
            ], 422);
        }

        $attrs = [
            'agent_id' => null,
            'category_id' => $validated['category_id'],
            'product_id' => $validated['product_id'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'branch_id' => $validated['branch_id'] ?? null,
        ];

        if (Schema::hasColumn('customer_needs', 'team_leader_id')) {
            $attrs['team_leader_id'] = Auth::id();
        }

        $need = CustomerNeed::create($attrs);
        $need->load(['category', 'product', 'branch']);

        return response()->json([
            'message' => 'Customer need recorded.',
            'data' => $this->serializeNeed($need),
        ], 201);
    }

    private function serializeNeed(CustomerNeed $need): array
    {
        return [
            'id' => $need->id,
            'category' => $need->category?->name,
            'product' => $need->product?->name,
            'customer_name' => $need->customer_name,
            'customer_phone' => $need->customer_phone,
            'branch' => $need->branch?->name,
            'created_at' => $need->created_at?->toIso8601String(),
        ];
    }
}
