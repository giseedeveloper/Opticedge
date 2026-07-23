<x-admin-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
            .admin-dash-section-head {
                padding: 1.1rem 1.5rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.65), rgba(241, 245, 249, 0.45));
                border-bottom: 1px solid rgba(255, 255, 255, 0.7);
                box-shadow: inset 0 -2px 8px rgba(148, 163, 184, 0.06);
            }

            .admin-dash-section-title {
                font-size: 1.125rem;
                font-weight: 700;
                color: #232f3e;
                letter-spacing: -0.02em;
            }

            .admin-dash-section-desc {
                font-size: 0.8125rem;
                color: rgb(100 116 139);
                margin-top: 0.2rem;
                line-height: 1.45;
            }

            .admin-dash-body {
                padding: 1.5rem;
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.4), rgba(255, 255, 255, 0.15));
            }

            .admin-dash-metric {
                position: relative;
                padding: 1rem 1.15rem 1rem 1.25rem;
                border-radius: 1rem;
                background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.82));
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow:
                    5px 7px 16px rgba(163, 177, 198, 0.18),
                    -3px -3px 12px rgba(255, 255, 255, 0.95),
                    inset 2px 2px 5px rgba(255, 255, 255, 0.85),
                    inset -1px -1px 4px rgba(148, 163, 184, 0.05);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .admin-dash-metric:hover {
                transform: translateY(-2px);
                box-shadow:
                    8px 10px 22px rgba(163, 177, 198, 0.22),
                    -4px -4px 14px rgba(255, 255, 255, 1),
                    inset 2px 2px 6px rgba(255, 255, 255, 0.9);
            }

            .admin-dash-metric::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0.65rem;
                bottom: 0.65rem;
                width: 3px;
                border-radius: 0 4px 4px 0;
                background: var(--dash-accent, #94a3b8);
                box-shadow: 2px 0 10px rgba(15, 23, 42, 0.08);
            }

            .admin-dash-metric-label {
                font-size: 0.8125rem;
                font-weight: 600;
                color: rgb(71 85 105);
            }

            .admin-dash-metric-value {
                font-size: 1.35rem;
                font-weight: 700;
                color: rgb(15 23 42);
                margin-top: 0.35rem;
                letter-spacing: -0.02em;
            }

            .admin-dash-metric-hint {
                font-size: 0.6875rem;
                color: rgb(100 116 139);
                margin-top: 0.4rem;
                line-height: 1.4;
            }

            .admin-dash-metric--amber {
                --dash-accent: #f59e0b;
            }

            .admin-dash-metric--blue {
                --dash-accent: #3b82f6;
            }

            .admin-dash-metric--emerald {
                --dash-accent: #10b981;
            }

            .admin-dash-metric--violet {
                --dash-accent: #8b5cf6;
            }

            .admin-dash-metric--slate {
                --dash-accent: #64748b;
            }

            .admin-dash-metric--green {
                --dash-accent: #22c55e;
            }

            .admin-dash-metric--red {
                --dash-accent: #ef4444;
            }

            .admin-dash-metric--indigo {
                --dash-accent: #6366f1;
            }

            .admin-dash-metric--teal {
                --dash-accent: #14b8a6;
            }

            .admin-dash-divider {
                margin-top: 1.25rem;
                padding-top: 1.25rem;
                border-top: 1px solid rgba(255, 255, 255, 0.75);
                box-shadow: inset 0 1px 0 rgba(148, 163, 184, 0.08);
            }

            .admin-dash-cash-card {
                padding: 1.1rem 1.2rem;
                border-radius: 1.1rem;
                background: linear-gradient(150deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.88));
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow:
                    6px 8px 18px rgba(163, 177, 198, 0.16),
                    -3px -4px 12px rgba(255, 255, 255, 0.92),
                    inset 1px 1px 4px rgba(255, 255, 255, 0.85);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .admin-dash-cash-card:hover {
                transform: translateY(-2px);
                box-shadow:
                    8px 12px 24px rgba(163, 177, 198, 0.2),
                    -4px -4px 14px rgba(255, 255, 255, 1),
                    inset 1px 1px 4px rgba(255, 255, 255, 0.9);
            }

            .admin-dash-cash-card--mobile {
                --cash-tint: #3b82f6;
            }

            .admin-dash-cash-card--bank {
                --cash-tint: #10b981;
            }

            .admin-dash-cash-card--other {
                --cash-tint: #f59e0b;
            }

            .admin-dash-cash-card::before {
                content: '';
                display: block;
                height: 3px;
                border-radius: 2px;
                margin: -0.35rem -0.5rem 0.85rem -0.5rem;
                background: linear-gradient(90deg, var(--cash-tint, #94a3b8), rgba(255, 255, 255, 0.65));
                opacity: 0.9;
            }

            .admin-dash-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.2rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                background: rgba(255, 255, 255, 0.85);
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow: 2px 2px 6px rgba(163, 177, 198, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.9);
                color: var(--cash-tint, #475569);
            }

            .admin-dash-cash-footer {
                margin-top: 1.25rem;
                padding: 1rem 1.25rem;
                border-radius: 1rem;
                background: linear-gradient(135deg, rgba(250, 137, 0, 0.12), rgba(255, 255, 255, 0.75));
                border: 1px solid rgba(250, 137, 0, 0.22);
                box-shadow:
                    inset 2px 2px 6px rgba(255, 255, 255, 0.75),
                    4px 6px 14px rgba(250, 137, 0, 0.08);
            }

            .admin-dash-filter-input {
                border-radius: 0.75rem;
                border: 1px solid rgba(255, 255, 255, 0.9);
                background: linear-gradient(165deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.95));
                box-shadow:
                    inset 3px 3px 8px rgba(163, 177, 198, 0.12),
                    inset -2px -2px 6px rgba(255, 255, 255, 0.9),
                    2px 2px 8px rgba(163, 177, 198, 0.08);
                padding: 0.45rem 0.65rem;
                font-size: 0.8125rem;
                color: rgb(15 23 42);
            }

            .admin-dash-filter-input:focus {
                outline: 2px solid rgba(250, 137, 0, 0.35);
                outline-offset: 1px;
            }

            .admin-dash-btn-primary {
                border-radius: 0.75rem;
                padding: 0.5rem 1.1rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: white;
                background: linear-gradient(145deg, #fa8900, #e07800);
                border: 1px solid rgba(255, 255, 255, 0.35);
                box-shadow:
                    4px 5px 14px rgba(250, 137, 0, 0.35),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.35);
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .admin-dash-btn-primary:hover {
                transform: translateY(-1px);
                box-shadow:
                    6px 8px 18px rgba(250, 137, 0, 0.4),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.4);
            }

            .admin-dash-chart-well {
                margin-top: 0.5rem;
                padding: 1.25rem;
                border-radius: 1.25rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.55), rgba(241, 245, 249, 0.35));
                border: 1px solid rgba(255, 255, 255, 0.65);
                box-shadow:
                    inset 3px 4px 12px rgba(163, 177, 198, 0.1),
                    inset -2px -2px 8px rgba(255, 255, 255, 0.85);
            }

            /* Pro section headers (chart + tables) */
            .admin-dash-pro-head {
                display: flex;
                align-items: flex-start;
                gap: 1rem;
                min-width: 0;
            }

            .admin-dash-pro-icon {
                flex-shrink: 0;
                width: 2.75rem;
                height: 2.75rem;
                border-radius: 0.85rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow:
                    3px 5px 14px rgba(163, 177, 198, 0.18),
                    inset 1px 1px 3px rgba(255, 255, 255, 0.9);
            }

            .admin-dash-pro-icon--chart {
                background: linear-gradient(145deg, rgba(250, 137, 0, 0.2), rgba(255, 255, 255, 0.95));
                border-color: rgba(250, 137, 0, 0.25);
                color: #c2410c;
            }

            .admin-dash-pro-icon--orders {
                background: linear-gradient(145deg, rgba(71, 85, 105, 0.12), rgba(255, 255, 255, 0.95));
                border-color: rgba(148, 163, 184, 0.35);
                color: #475569;
            }

            .admin-dash-pro-icon svg {
                width: 1.35rem;
                height: 1.35rem;
            }

            .admin-dash-pro-eyebrow {
                font-size: 0.625rem;
                font-weight: 800;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #94a3b8;
                margin-bottom: 0.3rem;
            }

            .admin-dash-pro-title {
                font-size: 1.2rem;
                font-weight: 800;
                color: #232f3e;
                letter-spacing: -0.03em;
                line-height: 1.2;
                margin: 0;
            }

            .admin-dash-pro-meta {
                font-size: 0.8125rem;
                color: #64748b;
                margin-top: 0.35rem;
                font-variant-numeric: tabular-nums;
                line-height: 1.4;
            }

            .admin-dash-filter-bar {
                padding: 0.65rem 0.85rem;
                border-radius: 0.85rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.75), rgba(248, 250, 252, 0.65));
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    inset 2px 2px 8px rgba(148, 163, 184, 0.1),
                    2px 3px 10px rgba(163, 177, 198, 0.08);
            }

            .admin-dash-table-wrap {
                border-radius: 1rem;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.75);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.9),
                    3px 4px 14px rgba(163, 177, 198, 0.1);
            }

            .admin-dash-table-wrap thead tr {
                background: transparent;
            }

            .admin-dash-table-wrap thead th.admin-dash-th {
                background: linear-gradient(180deg, #e8ecf2 0%, #dce2ea 45%, #cfd6e0 100%);
                color: #475569;
                font-size: 0.6875rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                padding: 0.95rem 1.15rem;
                border-bottom: 1px solid #aeb9c9;
                border-right: 1px solid rgba(255, 255, 255, 0.5);
                text-align: left;
                vertical-align: middle;
                white-space: nowrap;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
            }

            .admin-dash-table-wrap thead th.admin-dash-th:last-child {
                border-right: none;
            }

            .admin-dash-table-wrap thead th.admin-dash-th--end {
                text-align: right;
            }

            .admin-dash-table-wrap tbody tr {
                transition: background 0.15s ease;
            }

            .admin-dash-table-wrap tbody tr:hover {
                background: rgba(255, 255, 255, 0.72);
            }

            .admin-dash-link {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.75rem;
                border-radius: 0.65rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #232f3e;
                background: rgba(255, 255, 255, 0.75);
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow: 2px 3px 8px rgba(163, 177, 198, 0.12);
                transition: color 0.15s, box-shadow 0.15s, transform 0.15s;
            }

            .admin-dash-link:hover {
                color: #fa8900;
                transform: translateY(-1px);
                box-shadow: 3px 5px 12px rgba(250, 137, 0, 0.15);
            }

            .admin-dash-empty {
                padding: 2.5rem 1rem;
                text-align: center;
                color: rgb(100 116 139);
                font-size: 0.875rem;
                border-radius: 1rem;
                background: rgba(255, 255, 255, 0.4);
                border: 1px dashed rgba(148, 163, 184, 0.35);
            }
        </style>
    @endpush

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto">
        <h1 class="text-2xl font-bold text-[#232f3e] tracking-tight">Dashboard</h1>
        <p class="mt-2 text-slate-600 text-sm sm:text-base">Overview of your store performance.</p>

        <!-- Stats Grid -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Customers -->
            <a href="{{ route('admin.customers.index') }}"
                class="group admin-clay-panel-interactive p-6 transition-all relative overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div
                        class="p-3 bg-blue-50 text-blue-600 rounded-full group-hover:bg-[#fa8900] group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Customers</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($totalCustomers) }}</p>
                    </div>
                </div>
            </a>

            <!-- Orders -->
            <a href="{{ route('admin.orders.index') }}"
                class="group admin-clay-panel-interactive p-6 transition-all relative overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div
                        class="p-3 bg-purple-50 text-purple-600 rounded-full group-hover:bg-[#fa8900] group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Orders</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($totalOrders) }}</p>
                    </div>
                </div>
            </a>

            <!-- Products -->
            <a href="{{ route('admin.products.index') }}"
                class="group admin-clay-panel-interactive p-6 transition-all relative overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div
                        class="p-3 bg-green-50 text-green-600 rounded-full group-hover:bg-[#fa8900] group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Products</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($totalProducts) }}</p>
                    </div>
                </div>
            </a>

            @isset($financialMetrics)
            <!-- Agent aging products -->
            <a href="{{ route('admin.dashboard', ['open' => 'agent-aging-assets']) }}"
                class="group admin-clay-panel-interactive p-6 transition-all relative overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div
                        class="p-3 bg-teal-50 text-teal-600 rounded-full group-hover:bg-[#fa8900] group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Agent aging products</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($agentAgingAssetsCount ?? 0) }}</p>
                    </div>
                </div>
            </a>
            @endisset
        </div>

        <!-- Disbursement wallet -->
        @isset($walletBalance)
        <div class="mt-6">
            <a href="{{ route('admin.payout.index') }}"
                class="group admin-clay-panel-interactive p-6 transition-all relative overflow-hidden flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 relative z-10">
                    <div class="p-3 bg-amber-50 text-[#fa8900] rounded-full group-hover:bg-[#fa8900] group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 12a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 7.5m18 4.5v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V7.5m18 4.5h-3.75a1.5 1.5 0 100 3H21M3 7.5A2.25 2.25 0 015.25 5.25h12A2.25 2.25 0 0119.5 7.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Disbursement wallet</p>
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($walletBalance, 0) }} TZS</p>
                        <p class="text-xs text-slate-400 mt-0.5">Funds agent commission payouts — click to top up.</p>
                    </div>
                </div>
                <span class="shrink-0 text-xs font-semibold text-[#fa8900] opacity-0 group-hover:opacity-100 transition-opacity">Go to Pay out →</span>
            </a>
        </div>
        @endisset

        <!-- Sales Metrics Cards -->
        @if(isset($salesMetrics))
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Today Sales -->
            <div class="admin-clay-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-1">Mauzo ya Leo</p>
                <p class="text-2xl font-bold text-slate-900 mb-2">{{ number_format($salesMetrics['today']['sales'], 0) }} TZS</p>
                @if($salesMetrics['today']['percentage_change'] !== null)
                    <div class="flex items-center gap-1">
                        @if($salesMetrics['today']['is_increase'])
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-green-600">{{ number_format(abs($salesMetrics['today']['percentage_change']), 1) }}%</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-red-600">{{ number_format(abs($salesMetrics['today']['percentage_change']), 1) }}%</span>
                        @endif
                        <span class="text-xs text-slate-500">vs jana</span>
                    </div>
                @else
                    <span class="text-xs text-slate-500">Hakuna data ya jana</span>
                @endif
            </div>

            <!-- WTD Sales -->
            <div class="admin-clay-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-1">WTD (Weekly To Date)</p>
                <p class="text-2xl font-bold text-slate-900 mb-2">{{ number_format($salesMetrics['wtd']['sales'], 0) }} TZS</p>
                @if($salesMetrics['wtd']['percentage_change'] !== null)
                    <div class="flex items-center gap-1">
                        @if($salesMetrics['wtd']['is_increase'])
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-green-600">{{ number_format(abs($salesMetrics['wtd']['percentage_change']), 1) }}%</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-red-600">{{ number_format(abs($salesMetrics['wtd']['percentage_change']), 1) }}%</span>
                        @endif
                        <span class="text-xs text-slate-500">vs wiki iliyopita</span>
                    </div>
                @else
                    <span class="text-xs text-slate-500">Hakuna data ya wiki iliyopita</span>
                @endif
            </div>

            <!-- MTD Sales -->
            <div class="admin-clay-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-50 text-green-600 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-1">MTD (Monthly To Date)</p>
                <p class="text-2xl font-bold text-slate-900 mb-2">{{ number_format($salesMetrics['mtd']['sales'], 0) }} TZS</p>
                @if($salesMetrics['mtd']['percentage_change'] !== null)
                    <div class="flex items-center gap-1">
                        @if($salesMetrics['mtd']['is_increase'])
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-green-600">{{ number_format(abs($salesMetrics['mtd']['percentage_change']), 1) }}%</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-red-600">{{ number_format(abs($salesMetrics['mtd']['percentage_change']), 1) }}%</span>
                        @endif
                        <span class="text-xs text-slate-500">vs mwezi uliopita</span>
                    </div>
                @else
                    <span class="text-xs text-slate-500">Hakuna data ya mwezi uliopita</span>
                @endif
            </div>

            <!-- YTD Sales -->
            <div class="admin-clay-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-1">YTD (Yearly To Date)</p>
                <p class="text-2xl font-bold text-slate-900 mb-2">{{ number_format($salesMetrics['ytd']['sales'], 0) }} TZS</p>
                @if($salesMetrics['ytd']['percentage_change'] !== null)
                    <div class="flex items-center gap-1">
                        @if($salesMetrics['ytd']['is_increase'])
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-green-600">{{ number_format(abs($salesMetrics['ytd']['percentage_change']), 1) }}%</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-red-600">{{ number_format(abs($salesMetrics['ytd']['percentage_change']), 1) }}%</span>
                        @endif
                        <span class="text-xs text-slate-500">vs mwaka uliopita</span>
                    </div>
                @else
                    <span class="text-xs text-slate-500">Hakuna data ya mwaka uliopita</span>
                @endif
            </div>
        </div>
        @endif

        <!-- Financial Metrics -->
        @if(isset($financialMetrics))
        <div class="mt-8 admin-clay-panel overflow-hidden" x-data="{ cashInHandModalOpen: false, overduePurchasesModalOpen: false, manualPayablesModalOpen: false, receivablesModalOpen: false, agentAgingAssetsModalOpen: @js(request('open') === 'agent-aging-assets') }">
            <div class="admin-dash-section-head">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="admin-dash-section-title">Financial Summary</h3>
                        <p class="admin-dash-section-desc">
                            Payables, receivables, stock value, and profit overview.
                            <span class="font-semibold text-slate-700">
                                ({{ $financialStartDate->format('M j, Y') }} → {{ $financialEndDate->format('M j, Y') }})
                            </span>
                        </p>
                    </div>
                    <form method="GET" action="{{ route('admin.dashboard') }}"
                        class="admin-dash-filter-bar flex flex-wrap gap-3 items-end shrink-0"
                        aria-label="Filter financial summary date range">
                        <input type="hidden" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                        <input type="hidden" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                        @if(request()->filled('open'))
                            <input type="hidden" name="open" value="{{ request('open') }}">
                        @endif
                        <div>
                            <label for="financial_date_range"
                                class="block text-[0.6rem] font-extrabold uppercase tracking-widest text-slate-500 mb-1.5">Financial range</label>
                            <input type="text" id="financial_date_range" autocomplete="off"
                                class="admin-dash-filter-input w-full min-w-[17rem]"
                                value="{{ $financialStartDate->format('Y-m-d') }} to {{ $financialEndDate->format('Y-m-d') }}">
                            <input type="hidden" name="financial_start_date" id="financial_start_date"
                                value="{{ request('financial_start_date', $financialStartDate->format('Y-m-d')) }}">
                            <input type="hidden" name="financial_end_date" id="financial_end_date"
                                value="{{ request('financial_end_date', $financialEndDate->format('Y-m-d')) }}">
                        </div>
                        <button type="submit" class="admin-dash-btn-primary self-end">
                            Apply range
                        </button>
                    </form>
                </div>
            </div>
                <div class="admin-dash-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button type="button"
                        class="admin-dash-metric admin-dash-metric--amber text-left w-full cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fa8900]"
                        @click="overduePurchasesModalOpen = true">
                        <p class="admin-dash-metric-label">Payables</p>
                        <p class="admin-dash-metric-value">{{ number_format($financialMetrics['payables'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Total pending (not paid) from purchases</p>
                    </button>
                    <button type="button"
                        class="admin-dash-metric admin-dash-metric--blue text-left w-full cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3b82f6]"
                        @click="receivablesModalOpen = true">
                        <p class="admin-dash-metric-label">Receivables</p>
                        <p class="admin-dash-metric-value">{{ number_format($financialMetrics['receivables'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Pending from Distribution Sales + Agent Credit</p>
                    </button>
                    <div class="admin-dash-metric admin-dash-metric--emerald">
                        <p class="admin-dash-metric-label">Stock in Hand Value</p>
                        <p class="admin-dash-metric-value">{{ number_format($financialMetrics['stock_in_hand_value'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Total value of our stock</p>
                    </div>
                    <button type="button"
                        class="admin-dash-metric admin-dash-metric--violet text-left w-full cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fa8900]"
                        @click="cashInHandModalOpen = true">
                        <p class="admin-dash-metric-label">Cash in Hand</p>
                        <p class="admin-dash-metric-value">
                            {{ number_format(isset($paymentOptions) ? $paymentOptions->sum('balance') : $financialMetrics['cash_in_hand'], 0) }} TZS
                        </p>
                        <p class="admin-dash-metric-hint">Total amount in all payment options</p>
                    </button>
                </div>
                <div class="admin-dash-divider grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="admin-dash-metric admin-dash-metric--slate">
                        <p class="admin-dash-metric-label">Total Value</p>
                        <p class="admin-dash-metric-value">{{ number_format($financialMetrics['total_value'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Receivables + Stock in Hand + Cash in Hand</p>
                    </div>
                    <div class="admin-dash-metric admin-dash-metric--green">
                        <p class="admin-dash-metric-label">Gross Profit</p>
                        <p class="admin-dash-metric-value text-emerald-800">{{ number_format($financialMetrics['gross_profit'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Distribution + Agent sales + Agent credit margin (sell − buy)</p>
                    </div>
                    <div class="admin-dash-metric admin-dash-metric--red">
                        <p class="admin-dash-metric-label">Total Expenses</p>
                        <p class="admin-dash-metric-value text-red-800">{{ number_format($financialMetrics['total_expenses'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">From Expenses section</p>
                    </div>
                    <div
                        class="admin-dash-metric {{ $financialMetrics['net_profit'] >= 0 ? 'admin-dash-metric--green' : 'admin-dash-metric--red' }}">
                        <p class="admin-dash-metric-label">Net Profit</p>
                        <p
                            class="admin-dash-metric-value {{ $financialMetrics['net_profit'] >= 0 ? 'text-emerald-800' : 'text-red-800' }}">
                            {{ number_format($financialMetrics['net_profit'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Gross profit − Total expenses</p>
                    </div>
                    <div class="admin-dash-metric admin-dash-metric--indigo">
                        <p class="admin-dash-metric-label">Total Purchase Buy Price</p>
                        <p class="admin-dash-metric-value">{{ number_format($financialMetrics['total_purchase_buy_price'], 0) }} TZS</p>
                        <p class="admin-dash-metric-hint">Total buy price of all purchases</p>
                    </div>
                </div>
            </div>

            <!-- Cash in Hand Modal -->
            @if(isset($paymentOptions) && $paymentOptions->count() > 0)
            <div x-show="cashInHandModalOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="cashInHandModalOpen = false">
                <div
                    class="w-full max-w-5xl max-h-[80vh] overflow-y-auto rounded-3xl border border-white/80 bg-gradient-to-br from-white/98 via-slate-50/95 to-slate-100/90 shadow-[18px_22px_45px_rgba(15,23,42,0.32),-6px_-8px_24px_rgba(255,255,255,0.95)]">
                    <div class="admin-dash-section-head flex items-start justify-between">
                        <div>
                            <h3 class="admin-dash-section-title">Cash in Hand</h3>
                            <p class="admin-dash-section-desc">Payment options and their current balances.</p>
                        </div>
                        <button type="button" class="ml-4 rounded-full p-1.5 text-slate-500 hover:text-slate-800 hover:bg-white/80"
                            @click="cashInHandModalOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="admin-dash-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($paymentOptions as $option)
                                @php
                                    $currentBalance = $option->balance ?? 0;
                                    $openingBalance = $option->opening_balance ?? 0;
                                    $difference = $currentBalance - $openingBalance;
                                    $percentageChange = $openingBalance > 0 ? (($difference / $openingBalance) * 100) : 0;
                                    $isIncrease = $difference > 0;
                                    $isDecrease = $difference < 0;
                                    $cashCardClass =
                                        $option->type === 'mobile'
                                            ? 'admin-dash-cash-card--mobile'
                                            : ($option->type === 'bank'
                                                ? 'admin-dash-cash-card--bank'
                                                : 'admin-dash-cash-card--other');
                                @endphp
                                <div class="admin-dash-cash-card {{ $cashCardClass }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-700 leading-snug">{{ $option->name }}</p>
                                        <span class="admin-dash-pill shrink-0">{{ ucfirst($option->type) }}</span>
                                    </div>
                                    <p class="text-2xl font-bold text-[#232f3e] mt-2 tracking-tight">
                                        {{ number_format($currentBalance, 0) }}
                                        <span class="text-sm font-semibold text-slate-500">TZS</span>
                                    </p>
                                    <div
                                        class="mt-3 pt-3 space-y-1.5 border-t border-white/80 shadow-[inset_0_1px_0_rgba(148,163,184,0.08)]">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-xs font-medium text-slate-500">Opening balance</p>
                                            <p class="text-xs font-bold text-slate-800">{{ number_format($openingBalance, 0) }} TZS</p>
                                        </div>
                                        @if($difference != 0)
                                            <div class="flex items-center gap-1 flex-wrap">
                                                @if($isIncrease)
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                    </svg>
                                                    <span class="text-xs font-semibold text-emerald-700">
                                                        Imepanda {{ number_format(abs($difference), 0) }} TZS
                                                        ({{ number_format(abs($percentageChange), 1) }}%)
                                                    </span>
                                                @elseif($isDecrease)
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-3.5 h-3.5 text-red-600 shrink-0"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                                    </svg>
                                                    <span class="text-xs font-semibold text-red-700">
                                                        Imeshuka {{ number_format(abs($difference), 0) }} TZS
                                                        ({{ number_format(abs($percentageChange), 1) }}%)
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-500">Hakuna mabadiliko</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="admin-dash-cash-footer mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">Total Cash in Hand</p>
                            <p class="text-xl sm:text-2xl font-bold text-[#232f3e] tracking-tight">
                                {{ number_format($paymentOptions->sum('balance'), 0) }}
                                <span class="text-sm font-semibold text-slate-500">TZS</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Agent aging assets -->
            <div x-show="agentAgingAssetsModalOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="agentAgingAssetsModalOpen = false">
                <div
                    class="w-full max-w-6xl max-h-[80vh] overflow-y-auto rounded-3xl border border-white/80 bg-gradient-to-br from-white/98 via-slate-50/95 to-slate-100/90 shadow-[18px_22px_45px_rgba(15,23,42,0.32),-6px_-8px_24px_rgba(255,255,255,0.95)]">
                    <div class="admin-dash-section-head flex items-center justify-between">
                        <div>
                            <h3 class="admin-dash-section-title">Agent aging assets</h3>
                            <p class="admin-dash-section-desc">Assigned devices that are still not sold, oldest assignments first.</p>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap justify-end">
                            <button type="button"
                                class="admin-dash-link text-xs shrink-0 border-0 bg-transparent cursor-pointer p-0"
                                @click="agentAgingAssetsModalOpen = false; manualPayablesModalOpen = true">
                                Manual payables
                            </button>
                            <a href="{{ route('admin.stock.imei-search') }}" class="admin-dash-link text-xs shrink-0">
                                View all
                            </a>
                            <button type="button" class="ml-2 rounded-full p-1.5 text-slate-500 hover:text-slate-800 hover:bg-white/80"
                                @click="agentAgingAssetsModalOpen = false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="admin-dash-body">
                        <div class="admin-dash-table-wrap">
                            <table id="agent-aging-assets-table" class="w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        <th class="admin-dash-th">Model</th>
                                        <th class="admin-dash-th">IMEI</th>
                                        <th class="admin-dash-th">Assigned agent</th>
                                        <th class="admin-dash-th">Date assigned</th>
                                        <th class="admin-dash-th">Aging</th>
                                        <th class="admin-dash-th admin-dash-th--end">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100/80 bg-white/40">
                                    @forelse($agentAgingAssets ?? [] as $asset)
                                        @php
                                            $assignment = $asset->agentProductListAssignment;
                                            $assignedAt = $assignment?->created_at;
                                            $diffDays = $assignedAt ? (int) floor($assignedAt->floatDiffInRealDays(now())) : 0;
                                            if ($diffDays < 7) {
                                                $agingLabel = $diffDays . ' day' . ($diffDays === 1 ? '' : 's') . '+';
                                            } elseif ($diffDays < 30) {
                                                $weeks = floor($diffDays / 7);
                                                $agingLabel = $weeks . ' week' . ($weeks === 1 ? '' : 's') . '+';
                                            } else {
                                                $months = floor($diffDays / 30);
                                                $agingLabel = $months . ' month' . ($months === 1 ? '' : 's') . '+';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-[#232f3e]">
                                                {{ $asset->model ?: ($asset->product?->name ?? '—') }}
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700 font-mono">
                                                {{ $asset->imei_number ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                {{ $assignment?->agent?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                {{ $assignedAt ? $assignedAt->format('Y-m-d') : '—' }}
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                                    {{ $agingLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <a href="{{ route('admin.stock.imei-item', $asset) }}"
                                                   class="admin-dash-link text-xs py-1.5 px-3">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                No aging assets found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Purchases Modal -->
            <div x-show="overduePurchasesModalOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="overduePurchasesModalOpen = false">
                <div
                    class="w-full max-w-6xl max-h-[80vh] overflow-y-auto rounded-3xl border border-white/80 bg-gradient-to-br from-white/98 via-slate-50/95 to-slate-100/90 shadow-[18px_22px_45px_rgba(15,23,42,0.32),-6px_-8px_24px_rgba(255,255,255,0.95)]">
                    <div class="admin-dash-section-head flex items-center justify-between">
                        <div>
                            <h3 class="admin-dash-section-title">Overdue Purchases</h3>
                            <p class="admin-dash-section-desc">Purchases not fully paid yet, oldest first.</p>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap justify-end">
                            <button type="button"
                                class="admin-dash-link text-xs shrink-0 border-0 bg-transparent cursor-pointer p-0"
                                @click="overduePurchasesModalOpen = false; manualPayablesModalOpen = true">
                                Manual payables
                            </button>
                            <a href="{{ route('admin.stock.purchases') }}" class="admin-dash-link text-xs shrink-0">
                                View all
                            </a>
                            <button type="button" class="ml-2 rounded-full p-1.5 text-slate-500 hover:text-slate-800 hover:bg-white/80"
                                @click="overduePurchasesModalOpen = false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="admin-dash-body">
                        <div class="admin-dash-table-wrap">
                            <table id="overdue-purchases-table" class="w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        <th class="admin-dash-th">Invoice</th>
                                        <th class="admin-dash-th">Vendor</th>
                                        <th class="admin-dash-th">Branch</th>
                                        <th class="admin-dash-th">Outstanding</th>
                                        <th class="admin-dash-th">Aging</th>
                                        <th class="admin-dash-th admin-dash-th--end">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100/80 bg-white/40">
                                    @forelse($overduePurchases ?? [] as $purchase)
                                        @php
                                            $total = (float) ($purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price));
                                            $paid = (float) ($purchase->paid_amount ?? 0);
                                            $outstanding = max(0, $total - $paid);
                                            $created = $purchase->date ? \Carbon\Carbon::parse($purchase->date) : $purchase->created_at;
                                            $diffDays = $created ? (int) floor($created->floatDiffInRealDays(now())) : 0;
                                            if ($diffDays < 7) {
                                                $agingLabel = $diffDays . ' day' . ($diffDays === 1 ? '' : 's') . '+';
                                            } elseif ($diffDays < 30) {
                                                $weeks = floor($diffDays / 7);
                                                $agingLabel = $weeks . ' week' . ($weeks === 1 ? '' : 's') . '+';
                                            } else {
                                                $months = floor($diffDays / 30);
                                                $agingLabel = $months . ' month' . ($months === 1 ? '' : 's') . '+';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-[#232f3e]">
                                                {{ $purchase->name ?? ('Purchase #' . $purchase->id) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                {{ $purchase->distributor_name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-700">
                                                {{ $purchase->branch?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 font-bold text-amber-800">
                                                {{ number_format($outstanding, 0) }} TZS
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                                    {{ $agingLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <a href="{{ route('admin.stock.edit-purchase', $purchase->id) }}"
                                                   class="admin-dash-link text-xs py-1.5 px-3">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                No overdue purchases found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution receivables (dealers / distributors) -->
            <div x-show="receivablesModalOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="receivablesModalOpen = false">
                <div
                    class="w-full max-w-5xl max-h-[80vh] overflow-y-auto rounded-3xl border border-white/80 bg-gradient-to-br from-white/98 via-slate-50/95 to-slate-100/90 shadow-[18px_22px_45px_rgba(15,23,42,0.32),-6px_-8px_24px_rgba(255,255,255,0.95)]">
                    <div class="admin-dash-section-head flex items-center justify-between">
                        <div>
                            <h3 class="admin-dash-section-title">Receivables — dealers</h3>
                            <p class="admin-dash-section-desc">Distribution receivables plus agent credit receivables.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.stock.distribution') }}" class="admin-dash-link text-xs shrink-0">
                                Distribution sales
                            </a>
                            <button type="button" class="ml-2 rounded-full p-1.5 text-slate-500 hover:text-slate-800 hover:bg-white/80"
                                @click="receivablesModalOpen = false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="admin-dash-body">
                        @php
                            $recvRows = collect($distributorReceivables ?? []);
                            $recvBilled = (float) $recvRows->sum('total_billed');
                            $recvPaid = (float) $recvRows->sum('total_paid');
                            $recvOutstanding = (float) $recvRows->sum('outstanding');
                            $agentCreditRecv = $agentCreditReceivables ?? ['credits' => 0, 'total_credit' => 0, 'total_paid' => 0, 'outstanding' => 0];
                        @endphp
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3">
                                <p class="text-xs uppercase font-semibold text-blue-700">Distribution receivables</p>
                                <p class="text-lg font-bold text-blue-900 mt-1">{{ number_format($recvOutstanding, 0) }} TZS</p>
                            </div>
                            <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
                                <p class="text-xs uppercase font-semibold text-amber-700">Agent credit receivables</p>
                                <p class="text-lg font-bold text-amber-900 mt-1">{{ number_format((float) ($agentCreditRecv['outstanding'] ?? 0), 0) }} TZS</p>
                                <p class="text-[11px] text-amber-800 mt-1">Credits: {{ number_format((int) ($agentCreditRecv['credits'] ?? 0)) }}</p>
                            </div>
                        </div>
                        <div class="admin-dash-table-wrap">
                            <table class="w-full text-left border-collapse" aria-label="Dealer receivables">
                                <thead>
                                    <tr>
                                        <th class="admin-dash-th">Dealer</th>
                                        <th class="admin-dash-th">Total billed</th>
                                        <th class="admin-dash-th">Paid</th>
                                        <th class="admin-dash-th">Remaining</th>
                                        <th class="admin-dash-th">Aging</th>
                                        <th class="admin-dash-th admin-dash-th--end">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100/80 bg-white/40">
                                    @forelse($recvRows as $row)
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-[#232f3e]">{{ $row['dealer_name'] }}</td>
                                            <td class="px-4 py-2.5 text-slate-700 font-variant-numeric">{{ number_format($row['total_billed'], 0) }} TZS</td>
                                            <td class="px-4 py-2.5 text-slate-700 font-variant-numeric">{{ number_format($row['total_paid'], 0) }} TZS</td>
                                            <td class="px-4 py-2.5 font-bold text-blue-800 font-variant-numeric">{{ number_format($row['outstanding'], 0) }} TZS</td>
                                            <td class="px-4 py-2.5">
                                                @if(!empty($row['aging_label']))
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                                        {{ $row['aging_label'] }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                @if(!empty($row['dealer_id']))
                                                    <a href="{{ route('admin.dealers.show', $row['dealer_id']) }}" class="admin-dash-link text-xs py-1.5 px-3">Dealer</a>
                                                @else
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                No distribution sales found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($recvRows->isNotEmpty())
                                    <tfoot>
                                        <tr class="bg-slate-50/90 font-semibold text-[#232f3e]">
                                            <td class="px-4 py-3">Totals</td>
                                            <td class="px-4 py-3 font-variant-numeric">{{ number_format($recvBilled, 0) }} TZS</td>
                                            <td class="px-4 py-3 font-variant-numeric">{{ number_format($recvPaid, 0) }} TZS</td>
                                            <td class="px-4 py-3 text-blue-900 font-variant-numeric">{{ number_format($recvOutstanding, 0) }} TZS</td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3"></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        <p class="mt-4 text-xs text-slate-500">The Receivables figure on the dashboard is <strong>Distribution remaining + Agent Credit pending</strong>.</p>
                    </div>
                </div>
            </div>

            <!-- Manual payables (other obligations) -->
            <div x-show="manualPayablesModalOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click.self="manualPayablesModalOpen = false">
                <div
                    class="w-full max-w-4xl max-h-[80vh] overflow-y-auto rounded-3xl border border-white/80 bg-gradient-to-br from-white/98 via-slate-50/95 to-slate-100/90 shadow-[18px_22px_45px_rgba(15,23,42,0.32),-6px_-8px_24px_rgba(255,255,255,0.95)]">
                    <div class="admin-dash-section-head flex items-center justify-between">
                        <div>
                            <h3 class="admin-dash-section-title">Manual payables</h3>
                            <p class="admin-dash-section-desc">Other recorded payables (not purchase invoices).</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.stock.payables') }}" class="admin-dash-link text-xs shrink-0">
                                View all
                            </a>
                            <button type="button" class="ml-2 rounded-full p-1.5 text-slate-500 hover:text-slate-800 hover:bg-white/80"
                                @click="manualPayablesModalOpen = false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="admin-dash-body">
                        <div class="admin-dash-table-wrap">
                            <table id="overdue-payables-table" class="w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        <th class="admin-dash-th">Item</th>
                                        <th class="admin-dash-th">Amount</th>
                                        <th class="admin-dash-th">Aging</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-slate-100/80 bg-white/40">
                                    @forelse($overduePayables ?? [] as $payable)
                                        @php
                                            $created = $payable->date ? \Carbon\Carbon::parse($payable->date) : $payable->created_at;
                                            $diffDays = $created ? (int) floor($created->floatDiffInRealDays(now())) : 0;
                                            if ($diffDays < 7) {
                                                $agingLabel = $diffDays . ' day' . ($diffDays === 1 ? '' : 's') . '+';
                                            } elseif ($diffDays < 30) {
                                                $weeks = floor($diffDays / 7);
                                                $agingLabel = $weeks . ' week' . ($weeks === 1 ? '' : 's') . '+';
                                            } else {
                                                $months = floor($diffDays / 30);
                                                $agingLabel = $months . ' month' . ($months === 1 ? '' : 's') . '+';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-[#232f3e]">
                                                {{ $payable->item_name ?? 'Payable #' . $payable->id }}
                                            </td>
                                            <td class="px-4 py-2.5 font-bold text-amber-800">
                                                {{ number_format($payable->amount ?? 0, 0) }} TZS
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                                    {{ $agingLabel }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                No overdue payables found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Top Selling Products Chart -->
        <div class="mt-8 admin-clay-panel overflow-hidden">
            <div
                class="admin-dash-section-head flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="admin-dash-pro-head flex-1 min-w-0">
                    <div class="admin-dash-pro-icon admin-dash-pro-icon--chart" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="admin-dash-pro-eyebrow">Sales analytics</p>
                        <h3 class="admin-dash-pro-title" id="dash-top-models">Top selling models</h3>
                        <p class="admin-dash-pro-meta">
                            {{ $startDate->format('M j, Y') }} → {{ $endDate->format('M j, Y') }}
                            <span class="text-slate-400">·</span> ranked by units sold
                        </p>
                    </div>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}"
                    class="admin-dash-filter-bar flex flex-wrap gap-3 items-end shrink-0"
                    aria-label="Filter chart date range">
                    <input type="hidden" name="financial_start_date" value="{{ request('financial_start_date', $financialStartDate->format('Y-m-d')) }}">
                    <input type="hidden" name="financial_end_date" value="{{ request('financial_end_date', $financialEndDate->format('Y-m-d')) }}">
                    @if(request()->filled('open'))
                        <input type="hidden" name="open" value="{{ request('open') }}">
                    @endif
                    <div>
                        <label for="start_date" class="block text-[0.6rem] font-extrabold uppercase tracking-widest text-slate-500 mb-1.5">From</label>
                        <input type="date" name="start_date" id="start_date"
                            value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                            class="admin-dash-filter-input w-full min-w-[10.5rem]">
                    </div>
                    <div>
                        <label for="end_date" class="block text-[0.6rem] font-extrabold uppercase tracking-widest text-slate-500 mb-1.5">To</label>
                        <input type="date" name="end_date" id="end_date"
                            value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                            class="admin-dash-filter-input w-full min-w-[10.5rem]">
                    </div>
                    <button type="submit" class="admin-dash-btn-primary self-end">
                        Apply range
                    </button>
                </form>
            </div>
            <div class="admin-dash-body">
                @if(count($topProducts) > 0)
                    <div class="admin-dash-chart-well">
                        <canvas id="topProductsChart" class="max-h-[400px] w-full" role="img"
                            aria-labelledby="dash-top-models"></canvas>
                    </div>
                @else
                    <div class="admin-dash-empty">
                        <p>No sales data found for the selected date range.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="mt-8 admin-clay-panel overflow-hidden">
            <div class="admin-dash-section-head flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="admin-dash-pro-head flex-1 min-w-0">
                    <div class="admin-dash-pro-icon admin-dash-pro-icon--orders" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="admin-dash-pro-eyebrow">Operations</p>
                        <h3 class="admin-dash-pro-title" id="dash-recent-orders">Recent orders</h3>
                        <p class="admin-dash-pro-meta">Newest first · quick view of the latest storefront activity</p>
                    </div>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="admin-dash-link shrink-0">
                    View all orders
                </a>
            </div>
            <div class="admin-dash-body">
                <div class="admin-dash-table-wrap">
                    <table class="w-full text-left border-collapse" aria-labelledby="dash-recent-orders">
                        <thead>
                            <tr>
                                <th scope="col" class="admin-dash-th">Order ID</th>
                                <th scope="col" class="admin-dash-th">Customer</th>
                                <th scope="col" class="admin-dash-th">Total</th>
                                <th scope="col" class="admin-dash-th">Status</th>
                                <th scope="col" class="admin-dash-th admin-dash-th--end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100/80 bg-white/40">
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td class="px-5 py-3.5 font-semibold text-[#232f3e]">#{{ $order->id }}</td>
                                    <td class="px-5 py-3.5 text-slate-700">{{ $order->user->name ?? 'Guest' }}</td>
                                    <td class="px-5 py-3.5 font-bold text-slate-900">{{ number_format($order->total_price, 0) }} TZS</td>
                                    <td class="px-5 py-3.5">
                                        <span
                                            class="inline-flex px-2.5 py-1 rounded-lg text-[0.65rem] font-bold uppercase tracking-wide border {{ $order->status == 'completed' ? 'bg-emerald-50 text-emerald-800 border-emerald-200/80 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]' : 'bg-amber-50 text-amber-900 border-amber-200/80 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]' }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="admin-dash-link text-xs py-1.5 px-3">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-slate-500 text-sm">No recent orders.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js + DataTables -->
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        if (window.flatpickr) {
            const financialRangeInput = document.getElementById('financial_date_range');
            const financialStartInput = document.getElementById('financial_start_date');
            const financialEndInput = document.getElementById('financial_end_date');

            if (financialRangeInput && financialStartInput && financialEndInput) {
                const defaultDates = [financialStartInput.value, financialEndInput.value].filter(Boolean);
                flatpickr(financialRangeInput, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    defaultDate: defaultDates.length ? defaultDates : null,
                    onClose: function(selectedDates) {
                        if (selectedDates.length === 2) {
                            financialStartInput.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                            financialEndInput.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                        } else if (selectedDates.length === 1) {
                            const singleDate = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                            financialStartInput.value = singleDate;
                            financialEndInput.value = singleDate;
                        }
                    }
                });
            }
        }

        @if(count($topProducts) > 0)
        const ctx = document.getElementById('topProductsChart');
        if (ctx) {
            const chartData = @json($topProducts);
            const n = chartData.length;
            const orangeBars = chartData.map((_, i) => {
                const t = n <= 1 ? 0.5 : i / (n - 1);
                const a = 0.42 + t * 0.48;
                return 'rgba(250, 137, 0, ' + a.toFixed(2) + ')';
            });
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.map(item => item.model),
                    datasets: [{
                        label: 'Quantity Sold',
                        data: chartData.map(item => item.total_quantity),
                        backgroundColor: orangeBars,
                        borderColor: 'rgba(255, 255, 255, 0.65)',
                        borderWidth: 1,
                        borderRadius: 10,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(35, 47, 62, 0.92)',
                            titleFont: { size: 12, weight: '600' },
                            bodyFont: { size: 13 },
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: function(context) {
                                    return 'Quantity: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: { size: 11, weight: '500' }
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.15)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                color: '#64748b',
                                font: { size: 10, weight: '500' }
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    }
                }
            });
        }
        @endif

        // Overdue tables as DataTables (simple search + paging, keep server order)
        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#overdue-purchases-table').DataTable({
                paging: true,
                lengthChange: false,
                pageLength: 10,
                searching: true,
                ordering: false,
                info: false,
                autoWidth: false
            });

            jQuery('#overdue-payables-table').DataTable({
                paging: true,
                lengthChange: false,
                pageLength: 10,
                searching: true,
                ordering: false,
                info: false,
                autoWidth: false
            });

            jQuery('#agent-aging-assets-table').DataTable({
                paging: true,
                lengthChange: false,
                pageLength: 10,
                searching: true,
                ordering: false,
                info: false,
                autoWidth: false
            });
        }
    </script>
    @endpush
</x-admin-layout>