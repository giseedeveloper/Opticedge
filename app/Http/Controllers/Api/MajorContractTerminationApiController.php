<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use App\Services\ContractTerminationRequestService;
use Illuminate\Http\Request;

class MajorContractTerminationApiController extends Controller
{
    public function index(Request $request)
    {
        $major = $request->user();
        if (! in_array($major?->role, ['teamleader', 'regional_manager'], true)) {
            abort(403);
        }

        $status = $request->query('status');
        $query = ContractTerminationRequest::query()
            ->with(['user:id,name,email,phone', 'tenant:id,name,brand_name', 'majorUser:id,name,role'])
            ->where('major_user_id', $major->id)
            ->latest();

        if ($status && in_array($status, [
            ContractTerminationRequest::STATUS_AWAITING_MAJOR,
            ContractTerminationRequest::STATUS_PENDING,
            ContractTerminationRequest::STATUS_APPROVED,
            ContractTerminationRequest::STATUS_REJECTED,
            ContractTerminationRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
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

    public function approve(Request $request, ContractTerminationRequest $contractTermination)
    {
        $this->assertMajorAccess($contractTermination, $request->user());
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            $row = app(ContractTerminationRequestService::class)->approveByMajor(
                $contractTermination,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Approved. Vendor admin will make the final decision.',
            'data' => $row->toListArray(),
        ]);
    }

    public function reject(Request $request, ContractTerminationRequest $contractTermination)
    {
        $this->assertMajorAccess($contractTermination, $request->user());
        $validated = $request->validate(['note' => 'nullable|string|max:2000']);

        try {
            app(ContractTerminationRequestService::class)->rejectByMajor(
                $contractTermination,
                $request->user(),
                $validated['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Contract termination request rejected.',
            'data' => $contractTermination->fresh(['user', 'tenant', 'majorUser'])->toListArray(),
        ]);
    }

    private function assertMajorAccess(ContractTerminationRequest $row, $user): void
    {
        if ($user === null || ! in_array($user->role, ['teamleader', 'regional_manager'], true)) {
            abort(403);
        }
        if ((int) $row->major_user_id !== (int) $user->id) {
            abort(403);
        }
    }
}
