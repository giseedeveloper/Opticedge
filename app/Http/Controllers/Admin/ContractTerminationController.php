<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTerminationRequest;
use App\Services\ContractTerminationRequestService;
use Illuminate\Http\Request;

class ContractTerminationController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;
        abort_if($tenantId === null, 403);

        $status = $request->query('status');
        if ($status && ! in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'awaiting_major'], true)) {
            $status = null;
        }

        $query = ContractTerminationRequest::query()
            ->with(['user:id,name,email,phone', 'decidedByUser:id,name', 'majorUser:id,name'])
            ->where('tenant_id', $tenantId)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [
                ContractTerminationRequest::STATUS_PENDING,
                ContractTerminationRequest::STATUS_AWAITING_MAJOR,
            ]);
        }

        $requests = $query->paginate(50)->withQueryString();

        return view('admin.contract-terminations-index', compact('requests', 'status'));
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
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Contract termination approved.');
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
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Contract termination request rejected.');
    }

    private function assertTenantAccess(ContractTerminationRequest $row, $admin): void
    {
        abort_if($admin === null || ! in_array($admin->role, ['admin', 'subadmin'], true), 403);
        abort_if((int) $admin->tenant_id !== (int) $row->tenant_id, 403);
    }
}
