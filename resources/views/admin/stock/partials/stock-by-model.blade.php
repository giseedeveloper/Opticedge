<x-admin-page-dashboard label="Summary" class="mt-6">
    <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <dt class="text-xs uppercase text-slate-500">Models</dt>
            <dd class="text-lg font-semibold text-slate-900">{{ number_format(count($models)) }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase text-slate-500">Holders</dt>
            <dd class="text-lg font-semibold text-slate-900">{{ number_format(count($holders)) }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase text-slate-500">Total units in hand</dt>
            <dd class="text-lg font-semibold text-slate-900">{{ number_format($grand_total) }}</dd>
        </div>
    </dl>
</x-admin-page-dashboard>

<div class="mt-6 admin-clay-panel overflow-hidden">
    <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
        @if(count($models) === 0)
            <div class="text-center text-slate-500 py-10">No stock in hand found.</div>
        @else
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th scope="col" class="admin-prod-th sticky left-0 bg-white z-10">Model</th>
                        @foreach($holders as $holderCol)
                            <th scope="col" class="admin-prod-th text-right whitespace-nowrap">{{ $holderCol['label'] }}</th>
                        @endforeach
                        <th scope="col" class="admin-prod-th text-right whitespace-nowrap">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($models as $model)
                        <tr>
                            <td class="font-semibold text-[#232f3e] sticky left-0 bg-white z-10 whitespace-nowrap">{{ $model }}</td>
                            @foreach($holders as $holderCol)
                                @php $qty = $matrix[$model][$holderCol['key']] ?? 0; @endphp
                                <td class="text-right font-variant-numeric {{ $qty > 0 ? 'text-slate-700' : 'text-slate-300' }}">
                                    {{ $qty > 0 ? number_format($qty) : '—' }}
                                </td>
                            @endforeach
                            <td class="text-right font-semibold text-slate-900">{{ number_format($row_totals[$model] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" class="admin-prod-th sticky left-0 bg-white z-10">Total</th>
                        @foreach($holders as $holderCol)
                            <th class="admin-prod-th text-right whitespace-nowrap">{{ number_format($column_totals[$holderCol['key']] ?? 0) }}</th>
                        @endforeach
                        <th class="admin-prod-th text-right whitespace-nowrap">{{ number_format($grand_total) }}</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>
