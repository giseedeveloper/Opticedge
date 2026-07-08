<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use App\Services\ContractTerminationRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractTerminationApiController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) Auth::id();
        $status = $request->query('status');

        $query = ContractTerminationRequest::query()
            ->with(['tenant:id,name,brand_name', 'decidedByUser:id,name'])
            ->where('user_id', $userId)
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:5000',
        ]);

        try {
            $row = app(ContractTerminationRequestService::class)->create(
                $request->user(),
                $validated['reason']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Contract termination request submitted. Your vendor admin will review it.',
            'data' => $row->toListArray(),
        ], 201);
    }

    public function show(ContractTerminationRequest $contractTermination)
    {
        if ((int) $contractTermination->user_id !== (int) Auth::id()) {
            abort(403);
        }

        return response()->json(['data' => $contractTermination->fresh(['user', 'tenant', 'decidedByUser'])->toListArray()]);
    }

    public function cancel(ContractTerminationRequest $contractTermination)
    {
        try {
            app(ContractTerminationRequestService::class)->cancel($contractTermination, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contract termination request cancelled.']);
    }
}
