<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Agent sales</p>
                <h1 class="admin-prod-title">Pending sales</h1>
                <p class="admin-prod-subtitle">Select payment option and save to complete.</p>
            </div>
            <a href="{{ route('admin.stock.create-agent-sale') }}"
                class="shrink-0 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Record new sale</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-x-auto">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1100px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Customer</th>
                            <th scope="col" class="admin-prod-th">Seller</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Qty</th>
                            <th scope="col" class="admin-prod-th">Buy</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Total sell</th>
                            <th scope="col" class="admin-prod-th">Profit</th>
                            <th scope="col" class="admin-prod-th">Payment</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingSales as $sale)
                            <tr>
                                <td class="text-slate-600">{{ $sale->date }}</td>
                                <td class="font-semibold text-[#232f3e]">{{ $sale->customer_name ?? 'N/A' }}</td>
                                <td class="text-slate-600">{{ $sale->seller_name ?? '-' }}</td>
                                <td class="text-slate-600 text-sm">
                                    {{ $sale->product ? (($sale->product->category->name ?? '—') . ' – ' . $sale->product->name) : 'N/A' }}
                                </td>
                                <td class="font-variant-numeric">{{ $sale->quantity_sold }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->purchase_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->selling_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric font-bold">{{ number_format($sale->total_selling_value ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-green-700">{{ number_format($sale->profit ?? 0, 0) }}</td>
                                <td>
                                    <form action="{{ route('admin.stock.save-pending-sale', $sale->id) }}" method="POST">
                                        @csrf
                                        <select name="payment_option_id" required onchange="this.form.submit()"
                                            class="admin-prod-select text-sm min-w-[10rem] max-w-[14rem] py-2">
                                            <option value="">Select payment…</option>
                                            @foreach($paymentOptions as $option)
                                                <option value="{{ $option->id }}" @selected($sale->payment_option_id == $option->id)>
                                                    {{ $option->name }} ({{ ucfirst($option->type) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    @if($sale->payment_option_id)
                                        <form action="{{ route('admin.stock.save-pending-sale', $sale->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <input type="hidden" name="payment_option_id" value="{{ $sale->payment_option_id }}">
                                            <button type="submit" class="admin-prod-btn-inline admin-prod-link">Save</button>
                                        </form>
                                    @else
                                        <span class="admin-prod-muted text-xs">Select payment</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-slate-500 py-10">
                                    No pending sales.
                                    <a href="{{ route('admin.stock.create-agent-sale') }}" class="admin-prod-link">Record a sale</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $pendingSales, 'label' => 'pending sales'])
        </div>
    </div>
</x-admin-layout>
