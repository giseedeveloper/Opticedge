@php
    $showRoute = $isPassthrough ? 'admin.stock.passthrough.show' : 'admin.stock.purchase.show';
    $editRoute = $isPassthrough ? 'admin.stock.edit-passthrough' : 'admin.stock.edit-purchase';
    $destroyRoute = $isPassthrough ? 'admin.stock.destroy-passthrough' : 'admin.stock.destroy-purchase';
@endphp
@forelse($purchases as $purchase)
    @php
        $totalVal = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
        $paidVal = (float) ($purchase->paid_amount ?? 0);
        $pendingVal = max(0, $totalVal - $paidVal);
    @endphp
    <tr>
        <td class="text-slate-800">{{ $purchase->name ?? '–' }}</td>
        <td class="text-slate-600 text-sm">{{ $purchase->date }}</td>
        <td class="text-slate-600">{{ $purchase->branch?->name ?? '–' }}</td>
        <td class="text-slate-600">{{ $purchase->distributor_name ?? '-' }}</td>
        <td class="font-medium text-[#232f3e]">
            @if(($purchase->lines ?? collect())->isNotEmpty())
                {{ $purchase->lines->map(fn ($l) => $l->product?->name)->filter()->unique()->implode(', ') }}
            @else
                {{ $purchase->product?->name ?? 'N/A' }}
            @endif
        </td>
        <td class="font-variant-numeric">{{ $purchase->quantity }}</td>
        <td class="font-variant-numeric text-sm">{{ number_format($purchase->unit_price, 2) }}</td>
        <td class="font-variant-numeric font-bold">{{ number_format($totalVal, 2) }}</td>
        <td class="text-sm text-slate-600">{{ $purchase->paid_date ?? '-' }}</td>
        <td class="font-variant-numeric text-sm">{{ number_format($paidVal, 2) }}</td>
        <td class="font-variant-numeric font-medium">{{ number_format($pendingVal, 2) }}</td>
        <td class="font-variant-numeric text-sm">
            @if(($purchase->lines ?? collect())->isNotEmpty())
                @php
                    $sells = $purchase->lines->pluck('sell_price')->filter(fn ($s) => $s !== null)->unique();
                @endphp
                {{ $sells->isNotEmpty() ? $sells->map(fn ($s) => number_format((float) $s, 2))->implode(', ') : '–' }}
            @else
                {{ $purchase->sell_price !== null ? number_format($purchase->sell_price, 2) : '–' }}
            @endif
        </td>
        <td>
            <span
                class="admin-prod-dealer-status {{ $purchase->payment_status === 'paid' ? 'admin-prod-dealer-status--active' : ($purchase->payment_status === 'partial' ? 'admin-prod-dealer-status--pending' : 'admin-prod-dealer-status--suspended') }}">
                {{ $purchase->payment_status }}
            </span>
        </td>
        <td class="admin-prod-cell-actions">
            <div class="admin-prod-actions flex-wrap gap-2">
                <a href="{{ route($showRoute, $purchase->id) }}" class="text-slate-600 hover:text-[#fa8900]"
                    title="View">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </a>
                <a href="{{ route($editRoute, $purchase->id) }}" class="text-slate-600 hover:text-[#fa8900]"
                    title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                </a>
                <form action="{{ route($destroyRoute, $purchase->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this {{ $isPassthrough ? 'passthrough entry' : 'purchase' }}?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="14" class="text-center text-slate-500 py-10">{{ $isPassthrough ? 'No passthrough entries found.' : 'No purchases found.' }}</td>
    </tr>
@endforelse
