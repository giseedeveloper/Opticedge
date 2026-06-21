@php
    $isPassthrough = $isPassthrough ?? false;
    $listRoute = $isPassthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
    $exportRoute = $isPassthrough ? 'admin.stock.passthrough.export-csv' : 'admin.stock.purchases.export-csv';
    $receiptsRoute = $isPassthrough ? 'admin.stock.passthrough.receipts' : 'admin.stock.purchases.receipts';
    $createRoute = $isPassthrough ? 'admin.stock.create-passthrough' : 'admin.stock.create-purchase';
@endphp
<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">{{ $isPassthrough ? 'Passthrough' : 'Purchases' }}</h1>
                <p class="admin-prod-subtitle">{{ $isPassthrough ? 'Stock passthrough entries (no IMEI tracking), payments, and sell prices.' : 'Stock purchases, payments, and sell prices.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-end shrink-0">
                <a href="{{ route($exportRoute, request()->query()) }}" id="purchase-export-link"
                    class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V4.5m0 12 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5" />
                    </svg>
                    Export CSV
                </a>
                @if(! $isPassthrough)
                <form action="{{ route('admin.stock.update-product-prices') }}" method="POST"
                    onsubmit="return confirm('This will update all existing product prices to use sell_price from their latest purchase. Continue?');"
                    class="inline">
                    @csrf
                    <button type="submit" class="admin-prod-btn-ghost inline-flex items-center gap-2 text-blue-800 border-blue-200/60">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Update product prices
                    </button>
                </form>
                @endif
                <a href="{{ route($receiptsRoute) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    All receipts
                </a>
                <a href="{{ route($createRoute) }}" class="admin-prod-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ $isPassthrough ? 'Add passthrough' : 'Add purchase' }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <x-admin-page-dashboard label="Summary (current filter)" class="mt-2">
            <div id="purchase-dashboard">
                @include('admin.stock.partials.purchases-dashboard', [
                    'purchaseDashboard' => $purchaseDashboard,
                    'isPassthrough' => $isPassthrough,
                ])
            </div>
        </x-admin-page-dashboard>

        <div id="purchase-list-filters">
            <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Date filter</h2>
                    <p class="admin-prod-form-hint">Presets or custom range.</p>
                </div>
                <div class="admin-prod-form-body space-y-4">
                    <div class="admin-prod-filter-row">
                        <button type="button" data-preset="this_week"
                            class="js-purchase-preset admin-prod-filter-tab {{ ($preset ?? '') === 'this_week' ? 'admin-prod-filter-tab--active' : '' }}">This week</button>
                        <button type="button" data-preset="last_week"
                            class="js-purchase-preset admin-prod-filter-tab {{ ($preset ?? '') === 'last_week' ? 'admin-prod-filter-tab--active' : '' }}">Last week</button>
                        <button type="button" data-preset="last_30_days"
                            class="js-purchase-preset admin-prod-filter-tab {{ ($preset ?? '') === 'last_30_days' ? 'admin-prod-filter-tab--active' : '' }}">Last 30 days</button>
                    </div>
                    <form method="GET" action="{{ route($listRoute) }}" class="js-purchase-date-form flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="preset" id="purchase-preset-input" value="{{ $preset ?? '' }}">
                        <div>
                            <label for="date_from" class="admin-prod-label">From date</label>
                            <input type="date" name="date_from" id="date_from"
                                value="{{ old('date_from', $dateFrom ?? request('date_from')) }}" class="admin-prod-input w-auto min-w-[10rem]">
                        </div>
                        <div>
                            <label for="date_to" class="admin-prod-label">To date</label>
                            <input type="date" name="date_to" id="date_to"
                                value="{{ old('date_to', $dateTo ?? request('date_to')) }}" class="admin-prod-input w-auto min-w-[10rem]">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="admin-prod-btn-primary">Filter</button>
                            @if(($dateFrom ?? null) || ($dateTo ?? null) || request('date_from') || request('date_to') || ($preset ?? null) || ($search ?? null))
                                <a href="{{ route($listRoute) }}" class="admin-prod-btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @include('admin.partials.user-live-search', [
                'action' => route($listRoute),
                'search' => $search ?? '',
                'ajax' => true,
                'placeholder' => 'Search by invoice, distributor, product, or branch…',
                'class' => 'mt-4 mb-0',
            ])
        </div>

        <div class="mt-6 admin-clay-panel overflow-x-auto min-w-0" id="purchase-list-panel">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1200px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Invoice</th>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Branch</th>
                            <th scope="col" class="admin-prod-th">Distributor</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Qty</th>
                            <th scope="col" class="admin-prod-th">Unit</th>
                            <th scope="col" class="admin-prod-th">Total</th>
                            <th scope="col" class="admin-prod-th">Paid date</th>
                            <th scope="col" class="admin-prod-th">Paid</th>
                            <th scope="col" class="admin-prod-th">Pending</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="purchase-list-tbody">
                        <tr>
                            <td colspan="14" class="text-center text-slate-500 py-10">Loading {{ $isPassthrough ? 'entries' : 'purchases' }}…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="purchase-list-pagination"></div>
        </div>
    </div>

    @push('styles')
        <style>
            #purchase-list-panel.is-loading {
                opacity: 0.65;
                pointer-events: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            jQuery(function ($) {
                var $panel = $('#purchase-list-panel');
                var $filters = $('#purchase-list-filters');
                var endpoint = '{{ route($listRoute) }}';
                var exportBase = '{{ route($exportRoute) }}';
                var xhr = null;
                var debounceTimer = null;

                function collectParams(page) {
                    var params = {};

                    $filters.find('input, select').each(function () {
                        var $input = $(this);
                        var name = $input.attr('name');
                        var value = $.trim($input.val());

                        if (name && value !== '') {
                            params[name] = value;
                        }
                    });

                    if (page) {
                        params.page = page;
                    }

                    return params;
                }

                function updateUrl(params) {
                    var query = $.param(params);
                    window.history.pushState(params, '', endpoint + (query ? '?' + query : ''));
                    $('#purchase-export-link').attr('href', exportBase + (query ? '?' + query : ''));
                }

                function setActivePreset(preset) {
                    $('.js-purchase-preset')
                        .removeClass('admin-prod-filter-tab--active')
                        .filter('[data-preset="' + preset + '"]')
                        .addClass('admin-prod-filter-tab--active');
                }

                function applyResults(data) {
                    $('#purchase-list-tbody').html(data.tbody);
                    $('#purchase-list-pagination').html(data.pagination);
                    $('#purchase-dashboard').html(data.dashboard);
                }

                function loadPurchases(page, options) {
                    options = options || {};
                    var params = collectParams(page);

                    if (xhr) {
                        xhr.abort();
                    }

                    $panel.addClass('is-loading');

                    xhr = $.ajax({
                        url: endpoint,
                        method: 'GET',
                        data: params,
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .done(function (data) {
                            applyResults(data);

                            if (options.pushState !== false) {
                                updateUrl(params);
                            }
                        })
                        .fail(function (jqXHR, textStatus) {
                            if (textStatus === 'abort') {
                                return;
                            }

                            $('#purchase-list-tbody').html(
                                '<tr><td colspan="14" class="text-center text-red-600 py-10">Could not load {{ $isPassthrough ? 'entries' : 'purchases' }}. Please refresh the page.</td></tr>'
                            );
                            $('#purchase-list-pagination').empty();
                        })
                        .always(function () {
                            $panel.removeClass('is-loading');
                            xhr = null;
                        });
                }

                loadPurchases(new URLSearchParams(window.location.search).get('page'), { pushState: false });
                updateUrl(collectParams());

                $filters.find('.js-user-live-search').on('submit', function (event) {
                    event.preventDefault();
                    loadPurchases();
                });

                $filters.find('input[name="search"]').on('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        loadPurchases();
                    }, 300);
                });

                $filters.find('.js-purchase-date-form').on('submit', function (event) {
                    event.preventDefault();
                    $filters.find('input[name="preset"]').val('');
                    $('.js-purchase-preset').removeClass('admin-prod-filter-tab--active');
                    loadPurchases();
                });

                $filters.on('click', '.js-purchase-preset', function () {
                    var preset = $(this).data('preset');

                    $filters.find('input[name="preset"]').val(preset);
                    $('#date_from, #date_to').val('');
                    setActivePreset(preset);
                    loadPurchases();
                });

                $panel.on('click', '#purchase-list-pagination a', function (event) {
                    event.preventDefault();

                    var url = new URL($(this).attr('href'), window.location.origin);
                    loadPurchases(url.searchParams.get('page') || 1);
                });

                window.addEventListener('popstate', function () {
                    var urlParams = new URLSearchParams(window.location.search);

                    $filters.find('input[name="search"]').val(urlParams.get('search') || '');
                    $('#date_from').val(urlParams.get('date_from') || '');
                    $('#date_to').val(urlParams.get('date_to') || '');
                    $('#purchase-preset-input').val(urlParams.get('preset') || '');

                    if (urlParams.get('preset')) {
                        setActivePreset(urlParams.get('preset'));
                    } else {
                        $('.js-purchase-preset').removeClass('admin-prod-filter-tab--active');
                    }

                    loadPurchases(urlParams.get('page'), { pushState: false });
                    updateUrl(collectParams(urlParams.get('page')));
                });
            });
        </script>
    @endpush
</x-admin-layout>
