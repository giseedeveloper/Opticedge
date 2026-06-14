<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">Device return requests</h1>
                <p class="admin-prod-subtitle">Regional managers return devices to admin stock. Accept or decline each pending request.</p>
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
            <table class="min-w-[900px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">From regional manager</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Units</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-900">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $r)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 text-slate-600">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ $r->fromRegionalManager->name ?? '—' }}<br><span class="text-xs text-slate-500">{{ $r->fromRegionalManager->email ?? '' }}</span></td>
                            <td class="px-4 py-3">{{ $r->items->count() }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($r->status === 'pending') bg-amber-100 text-amber-900
                                    @elseif($r->status === 'approved') bg-green-100 text-green-900
                                    @elseif($r->status === 'rejected') bg-red-100 text-red-900
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($r->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.stock.device-returns.accept', $r->id) }}" class="inline"
                                            onsubmit="return confirm('Accept this return? Devices will be added to admin stock.');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.stock.device-returns.decline', $r->id) }}" class="inline"
                                            onsubmit="return confirm('Decline this return request?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Decline</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @if($r->message || $r->recipient_note)
                            <tr class="bg-slate-50/80">
                                <td colspan="5" class="px-4 py-2 text-xs text-slate-600">
                                    @if($r->message)<span class="font-medium text-slate-700">Regional manager note:</span> {{ $r->message }}@endif
                                    @if($r->message && $r->recipient_note)<br>@endif
                                    @if($r->recipient_note)<span class="font-medium text-slate-700">Admin response:</span> {{ $r->recipient_note }}@endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No device return requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
