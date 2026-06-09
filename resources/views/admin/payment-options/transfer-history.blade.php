<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Payments</p>
                <h1 class="admin-prod-title">Transfer history</h1>
                <p class="admin-prod-subtitle">View all money transfers between payment channels.</p>
            </div>
            <a href="{{ route('admin.payment-options.index') }}" class="admin-prod-back shrink-0">Back to channels</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.payment-transfer.history') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="admin-prod-label">Date range</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="admin-prod-input py-2 text-sm">
            </div>
            <div>
                <label class="admin-prod-label">To</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="admin-prod-input py-2 text-sm">
            </div>
            <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Filter</button>
            @if(request('from_date') || request('to_date'))
                <a href="{{ route('admin.payment-transfer.history') }}" class="admin-prod-btn-ghost text-sm py-2 px-4">Clear</a>
            @endif
        </form>

        <div class="admin-clay-panel overflow-x-auto">
            @if($transfers->isEmpty())
                <div class="text-center py-10 text-slate-500">
                    <p class="text-sm">No transfers found.</p>
                    <a href="{{ route('admin.payment-transfer.create') }}" class="admin-prod-link mt-2 inline-block">Create first transfer →</a>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">From</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">To</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Amount</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">Description</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-900">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-3 text-slate-600">{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 font-medium">{{ $transfer->fromChannel->name }}</td>
                                <td class="px-4 py-3 font-medium">{{ $transfer->toChannel->name }}</td>
                                <td class="px-4 py-3 font-variant-numeric font-bold text-slate-900">{{ number_format($transfer->amount, 2) }} TZS</td>
                                <td class="px-4 py-3 text-slate-600">{{ $transfer->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $transfer->user->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="admin-clay-panel p-6">
                <p class="text-xs font-semibold uppercase text-slate-500 mb-1">Total transferred</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($totalTransferred, 2) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-6">
                <p class="text-xs font-semibold uppercase text-slate-500 mb-1">Number of transfers</p>
                <p class="text-2xl font-bold text-slate-900">{{ $totalCount }}</p>
            </div>
            <div class="admin-clay-panel p-6">
                <p class="text-xs font-semibold uppercase text-slate-500 mb-1">Average transfer</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($totalCount > 0 ? $totalTransferred / $totalCount : 0, 2) }} TZS</p>
            </div>
        </div>
    </div>
</x-admin-layout>
