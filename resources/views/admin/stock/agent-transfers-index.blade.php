<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Agents</p>
                <h1 class="admin-prod-title">Agent transfer requests</h1>
                <p class="admin-prod-subtitle">View transfers between agents. Recipients accept or decline pending requests.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.stock.agent-transfers') }}" class="mb-4 flex flex-wrap items-end gap-3">
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
            <table class="min-w-[900px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">From</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">To</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Units</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 text-slate-600">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ $t->fromAgent->name }}<br><span class="text-xs text-slate-500">{{ $t->fromAgent->email }}</span></td>
                            <td class="px-4 py-3">{{ $t->toAgent->name }}<br><span class="text-xs text-slate-500">{{ $t->toAgent->email }}</span></td>
                            <td class="px-4 py-3">{{ $t->items->count() }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($t->status === 'pending') bg-amber-100 text-amber-900
                                    @elseif($t->status === 'approved') bg-green-100 text-green-900
                                    @elseif($t->status === 'rejected') bg-red-100 text-red-900
                                    @else bg-slate-100 text-slate-700 @endif">{{ $t->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.stock.agent-transfers.show', $t) }}" class="text-sm font-medium text-[#fa8900] hover:underline">View all info</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No transfer requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
