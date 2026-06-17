<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page w-full max-w-none">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
            <div>
                <p class="admin-prod-eyebrow">Management</p>
                <h1 class="admin-prod-title">Customer leads</h1>
                <p class="admin-prod-subtitle">Category and model requests submitted from the app (Record Sale → Lead) by agents and team leaders.</p>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden w-full mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Product leads trend</h2>
                <p class="admin-prod-form-hint">Animated line chart of top requested models in the selected period. Year is year-to-date (Jan 1 through today).</p>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <form method="GET" action="{{ route('admin.customer-needs.index') }}" class="flex flex-wrap gap-2">
                    <button type="submit" name="period" value="week" class="admin-prod-btn-ghost {{ ($selectedPeriod ?? '') === 'week' ? '!text-[#232f3e] !border-[#fa8900]/60 !bg-[#fa8900]/10' : '' }}">Week</button>
                    <button type="submit" name="period" value="month" class="admin-prod-btn-ghost {{ ($selectedPeriod ?? '') === 'month' ? '!text-[#232f3e] !border-[#fa8900]/60 !bg-[#fa8900]/10' : '' }}">Month</button>
                    <button type="submit" name="period" value="year" class="admin-prod-btn-ghost {{ ($selectedPeriod ?? '') === 'year' ? '!text-[#232f3e] !border-[#fa8900]/60 !bg-[#fa8900]/10' : '' }}">Year</button>
                </form>
                <div id="leadsLineChart" class="w-full h-[360px]"></div>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden w-full">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Leads</h2>
                <p class="admin-prod-form-hint">Full list of submitted leads.</p>
            </div>
            <div class="w-full overflow-x-auto p-4 sm:p-6">
                <table id="customerLeadsTable" class="js-datatable w-full min-w-[640px] text-sm text-left" data-datatable-order="0,desc">
                    <thead class="text-xs text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-3 pr-4">Submitted</th>
                            <th class="py-3 pr-4">Submitted by</th>
                            <th class="py-3 pr-4">Customer</th>
                            <th class="py-3 pr-4">Phone</th>
                            <th class="py-3 pr-4">Branch</th>
                            <th class="py-3 pr-4">Category</th>
                            <th class="py-3">Model</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($customerNeeds as $n)
                            <tr>
                                <td class="py-3 pr-4 whitespace-nowrap align-top">{{ $n->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 align-top break-words">{{ $n->teamLeader?->name ?? $n->agent?->name ?? '—' }}</td>
                                <td class="py-3 pr-4 align-top break-words">{{ $n->customer_name ?? '—' }}</td>
                                <td class="py-3 pr-4 align-top break-words">{{ $n->customer_phone ?? '—' }}</td>
                                <td class="py-3 pr-4 align-top break-words">{{ $n->branch?->name ?? '—' }}</td>
                                <td class="py-3 pr-4 align-top break-words">{{ $n->category?->name ?? '—' }}</td>
                                <td class="py-3 align-top break-words">{{ $n->product?->name ?? '—' }}</td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
            (function() {
                const chartRows = @json($chartRows ?? [['Date']]);

                google.charts.load('current', {
                    packages: ['corechart']
                });
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    const target = document.getElementById('leadsLineChart');
                    if (!target || !Array.isArray(chartRows) || chartRows.length < 2 || chartRows[0].length < 2) {
                        if (target) {
                            target.innerHTML = '<div class="h-full flex items-center justify-center text-slate-500 text-sm">No leads chart data for this filter.</div>';
                        }
                        return;
                    }

                    const data = new google.visualization.DataTable();
                    data.addColumn('string', chartRows[0][0]);
                    for (let i = 1; i < chartRows[0].length; i++) {
                        data.addColumn('number', chartRows[0][i]);
                    }
                    data.addRows(chartRows.slice(1));

                    const options = {
                        backgroundColor: 'transparent',
                        chartArea: {
                            left: 56,
                            top: 16,
                            width: '84%',
                            height: '74%'
                        },
                        legend: {
                            position: 'bottom'
                        },
                        hAxis: {
                            textStyle: {
                                color: '#64748b',
                                fontSize: 11
                            },
                            slantedText: true,
                            slantedTextAngle: 35
                        },
                        vAxis: {
                            minValue: 0,
                            textStyle: {
                                color: '#64748b',
                                fontSize: 11
                            }
                        },
                        curveType: 'function',
                        lineWidth: 3,
                        pointSize: 4,
                        animation: {
                            startup: true,
                            duration: 900,
                            easing: 'out'
                        }
                    };

                    const chart = new google.visualization.LineChart(target);
                    chart.draw(data, options);
                    window.addEventListener('resize', function() {
                        chart.draw(data, options);
                    }, {
                        passive: true
                    });
                }
            })();
        </script>
    @endpush
</x-admin-layout>
