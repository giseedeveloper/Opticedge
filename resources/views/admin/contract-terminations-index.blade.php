<x-admin-layout>
@include('admin.partials.catalog-styles')

<div class="admin-prod-page">
    <div class="admin-prod-toolbar">
        <div>
            <p class="admin-prod-eyebrow">Users</p>
            <h1 class="admin-prod-title">Contract termination requests</h1>
            <p class="admin-prod-subtitle">Review requests from agents, team leaders, and regional managers who want to leave your vendor.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.contract-terminations.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="admin-prod-label">Status</label>
            <select name="status" class="admin-prod-select mt-1" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
    </form>

    <div class="admin-clay-panel overflow-x-auto">
        <table class="min-w-[1100px] w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Requested</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">User</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Role</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Reason</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-900">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $row)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $row->user?->name ?? '—' }}
                            @if($row->user?->email)
                                <br><span class="text-xs text-slate-500">{{ $row->user->email }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ \App\Models\ContractTerminationRequest::roleLabel($row->role_at_request) }}</td>
                        <td class="px-4 py-3 text-slate-700 max-w-md whitespace-pre-wrap">{{ $row->reason }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($row->status === 'pending') bg-amber-100 text-amber-900
                                @elseif($row->status === 'approved') bg-green-100 text-green-900
                                @elseif($row->status === 'rejected') bg-red-100 text-red-900
                                @else bg-slate-100 text-slate-700 @endif">
                                {{ ucfirst($row->status) }}
                            </span>
                            @if($row->admin_note)
                                <div class="text-xs text-slate-500 mt-1">Admin: {{ $row->admin_note }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($row->isPending())
                                <form method="POST" action="{{ route('admin.contract-terminations.approve', $row) }}" class="inline-block mb-2">
                                    @csrf
                                    <input type="text" name="admin_note" placeholder="Optional note" class="admin-prod-input text-xs mb-1 w-40">
                                    <button type="submit" class="admin-prod-btn-primary text-xs">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.contract-terminations.reject', $row) }}" class="inline-block">
                                    @csrf
                                    <input type="text" name="admin_note" placeholder="Optional note" class="admin-prod-input text-xs mb-1 w-40">
                                    <button type="submit" class="admin-prod-btn-ghost text-xs">Reject</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-500">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No contract termination requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif
</div>
</x-admin-layout>
