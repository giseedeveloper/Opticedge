<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Passthrough</p>
                <h1 class="admin-prod-title">All payment receipts</h1>
                <p class="admin-prod-subtitle">Receipt images across all passthrough entries.</p>
            </div>
            <a href="{{ route('admin.stock.passthrough') }}" class="admin-prod-back shrink-0">Back to passthrough</a>
        </div>

        @if($purchases->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($purchases as $purchase)
                    <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                        <div class="admin-prod-form-head !py-3">
                            <h3 class="admin-prod-form-title !text-base">{{ $purchase->name ?? 'Passthrough #' . $purchase->id }}</h3>
                            <div class="mt-2 text-sm text-slate-600 space-y-1">
                                <p><span class="font-semibold text-slate-700">Date:</span> {{ $purchase->date }}</p>
                                <p><span class="font-semibold text-slate-700">Product:</span>
                                    {{ $purchase->product->name ?? 'N/A' }}</p>
                                <p><span class="font-semibold text-slate-700">Distributor:</span>
                                    {{ $purchase->distributor_name ?? 'N/A' }}</p>
                                <p><span class="font-semibold text-slate-700">Amount:</span>
                                    {{ number_format($purchase->paid_amount, 2) }}</p>
                                <p>
                                    <span class="font-semibold text-slate-700">Status:</span>
                                    <span
                                        class="admin-prod-dealer-status {{ $purchase->payment_status === 'paid' ? 'admin-prod-dealer-status--active' : ($purchase->payment_status === 'partial' ? 'admin-prod-dealer-status--pending' : 'admin-prod-dealer-status--suspended') }}">
                                        {{ ucfirst($purchase->payment_status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="admin-prod-form-body !pt-4">
                            <a href="{{ asset('storage/' . $purchase->payment_receipt_image) }}" target="_blank"
                                rel="noopener noreferrer" class="block">
                                <img src="{{ asset('storage/' . $purchase->payment_receipt_image) }}"
                                    alt="Receipt for {{ $purchase->name }}"
                                    class="w-full h-auto rounded-lg border border-slate-200 object-cover max-h-64">
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="admin-clay-panel p-8 text-center text-slate-500">
                No payment receipts found for passthrough entries.
            </div>
        @endif
    </div>
</x-admin-layout>
