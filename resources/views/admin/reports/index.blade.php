<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Analytics</p>
                <h1 class="admin-prod-title">Sales reports</h1>
                <p class="admin-prod-subtitle">Storefront totals, branch purchase mix, and recent sales trend.</p>
            </div>
            <button type="button"
                class="admin-prod-btn-ghost shrink-0 cursor-default opacity-80"
                title="Placeholder — wire export when ready">
                Export data
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-8">
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head !py-4">
                    <p class="admin-prod-eyebrow !mb-1">Revenue</p>
                    <p class="admin-prod-form-title !text-2xl font-variant-numeric">{{ number_format($totalSales, 0) }} TZS</p>
                    <p class="admin-prod-form-hint !mt-1 text-green-700">+12% vs last month (sample)</p>
                </div>
            </div>
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head !py-4">
                    <p class="admin-prod-eyebrow !mb-1">Orders</p>
                    <p class="admin-prod-form-title !text-2xl font-variant-numeric">{{ number_format($totalOrders) }}</p>
                    <p class="admin-prod-form-hint !mt-1 text-green-700">+5% vs last month (sample)</p>
                </div>
            </div>
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head !py-4">
                    <p class="admin-prod-eyebrow !mb-1">Customers</p>
                    <p class="admin-prod-form-title !text-2xl font-variant-numeric">{{ number_format($totalCustomers) }}</p>
                    <p class="admin-prod-form-hint !mt-1">Active accounts</p>
                </div>
            </div>
        </div>

        @php
            $asr = $agentStockReport;
            $agentColorBands = [
                'bg-orange-100/80',
                'bg-sky-100/80',
                'bg-emerald-100/80',
                'bg-violet-100/80',
                'bg-amber-100/80',
                'bg-rose-100/80',
            ];
            $agentCellBands = [
                'bg-orange-100/40',
                'bg-sky-100/40',
                'bg-emerald-100/40',
                'bg-violet-100/40',
                'bg-amber-100/40',
                'bg-rose-100/40',
            ];
        @endphp
        <div class="admin-clay-panel overflow-hidden mb-8">
            <div class="admin-prod-form-head">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="admin-prod-form-title">Agent opening stock &amp; sales (by product)</h2>
                        <p class="admin-prod-form-hint max-w-3xl">
                            <strong>Agent — Opening</strong> = end-of-yesterday position (same as that day’s <strong>closing</strong>): it stays fixed for the whole calendar day and does not drop when sales are recorded.
                            <strong>Sales</strong> = units sold in the selected <strong>date range</strong> (From–To).
                            <strong>Closing</strong> = <strong>opening − sales</strong> per agent.
                            <strong>Total</strong> = sum across <strong>all agents only</strong> for that product (opening, sales, closing each summed separately). Shop / unassigned warehouse stock is <strong>not</strong> included in Total.
                        </p>
                    </div>
                    <a href="{{ route('admin.reports.agent-stock-export', ['date_from' => $asr['report_date_from'] ?? $asr['report_date'], 'date_to' => $asr['report_date_to'] ?? $asr['report_date'], 'branch_id' => request('branch_id')]) }}"
                        class="admin-prod-btn-primary text-sm py-2 px-4 shrink-0 whitespace-nowrap">
                        Export Excel (CSV)
                    </a>
                </div>
            </div>
            <div class="admin-prod-form-body !pt-4 border-t border-white/60">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
                    @php
                        $reportDateFrom = request('date_from', $asr['report_date_from'] ?? $asr['report_date']);
                        $reportDateTo = request('date_to', $asr['report_date_to'] ?? $asr['report_date']);
                    @endphp
                    <div>
                        <label for="date_from" class="admin-prod-label !mb-1">From</label>
                        <input type="date" id="date_from" name="date_from" value="{{ $reportDateFrom }}"
                            class="admin-prod-input py-2 text-sm min-w-[11rem]">
                    </div>
                    <div>
                        <label for="date_to" class="admin-prod-label !mb-1">To</label>
                        <input type="date" id="date_to" name="date_to" value="{{ $reportDateTo }}"
                            class="admin-prod-input py-2 text-sm min-w-[11rem]">
                    </div>
                    <div>
                        <label for="agent_report_branch_id" class="admin-prod-label !mb-1">Branch</label>
                        <select name="branch_id" id="agent_report_branch_id" class="admin-prod-select text-sm min-w-[200px] py-2">
                            <option value="">All branches</option>
                            @foreach($reportBranchOptions as $b)
                                <option value="{{ $b->id }}" @selected((string) request('branch_id') === (string) $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Apply</button>
                </form>

                @if($asr['agents']->isEmpty())
                    <p class="text-sm text-amber-800 bg-amber-50/80 border border-amber-200/70 rounded-lg px-3 py-2 mb-4">No agents yet — <strong>Total</strong> columns show agent sums only (0). Warehouse stock is not rolled into Total. Add agents under Sales team to see per-agent columns.</p>
                @endif
                @if(count($asr['rows']) === 0)
                    <p class="text-sm text-slate-500 py-6">No stock movement for this date and branch filter.</p>
                @else
                    <style>
                        .admin-report-table-container {
                            overflow-x: auto;
                            border-radius: 0.75rem;
                            max-height: 600px;
                            overflow-y: auto;
                        }
                        
                        .admin-report-table-container table {
                            min-width: 720px;
                        }
                        
                        .admin-report-table-container thead tr th {
                            position: sticky;
                            top: 0;
                            z-index: 10;
                            background-color: inherit;
                        }
                        
                        .admin-report-table-wrap-sticky {
                            position: relative;
                        }
                        
                        .admin-report-table-container tbody tr td:first-child {
                            position: sticky;
                            left: 0;
                            z-index: 5;
                            background-color: white;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            min-width: 150px;
                        }
                        
                        .admin-report-table-container tbody tr.totals-row td:first-child {
                            background-color: #f1f5f9;
                        }
                    </style>
                    
                    <div class="admin-report-table-container rounded-xl">
                        <table class="text-sm">
                            <thead>
                                <tr>
                                    <th scope="col" class="admin-prod-th align-bottom" rowspan="2">Product</th>
                                    <th scope="col" class="admin-prod-th text-center bg-slate-100/80" colspan="3">Total</th>
                                    @foreach($asr['agents'] as $agent)
                                        @php
                                            $agentBand = $agentColorBands[$loop->index % count($agentColorBands)];
                                            $branchLabel = $agent->branch?->name;
                                            $agentHeader = $branchLabel ? ($agent->name.' · '.$branchLabel) : $agent->name;
                                        @endphp
                                        <th scope="col" class="admin-prod-th text-center {{ $agentBand }}" colspan="3">{{ $agentHeader }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end text-xs bg-slate-100/80">Opening</th>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end text-xs bg-slate-100/80">Sales</th>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end text-xs bg-slate-100/80">Closing</th>
                                    @foreach($asr['agents'] as $agent)
                                        @php $agentBand = $agentColorBands[$loop->index % count($agentColorBands)]; @endphp
                                        <th scope="col" class="admin-prod-th admin-prod-th--end text-xs {{ $agentBand }}">Opening</th>
                                        <th scope="col" class="admin-prod-th admin-prod-th--end text-xs {{ $agentBand }}">Sales</th>
                                        <th scope="col" class="admin-prod-th admin-prod-th--end text-xs {{ $agentBand }}">Closing</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asr['rows'] as $row)
                                    @php
                                        $agentsO = collect($row['agents'] ?? [])->sum('opening');
                                        $agentsS = collect($row['agents'] ?? [])->sum('sales');
                                        $agentsC = collect($row['agents'] ?? [])->sum('closing');
                                    @endphp
                                    <tr class="h-12">
                                        <td class="font-medium text-[#232f3e] bg-white px-3 py-3">{{ $row['name'] }}</td>
                                        <td class="text-right font-variant-numeric bg-slate-50/50 px-3 py-3">{{ number_format($agentsO) }}</td>
                                        <td class="text-right font-variant-numeric bg-slate-50/50 px-3 py-3">{{ number_format($agentsS) }}</td>
                                        <td class="text-right font-variant-numeric bg-slate-50/50 font-semibold px-3 py-3">{{ number_format($agentsC) }}</td>
                                        @foreach($asr['agents'] as $agent)
                                            @php $ac = $row['agents'][(int) $agent->id] ?? ['opening' => 0, 'sales' => 0, 'closing' => 0]; @endphp
                                            @php $agentCellBand = $agentCellBands[$loop->index % count($agentCellBands)]; @endphp
                                            <td class="text-right font-variant-numeric {{ $agentCellBand }} px-3 py-3">{{ number_format($ac['opening']) }}</td>
                                            <td class="text-right font-variant-numeric {{ $agentCellBand }} px-3 py-3">{{ number_format($ac['sales']) }}</td>
                                            <td class="text-right font-variant-numeric {{ $agentCellBand }} font-semibold px-3 py-3">{{ number_format($ac['closing']) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                @php
                                    $tot = $asr['totals'];
                                    $totAgentsO = collect($tot['agents'] ?? [])->sum('opening');
                                    $totAgentsS = collect($tot['agents'] ?? [])->sum('sales');
                                    $totAgentsC = collect($tot['agents'] ?? [])->sum('closing');
                                @endphp
                                <tr class="border-t-2 border-slate-300 font-semibold text-[#232f3e] totals-row h-12">
                                    <td class="bg-slate-100 px-3 py-3">Totals</td>
                                    <td class="text-right font-variant-numeric bg-slate-50/50 px-3 py-3">{{ number_format($totAgentsO) }}</td>
                                    <td class="text-right font-variant-numeric bg-slate-50/50 px-3 py-3">{{ number_format($totAgentsS) }}</td>
                                    <td class="text-right font-variant-numeric bg-slate-50/50 px-3 py-3">{{ number_format($totAgentsC) }}</td>
                                    @foreach($asr['agents'] as $agent)
                                        @php $tc = $tot['agents'][(int) $agent->id] ?? ['opening' => 0, 'sales' => 0, 'closing' => 0]; @endphp
                                        @php $agentCellBand = $agentCellBands[$loop->index % count($agentCellBands)]; @endphp
                                        <td class="text-right font-variant-numeric {{ $agentCellBand }} px-3 py-3">{{ number_format($tc['opening']) }}</td>
                                        <td class="text-right font-variant-numeric {{ $agentCellBand }} px-3 py-3">{{ number_format($tc['sales']) }}</td>
                                        <td class="text-right font-variant-numeric {{ $agentCellBand }} px-3 py-3">{{ number_format($tc['closing']) }}</td>
                                    @endforeach
                                </tr>
                            </tbody> 
                        </table>
                    </div>
                    @if(request('branch_id'))
                        <p class="mt-2 text-xs text-slate-600 bg-slate-50/90 border border-slate-200/80 rounded-lg px-3 py-2">
                            Branch filter is on: agent columns show this branch’s team (assigned branch) plus any rep with stock or sales in this branch’s scope, even if their profile branch is not set yet.
                        </p>
                    @endif
                    <p class="mt-3 text-xs text-slate-500"><strong>Total</strong> = sum of every agent column for that product (opening, sales, closing each summed across agents). Shop / unassigned warehouse is excluded. Agent cells are per rep. <strong>Opening</strong> is at the start of the From date; <strong>sales</strong> are summed across From–To.</p>
                @endif
            </div>
        </div>

        @if($branchesBusiness->isNotEmpty() || $unassignedPurchases > 0)
            <div class="admin-clay-panel overflow-hidden mb-8">
                <div class="admin-prod-form-head">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="admin-prod-form-title">Business by branch (purchases)</h2>
                            <p class="admin-prod-form-hint">Highlight a branch to see detail above the table.</p>
                        </div>
                        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-2">
                            @if(request('date_from'))
                                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            @endif
                            @if(request('date_to'))
                                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                            @endif
                            <div>
                                <label for="branch_id" class="admin-prod-label !mb-1">Branch</label>
                                <select name="branch_id" id="branch_id" onchange="this.form.submit()" class="admin-prod-select text-sm min-w-[200px] py-2">
                                    <option value="">All branches</option>
                                    @foreach($branchesBusiness as $row)
                                        <option value="{{ $row->id }}" @selected(request('branch_id') == $row->id)>{{ $row->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(request('branch_id'))
                                <a href="{{ route('admin.reports.index', request()->only(['date_from', 'date_to'])) }}" class="admin-prod-btn-ghost text-sm py-2">Clear</a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="admin-prod-form-body !pt-4">
                    @php
                        $branchOpeningTotal = (int) $branchesBusiness->sum('opening_stock') + (int) $unassignedOpeningStock;
                        $branchSalesTotal = (int) $branchesBusiness->sum('sales_count') + (int) $unassignedSales;
                        $branchClosingTotal = (int) $branchesBusiness->sum('closing_stock') + (int) $unassignedClosingStock;
                    @endphp
                    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <span class="font-semibold text-slate-900">Branch sales summary:</span>
                        Opening {{ number_format($branchOpeningTotal) }}
                        · Sales {{ number_format($branchSalesTotal) }}
                        · Closing {{ number_format($branchClosingTotal) }}
                    </div>
                    @if($selectedBranchDetail)
                        <div class="admin-prod-alert admin-prod-alert--warning mb-4">
                            <span class="font-semibold text-slate-800">{{ $selectedBranchDetail->branch->name }}</span>
                            <span class="block mt-1 text-sm">
                                Opening: {{ number_format($selectedBranchDetail->opening_stock) }}
                                · Sales: {{ number_format($selectedBranchDetail->sales_count) }}
                                · Closing: {{ number_format($selectedBranchDetail->closing_stock) }}
                            </span>
                        </div>
                    @endif

                    <div class="admin-prod-table-wrap overflow-x-auto rounded-xl">
                        <table class="min-w-[760px]">
                            <thead>
                                <tr>
                                    <th scope="col" class="admin-prod-th">Branch</th>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end">Opening Stock</th>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end">Sales</th>
                                    <th scope="col" class="admin-prod-th admin-prod-th--end">Closing Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branchesBusiness as $row)
                                    <tr class="@if(request('branch_id') == $row->id) bg-orange-50/40 @endif">
                                        <td class="font-medium text-[#232f3e]">{{ $row->name }}</td>
                                        <td class="text-right font-variant-numeric text-slate-700">{{ number_format($row->opening_stock) }}</td>
                                        <td class="text-right font-variant-numeric text-slate-700">{{ number_format($row->sales_count) }}</td>
                                        <td class="text-right font-variant-numeric text-slate-700">{{ number_format($row->closing_stock) }}</td>
                                    </tr>
                                @endforeach
                                @if($unassignedPurchases > 0)
                                    <tr class="text-slate-600">
                                        <td class="italic">No branch assigned</td>
                                        <td class="text-right">{{ number_format($unassignedOpeningStock) }}</td>
                                        <td class="text-right">{{ number_format($unassignedSales) }}</td>
                                        <td class="text-right">{{ number_format($unassignedClosingStock) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-t-2 border-slate-300 font-semibold text-slate-900">
                                    <td>Total</td>
                                    <td class="text-right font-variant-numeric">{{ number_format($branchOpeningTotal) }}</td>
                                    <td class="text-right font-variant-numeric">{{ number_format($branchSalesTotal) }}</td>
                                    <td class="text-right font-variant-numeric">{{ number_format($branchClosingTotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Branch Opening Stock is derived as sold + unsold units for that branch. Closing Stock is current unsold units. Sales is cumulative sold units.</p>
                </div>
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Sales overview (last 7 days)</h2>
            </div>
            <div class="admin-prod-form-body !pt-6">
                @php
                    $salesMax = max(1, (float) max(array_values($salesData)));
                @endphp
                <div class="h-64 flex gap-2 px-1 items-stretch">
                    @foreach($salesData as $date => $amount)
                        @php
                            $amt = (float) $amount;
                            $pct = $salesMax > 0 ? ($amt / $salesMax) * 100 : 0;
                            $barPct = $amt > 0 ? max($pct, 0.35) : 0;
                        @endphp
                        <div class="flex-1 flex flex-col min-w-0 h-full min-h-0 group">
                            <div class="flex-1 min-h-0 flex flex-col justify-end">
                                <div class="w-full rounded-t-md bg-gradient-to-t from-[#e07800] to-[#fa8900] opacity-85 group-hover:opacity-100 transition-opacity relative shadow-inner"
                                    style="height: {{ sprintf('%.4f', $barPct) }}%; min-height: {{ $amt > 0 ? '4px' : '2px' }};">
                                    <div
                                        class="absolute -top-9 left-1/2 -translate-x-1/2 bg-[#232f3e] text-white text-xs px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10 shadow-lg">
                                        {{ number_format($amt, 0) }} TZS
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] sm:text-xs text-slate-500 mt-2 text-center leading-tight shrink-0">
                                {{ \Carbon\Carbon::parse($date)->format('M j') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
