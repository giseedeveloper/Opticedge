<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use App\Services\ContractTerminationRequestService;
use Illuminate\Http\Request;

class AdminContractTerminationApiController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;
        if ($tenantId === null) {
            return response()->json(['message' => 'Your admin account is not linked to a vendor.'], 422);
        }

        $status = $request->query('status');
        $query = ContractTerminationRequest::query()
            ->with(['user:id,name,email,phone', 'tenant:id,name,brand_name', 'decidedByUser:id,name'])
            ->where('tenant_id', $tenantId)
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'awaiting_major'], true)) {
            $query->where('status', $status);
        } else {
            // Default admin queue: requests ready for admin decision.
            $query->whereIn('status', [
                ContractTerminationRequest::STATUS_PENDING,
                ContractTerminationRequest::STATUS_AWAITING_MAJOR,
            ]);
        }

        $page = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($row) => $row->toListArray())->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(ContractTerminationRequest $contractTermination)
    {
        $this->assertTenantAccess($contractTermination, request()->user());

        return response()->json(['data' => $contractTermination->fresh(['user', 'tenant', 'decidedByUser'])->toListArray()]);
    }

    public function approve(Request $request, ContractTerminationRequest $contractTermination)
    {
        $this->assertTenantAccess($contractTermination, $request->user());
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'rating_comment' => 'nullable|string|max:2000',
        ]);

        $ratingPayload = null;
        if (isset($validated['rating'])) {
            $ratingPayload = [
                'score' => (int) $validated['rating'],
                'comment' => $validated['rating_comment'] ?? null,
            ];
        }

        try {
            app(ContractTerminationRequestService::class)->approve(
                $contractTermination,
                $request->user(),
                $validated['admin_note'] ?? null,
                $ratingPayload,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Contract termination approved. The user is now a guest again.',
            'data' => $contractTermination->fresh(['user', 'tenant', 'decidedByUser'])->toListArray(),
        ]);
    }

    public function reject(Request $request, ContractTerminationRequest $contractTermination)
    {
        $this->assertTenantAccess($contractTermination, $request->user());
        $validated = $request->validate(['admin_note' => 'nullable|string|max:2000']);

        try {
            app(ContractTerminationRequestService::class)->reject(
                $contractTermination,
                $request->user(),
                $validated['admin_note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Contract termination request rejected.',
            'data' => $contractTermination->fresh(['user', 'tenant', 'decidedByUser'])->toListArray(),
        ]);
    }

    public function force(Request $request)
    {
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;
        if ($tenantId === null) {
            return response()->json(['message' => 'Your admin account is not linked to a vendor.'], 422);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'reason' => 'required|string|max:5000',
        ]);

        $target = \App\Models\User::withoutGlobalScopes()
            ->whereKey($validated['user_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $target) {
            return response()->json(['message' => 'User not found in your vendor.'], 404);
        }

        try {
            $row = app(ContractTerminationRequestService::class)->createForced(
                $admin,
                $target,
                $validated['reason'],
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Forced termination started. Awaiting admin review after devices are returned.',
            'data' => $row->toListArray(),
        ], 201);
    }

    private function assertTenantAccess(ContractTerminationRequest $row, $admin): void
    {
        if ($admin === null || ! in_array($admin->role, ['admin', 'subadmin'], true)) {
            abort(403);
        }
        if ((int) $admin->tenant_id !== (int) $row->tenant_id) {
            abort(403);
        }
    }
}
