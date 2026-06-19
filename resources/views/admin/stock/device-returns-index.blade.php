<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">Device return</h1>
                <p class="admin-prod-subtitle">All device return requests — agent to team leader, team leader to regional manager, and regional manager to admin.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.stock.device-returns') }}" class="mb-4 flex flex-wrap items-end gap-3">
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
            <table id="deviceReturnsTable" class="js-datatable min-w-[1200px] w-full text-sm" data-datatable-order="0,desc">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Route</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">From</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">To</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Units</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Notes</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns as $r)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap" data-order="{{ $r['created_at']?->timestamp ?? 0 }}">
                                {{ $r['created_at']?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $r['route_label'] }}</td>
                            <td class="px-4 py-3">
                                {{ $r['from_name'] }}
                                @if($r['from_email'])
                                    <br><span class="text-xs text-slate-500">{{ $r['from_email'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $r['to_name'] }}
                                @if($r['to_email'])
                                    <br><span class="text-xs text-slate-500">{{ $r['to_email'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" data-order="{{ $r['units'] }}">{{ $r['units'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($r['status'] === 'pending') bg-amber-100 text-amber-900
                                    @elseif($r['status'] === 'approved') bg-green-100 text-green-900
                                    @elseif($r['status'] === 'rejected') bg-red-100 text-red-900
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ ucfirst($r['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 max-w-xs">
                                @if(!empty($r['message']))
                                    <span class="font-medium text-slate-700">Sender:</span> {{ $r['message'] }}
                                @endif
                                @if(!empty($r['message']) && !empty($r['recipient_note']))<br>@endif
                                @if(!empty($r['recipient_note']))
                                    <span class="font-medium text-slate-700">Response:</span> {{ $r['recipient_note'] }}
                                @endif
                                @if(empty($r['message']) && empty($r['recipient_note']))
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end items-center gap-3">
                                    <a href="{{ $r['show_url'] }}" class="text-sm font-medium text-[#fa8900] hover:underline">View</a>
                                    @if(!empty($r['can_admin_accept']))
                                        <form method="POST" action="{{ $r['accept_url'] }}" class="inline"
                                            onsubmit="return confirm('Accept this return? Devices will be added to admin stock.');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ $r['decline_url'] }}" class="inline"
                                            onsubmit="return confirm('Decline this return request?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Decline</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
