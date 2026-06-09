<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">Branch transfer history</h1>
                <p class="admin-prod-subtitle">Past moves between branches with full device context.</p>
            </div>
            <a href="{{ route('admin.stock.branch-transfer') }}" class="admin-prod-btn-primary shrink-0">New transfer</a>
        </div>

        <div class="admin-clay-panel overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-2 text-left font-semibold">When</th>
                        <th class="px-4 py-2 text-left font-semibold">IMEI</th>
                        <th class="px-4 py-2 text-left font-semibold">Product</th>
                        <th class="px-4 py-2 text-left font-semibold">From</th>
                        <th class="px-4 py-2 text-left font-semibold">To</th>
                        <th class="px-4 py-2 text-left font-semibold">Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php $i = $log->productListItem; @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2 text-slate-600">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $i?->imei_number ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $i?->product?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $log->fromBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $log->toBranch?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $log->admin?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No transfers logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
