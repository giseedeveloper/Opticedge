<div class="admin-clay-panel overflow-x-auto">
    <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Devices ({{ $items->count() }})</h2>
    <table class="min-w-[1000px] w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50">
                <th class="px-4 py-2 text-left font-semibold">IMEI</th>
                <th class="px-4 py-2 text-left font-semibold">Model</th>
                <th class="px-4 py-2 text-left font-semibold">Product</th>
                <th class="px-4 py-2 text-left font-semibold">Category</th>
                <th class="px-4 py-2 text-left font-semibold">Stock</th>
                <th class="px-4 py-2 text-left font-semibold">Purchase</th>
                <th class="px-4 py-2 text-left font-semibold">Branch</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $ti)
                @php $i = $ti->productListItem; @endphp
                @if(!$i)
                    @continue
                @endif
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 font-mono text-xs">{{ $i->imei_number }}</td>
                    <td class="px-4 py-2">{{ $i->model ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $i->product->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $i->product->category->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $i->stock->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if($i->purchase)
                            {{ $i->purchase->name ?? 'Purchase #'.$i->purchase_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @php
                            $bname = $i->branch?->name ?? $i->purchase?->branch?->name;
                        @endphp
                        {{ $bname ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-slate-500">No devices on this request.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
