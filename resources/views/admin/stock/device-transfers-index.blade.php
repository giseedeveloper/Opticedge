<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">Device transfer</h1>
                <p class="admin-prod-subtitle">All device transfer requests — admin to regional manager, regional manager to team leader, and agent to agent.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.stock.device-transfers') }}" class="mb-4 flex flex-wrap items-end gap-3">
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
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Route</th>
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
                            <td class="px-4 py-3 text-slate-600">{{ $t['created_at']?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $t['route_label'] }}</td>
                            <td class="px-4 py-3">
                                {{ $t['from_name'] }}
                                @if($t['from_email'])
                                    <br><span class="text-xs text-slate-500">{{ $t['from_email'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $t['to_name'] }}
                                @if($t['to_email'])
                                    <br><span class="text-xs text-slate-500">{{ $t['to_email'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $t['units'] }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($t['status'] === 'pending') bg-amber-100 text-amber-900
                                    @elseif($t['status'] === 'approved') bg-green-100 text-green-900
                                    @elseif($t['status'] === 'rejected') bg-red-100 text-red-900
                                    @else bg-slate-100 text-slate-700 @endif">{{ ucfirst($t['status']) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $t['show_url'] }}" class="text-sm font-medium text-[#fa8900] hover:underline">View</a>
                            </td>
                        </tr>
                        @if(!empty($t['message']))
                            <tr class="bg-slate-50/80">
                                <td colspan="7" class="px-4 py-2 text-xs text-slate-600">
                                    <span class="font-medium text-slate-700">Message:</span> {{ $t['message'] }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No device transfer requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
