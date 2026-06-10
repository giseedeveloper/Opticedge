{{--
  Full inventory / sale context for one product_list row (keyed by IMEI).
  Expects $item as ProductListItem with relations eager-loaded where possible.
--}}
@php
    $imei = $item->imei_number ?? '—';
    $sold = $item->sold_at !== null;
    $hierarchyChain = $item->hierarchyChain();
    $hasAgentInChain = collect($hierarchyChain)->contains(fn ($s) => $s['role'] === 'agent');
    $hasTeamLeaderInChain = collect($hierarchyChain)->contains(fn ($s) => $s['role'] === 'team_leader');
    $hasRegionalManagerInChain = collect($hierarchyChain)->contains(fn ($s) => $s['role'] === 'regional_manager');
@endphp
<div class="admin-clay-inset px-6 py-4 text-sm text-slate-700 space-y-3 border-l-4 border-[#fa8900]/50 mx-2 my-2 rounded-r-xl">
    <div class="flex flex-wrap items-baseline gap-2">
        <span class="font-mono font-semibold text-slate-900">{{ $imei }}</span>
        @if($sold)
            <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded bg-slate-200 text-slate-700">
                {{ $item->agent_sale_id || $item->agent_credit_id ? 'Installed' : ($item->distribution_sale_id ? 'Distribution' : 'Sold') }}
            </span>
            <span class="text-slate-500">{{ $item->sold_at instanceof \Carbon\Carbon ? $item->sold_at->format('Y-m-d H:i') : $item->sold_at }}</span>
        @else
            <span class="text-xs uppercase tracking-wide px-2 py-0.5 rounded bg-green-100 text-green-800">In stock</span>
        @endif
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
        @if($item->purchase)
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase text-slate-500">Purchase / source</dt>
                <dd>
                    {{ $item->purchase->name ?? 'Purchase #' . $item->purchase->id }}
                    @if(!empty($item->purchase->distributor_name))
                        <span class="text-slate-600"> — Supplier / distributor: <span class="font-medium text-slate-800">{{ $item->purchase->distributor_name }}</span></span>
                    @endif
                </dd>
            </div>
        @endif

        @if($item->stock)
            <div>
                <dt class="text-xs uppercase text-slate-500">Stock</dt>
                <dd class="font-medium">{{ $item->stock->name ?? '—' }}</dd>
            </div>
        @endif

        @if(count($hierarchyChain) > 0)
            <div class="sm:col-span-2 rounded-md bg-slate-50 border border-slate-200 px-3 py-2 space-y-2">
                <dt class="text-xs uppercase text-slate-600 font-semibold">Distribution chain</dt>
                @foreach($hierarchyChain as $step)
                    <dd class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <span class="text-xs uppercase tracking-wide text-slate-500">{{ $step['label'] }}:</span>
                        <strong class="text-slate-900">{{ $step['name'] }}</strong>
                        @if(!empty($step['email']))
                            <span class="text-slate-600 text-xs">({{ $step['email'] }})</span>
                        @endif
                    </dd>
                @endforeach
            </div>
        @elseif(!$sold)
            <div class="sm:col-span-2 text-slate-600">
                <strong>Not assigned</strong> to regional manager — available in warehouse / for assignment.
            </div>
        @endif

        @if(!$sold && !$hasAgentInChain)
            <div class="sm:col-span-2 text-slate-600">
                @if($hasTeamLeaderInChain)
                    <strong>With team leader</strong> — not yet assigned to an agent.
                @elseif($hasRegionalManagerInChain)
                    <strong>With regional manager</strong> — not yet assigned to a team leader.
                @endif
            </div>
        @endif

        @if($sold)
            @if($item->distribution_sale_id && $item->distributionSale)
                @php $ds = $item->distributionSale; @endphp
                <div class="sm:col-span-2 rounded-md bg-emerald-50 border border-emerald-100 px-3 py-2 space-y-1">
                    <div class="text-xs uppercase text-emerald-800 font-semibold">Distribution sale (dealer)</div>
                    <div><span class="text-slate-500">Dealer:</span> <strong>{{ $ds->dealer_name ?? '—' }}</strong></div>
                    @if(!empty($ds->seller_name))
                        <div><span class="text-slate-500">Recorded by:</span> {{ $ds->seller_name }}</div>
                    @endif
                    <div><span class="text-slate-500">Status:</span> <strong>{{ $ds->status ?? '—' }}</strong>
                        — Paid {{ number_format((float) ($ds->paid_amount ?? 0), 2) }} / {{ number_format((float) ($ds->total_selling_value ?? 0), 2) }} TZS
                    </div>
                    @if((float) ($ds->balance ?? 0) > 0)
                        <div><span class="text-slate-500">Balance:</span> {{ number_format((float) $ds->balance, 2) }} TZS</div>
                    @endif
                </div>
            @elseif($item->agent_credit_id && $item->agentCredit)
                @php $ac = $item->agentCredit; @endphp
                <div class="sm:col-span-2 rounded-md bg-violet-50 border border-violet-100 px-3 py-2 space-y-1">
                    <div class="text-xs uppercase text-violet-800 font-semibold">Credit sale (agent)</div>
                    <div><span class="text-slate-500">Customer:</span> <strong>{{ $ac->customer_name ?? '—' }}</strong>
                        @if(!empty($ac->customer_phone)) <span class="text-slate-600">· {{ $ac->customer_phone }}</span> @endif
                    </div>
                    @if($ac->agent)
                        <div><span class="text-slate-500">Agent:</span> <strong>{{ $ac->agent->name }}</strong></div>
                    @endif
                    <div><span class="text-slate-500">Credit status:</span> <strong>{{ $ac->payment_status ?? '—' }}</strong>
                        — Paid {{ number_format((float) ($ac->paid_amount ?? 0), 2) }} / {{ number_format((float) ($ac->total_amount ?? 0), 2) }} TZS
                    </div>
                    @if($ac->paymentOption)
                        <div><span class="text-slate-500">Channel:</span> {{ $ac->paymentOption->name }}</div>
                    @endif
                </div>
            @elseif($item->pending_sale_id && $item->pendingSale)
                @php $ps = $item->pendingSale; @endphp
                <div class="sm:col-span-2 rounded-md bg-sky-50 border border-sky-100 px-3 py-2 space-y-1">
                    <div class="text-xs uppercase text-sky-800 font-semibold">Pending sale (awaiting payment option)</div>
                    <div><span class="text-slate-500">Customer:</span> <strong>{{ $ps->customer_name ?? '—' }}</strong></div>
                    <div><span class="text-slate-500">Seller / recorded by:</span> {{ $ps->seller_name ?? '—' }}</div>
                    <div><span class="text-slate-500">Sale amount:</span> {{ number_format((float) ($ps->selling_price ?? 0), 2) }} TZS</div>
                </div>
            @elseif($item->agent_sale_id && $item->agentSale)
                @php $as = $item->agentSale; @endphp
                <div class="sm:col-span-2 rounded-md bg-orange-50 border border-orange-100 px-3 py-2 space-y-1">
                    <div class="text-xs uppercase text-orange-800 font-semibold">Installed by agent</div>
                    <div><span class="text-slate-500">Customer:</span> <strong>{{ $as->customer_name ?? '—' }}</strong></div>
                    @if($as->agent)
                        <div><span class="text-slate-500">Agent:</span> <strong>{{ $as->agent->name }}</strong></div>
                    @endif
                    <div><span class="text-slate-500">Total selling value:</span> {{ number_format((float) ($as->total_selling_value ?? 0), 2) }} TZS</div>
                </div>
            @else
                <div class="sm:col-span-2 text-amber-800 bg-amber-50 border border-amber-100 px-3 py-2 rounded-md">
                    Sold — no linked credit, pending sale, or agent sale row on this device. Check data integrity if this is unexpected.
                </div>
            @endif
        @endif
    </dl>

    <p class="text-xs text-slate-400 pt-1 border-t border-slate-200">
        Product list ID: {{ $item->id }}
        @if($item->product) · Model record: {{ $item->product?->name }} @endif
    </p>
</div>
