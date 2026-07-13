<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'opticadgeafrica') }} - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('app-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    @include('layouts.partials.admin-surface-styles')
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.datatables-styles')
    @stack('styles')
</head>

@php
    $tenantSuspended = \App\Support\TenantSuspension::adminHasRestrictedAccess(auth()->user());
@endphp

<body
    class="font-sans antialiased text-slate-600 min-h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]"
    x-data="{ sidebarOpen: false }">

    <!-- Header (clay slab) -->
    <header class="sticky top-0 z-[100] px-3 pt-3 sm:px-4 sm:pt-4">
        <div
            class="max-w-[1600px] mx-auto rounded-[1.75rem] border border-white/80 bg-gradient-to-br from-white/95 via-slate-50/90 to-slate-100/85 text-slate-700 shadow-[10px_14px_28px_rgba(163,177,198,0.28),-6px_-8px_20px_rgba(255,255,255,0.95),inset_2px_2px_4px_rgba(255,255,255,0.9),inset_-1px_-2px_6px_rgba(148,163,184,0.06)] overflow-visible">
        <!-- Main Bar -->
        <div class="flex items-center gap-2 lg:gap-4 px-3 py-2.5 sm:px-4 sm:py-3">
            <!-- Sidebar Toggle Button -->
            <button @click="sidebarOpen = !sidebarOpen"
                class="flex items-center gap-1 p-2 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200"
                aria-label="Toggle Sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Logo -->
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center pt-1 px-2 rounded-xl transition-all duration-200 hover:bg-white/60">
                <span class="text-2xl font-bold tracking-tight text-[#232f3e]">opticedg<span
                        class="text-[#fa8900]">eafrica</span></span>
                <span
                    class="ml-2 text-xs font-semibold bg-gradient-to-br from-[#fa8900] to-[#e67a00] text-white px-2.5 py-1 rounded-lg shadow-[3px_3px_8px_rgba(250,137,0,0.35),inset_1px_1px_2px_rgba(255,255,255,0.35)]">ADMIN</span>
            </a>

            <!-- Spacer -->
            <div class="flex-grow"></div>

            <!-- Quick Actions -->
            <div class="hidden md:flex items-center gap-2">
                <x-web-notification-bell />

                <!-- View Website -->
                <a href="/" target="_blank"
                    class="flex items-center gap-2 p-2 px-3 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span class="text-sm font-medium">View Site</span>
                </a>
            </div>

            <!-- User Profile -->
            <div class="relative z-[110]" x-data="{ userMenuOpen: false, displayName: @js(Auth::user()->name) }"
                @keydown.escape.window="userMenuOpen = false"
                @profile-updated.window="displayName = $event.detail.name">
                <button type="button" @click.stop="userMenuOpen = !userMenuOpen"
                    :aria-expanded="userMenuOpen"
                    aria-haspopup="menu"
                    class="flex items-center gap-2 p-1.5 pr-2 rounded-2xl admin-clay-inset text-slate-700 hover:text-[#232f3e] transition-all duration-200">
                    <div
                        class="w-9 h-9 rounded-full bg-gradient-to-br from-[#fa8900] to-[#e07800] flex items-center justify-center text-sm font-bold text-white shadow-[inset_1px_2px_4px_rgba(255,255,255,0.35),2px_3px_8px_rgba(250,137,0,0.3)]">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:flex flex-col items-start">
                        <span class="text-xs text-slate-500">Admin</span>
                        <span class="text-sm font-medium text-slate-800" x-text="displayName">{{ Auth::user()->name }}</span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div x-show="userMenuOpen" x-cloak @click.outside="userMenuOpen = false" @click.stop
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 top-full mt-2 w-52 rounded-2xl border border-white/90 bg-gradient-to-br from-white/98 to-slate-50/95 py-1 z-[120] shadow-[12px_16px_32px_rgba(163,177,198,0.35),-4px_-4px_12px_rgba(255,255,255,0.9),inset_1px_1px_2px_rgba(255,255,255,0.8)]"
                    role="menu">
                    <a href="{{ route('admin.profile') }}"
                        class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-white/80 rounded-xl mx-1">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" @click.stop>
                        @csrf
                        <button type="submit"
                            class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-white/80 hover:text-red-600 rounded-xl mx-1 mb-1">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if ($tenantSuspended)
            <div class="mx-2 mb-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
                Subscription suspended — only the subscription page is available until you renew.
            </div>
        @endif

        <!-- Sub Navigation (inset clay strip) -->
        @unless ($tenantSuspended)
        <div
            class="admin-clay-inset flex items-center gap-1 py-1.5 px-2 sm:px-3 mx-2 mb-2 text-sm font-medium overflow-x-auto whitespace-nowrap custom-scrollbar">
            <a href="{{ route('admin.dashboard') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0">Dashboard</a>
            <a href="{{ route('admin.orders.index') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0">Orders</a>
            <a href="{{ route('admin.dealers.index') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0">Dealers</a>
            <a href="{{ route('admin.vendors.index') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0">Vendors</a>
            <a href="{{ route('admin.tenant.edit') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('admin.tenant.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Subscription</a>
            <a href="{{ route('admin.settings.index') }}"
                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0">Settings</a>
        </div>
        @else
        <div class="admin-clay-inset flex items-center gap-1 py-1.5 px-2 sm:px-3 mx-2 mb-2 text-sm font-medium">
            <a href="{{ route('admin.tenant.edit') }}"
                class="px-3 py-1.5 rounded-xl bg-white/80 text-[#232f3e] font-semibold shrink-0">Subscription</a>
        </div>
        @endunless
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Overlay (Mobile) -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        </div>

        <!-- Sidebar -->
        @unless ($tenantSuspended)
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 flex flex-col h-[calc(100vh-124px)] overflow-y-auto transform transition-transform duration-300 ease-in-out custom-scrollbar mt-[124px] sm:mt-[128px] rounded-r-[1.75rem] border border-white/70 border-l-0 bg-gradient-to-b from-white/95 via-slate-50/92 to-slate-100/88 shadow-[8px_12px_28px_rgba(163,177,198,0.22),inset_2px_0_8px_rgba(255,255,255,0.65)]">

            <!-- Close button (Mobile) -->
            <div class="lg:hidden flex items-center justify-between p-4 border-b border-white/50">
                <span class="text-lg font-bold tracking-tight text-[#232f3e]">opticedg<span
                        class="text-[#fa8900]">eafrica</span>
                    Menu</span>
                <button @click="sidebarOpen = false" class="p-2 rounded-xl admin-clay-inset text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @php
                $sidebarUsersActive = request()->routeIs([
                    'admin.customers.index',
                    'admin.customers.regional-managers.*',
                    'admin.customers.team-leaders.*',
                    'admin.customers.organization-tree',
                    'admin.guest-users.*',
                    'admin.contract-terminations.*',
                    'admin.dealers.*',
                    'admin.agents.*',
                    'admin.subadmins.*',
                    'admin.vendors.*',
                ]);
                $sidebarStockActive = request()->routeIs([
                    'admin.stock.*',
                    'admin.branches.*',
                ]);
                $navStockImei = request()->routeIs([
                    'admin.stock.imei-search',
                    'admin.stock.imei-item',
                ]);
                $navStockStocks = request()->routeIs([
                    'admin.stock.stocks',
                    'admin.stock.stocks.models',
                    'admin.stock.stocks.show',
                    'admin.stock.stock-receipts',
                    'admin.stock.add-product',
                    'admin.stock.store-add-product',
                    'admin.stock.decode-barcodes',
                ]);
                $navStockPurchases = request()->routeIs([
                    'admin.stock.purchases',
                    'admin.stock.purchases.receipts',
                    'admin.stock.purchase.show',
                    'admin.stock.create-purchase',
                    'admin.stock.store-purchase',
                    'admin.stock.edit-purchase',
                    'admin.stock.update-purchase',
                    'admin.stock.destroy-purchase',
                    'admin.stock.update-product-prices',
                ]) && ! request()->routeIs([
                    'admin.stock.passthrough',
                    'admin.stock.passthrough.*',
                    'admin.stock.create-passthrough',
                    'admin.stock.store-passthrough',
                    'admin.stock.edit-passthrough',
                    'admin.stock.update-passthrough',
                    'admin.stock.destroy-passthrough',
                ]);
                $navStockPassthrough = request()->routeIs([
                    'admin.stock.passthrough',
                    'admin.stock.passthrough.*',
                    'admin.stock.create-passthrough',
                    'admin.stock.store-passthrough',
                    'admin.stock.edit-passthrough',
                    'admin.stock.update-passthrough',
                    'admin.stock.destroy-passthrough',
                ]);
                $navStockDistribution = request()->routeIs([
                    'admin.stock.distribution',
                    'admin.stock.create-distribution',
                    'admin.stock.store-distribution',
                    'admin.stock.edit-distribution',
                    'admin.stock.update-distribution',
                    'admin.stock.destroy-distribution',
                    'admin.stock.distribution-update-status',
                    'admin.stock.distribution-save-channel',
                ]);
                $navStockAgentSales = request()->routeIs([
                    'admin.stock.agent-sales',
                    'admin.stock.create-agent-sale',
                    'admin.stock.store-agent-sale',
                    'admin.stock.agent-sales-update-commission',
                    'admin.stock.agent-sales-save-channel',
                    'admin.stock.agent-sales-convert-to-credit',
                ]);
                $navStockAgentCredits = request()->routeIs([
                    'admin.stock.agent-credits',
                    'admin.stock.edit-agent-credit',
                    'admin.stock.agent-credit-payment-channel',
                    'admin.stock.agent-credit-pay-remaining',
                    'admin.stock.update-agent-credit',
                ]);
                $navStockBranchTransfer = request()->routeIs([
                    'admin.stock.branch-transfer',
                    'admin.stock.branch-transfer.store',
                    'admin.stock.branch-transfer.items',
                    'admin.stock.branch-transfer.logs',
                ]);
                $navStockDeviceTransfers = request()->routeIs([
                    'admin.stock.device-transfers',
                    'admin.stock.device-transfers.*',
                    'admin.stock.agent-transfers',
                    'admin.stock.agent-transfers.*',
                ]);
                $navStockDeviceReturns = request()->routeIs([
                    'admin.stock.device-returns',
                    'admin.stock.device-returns.*',
                ]);
            @endphp

            <nav class="flex-1 px-3 py-5 sm:px-4 sm:py-6 space-y-5">

                <!-- Dashboard Section -->
                <div>
                    <h3 class="admin-sidebar-section-title">Dashboard</h3>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}"
                            @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.dashboard') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Main Dashboard
                        </a>
                    </div>
                </div>

                <!-- Management Section -->
                <div>
                    <h3 class="admin-sidebar-section-title">Management</h3>
                    <div class="space-y-1">

                        <a href="{{ route('admin.categories.index') }}"
                            @if (request()->routeIs('admin.categories.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.categories.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Brands
                        </a>
                        <a href="{{ route('admin.products.index') }}"
                            @if (request()->routeIs('admin.products.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.products.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Models
                        </a>
                        <div x-data="{ open: {{ $sidebarUsersActive ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                class="admin-sidebar-item admin-sidebar-group-btn {{ $sidebarUsersActive ? 'admin-sidebar-item-active' : '' }}">
                                <div class="admin-sidebar-item-leading flex-1 min-w-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Users
                                </div>
                                <svg class="admin-sidebar-chevron transition-transform" :class="{ 'rotate-180': open }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak
                                class="mt-1.5 ml-3 pl-3 space-y-0.5 border-l border-slate-300/50">
                                <a href="{{ route('admin.customers.index') }}"
                                    @if (request()->routeIs('admin.customers.index')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.customers.index') ? 'admin-sidebar-sublink-active' : '' }}">All users</a>
                                <a href="{{ route('admin.guest-users.index') }}"
                                    @if (request()->routeIs('admin.guest-users.*')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.guest-users.*') ? 'admin-sidebar-sublink-active' : '' }}">OpticEdge users</a>
                                <a href="{{ route('admin.contract-terminations.index') }}"
                                    @if (request()->routeIs('admin.contract-terminations.*')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.contract-terminations.*') ? 'admin-sidebar-sublink-active' : '' }}">Contract terminations</a>
                                <a href="{{ route('admin.customers.organization-tree') }}"
                                    @if (request()->routeIs('admin.customers.organization-tree')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.customers.organization-tree') ? 'admin-sidebar-sublink-active' : '' }}">Organization tree</a>
                                <a href="{{ route('admin.dealers.index') }}"
                                    @if (request()->routeIs('admin.dealers.*')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.dealers.*') ? 'admin-sidebar-sublink-active' : '' }}">Dealers</a>
                                <a href="{{ route('admin.vendors.index') }}"
                                    @if (request()->routeIs('admin.vendors.*')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.vendors.*') ? 'admin-sidebar-sublink-active' : '' }}">Vendors</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Management Section -->
                <div>
                    <h3 class="admin-sidebar-section-title">Stock Management</h3>
                    <div class="space-y-1">
                        <div x-data="{ open: {{ $sidebarStockActive ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                class="admin-sidebar-item admin-sidebar-group-btn {{ $sidebarStockActive ? 'admin-sidebar-item-active' : '' }}">
                                <div class="admin-sidebar-item-leading flex-1 min-w-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Stock
                                </div>
                                <svg class="admin-sidebar-chevron transition-transform" :class="{ 'rotate-180': open }"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak
                                class="mt-1.5 ml-3 pl-3 space-y-0.5 border-l border-slate-300/50">
                                <a href="{{ route('admin.stock.stocks') }}"
                                    @if ($navStockStocks) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockStocks ? 'admin-sidebar-sublink-active' : '' }}">Stocks</a>
                                <a href="{{ route('admin.stock.imei-search') }}"
                                    @if ($navStockImei) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockImei ? 'admin-sidebar-sublink-active' : '' }}">IMEI search</a>
                                <a href="{{ route('admin.branches.index') }}"
                                    @if (request()->routeIs('admin.branches.*')) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ request()->routeIs('admin.branches.*') ? 'admin-sidebar-sublink-active' : '' }}">Branches</a>
                                <a href="{{ route('admin.stock.purchases') }}"
                                    @if ($navStockPurchases) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockPurchases ? 'admin-sidebar-sublink-active' : '' }}">Purchases</a>
                                <a href="{{ route('admin.stock.passthrough') }}"
                                    @if ($navStockPassthrough) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockPassthrough ? 'admin-sidebar-sublink-active' : '' }}">Passthrough Sales</a>
                                <a href="{{ route('admin.stock.distribution') }}"
                                    @if ($navStockDistribution) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockDistribution ? 'admin-sidebar-sublink-active' : '' }}">Distribution Sales</a>
                                <a href="{{ route('admin.stock.agent-sales') }}"
                                    @if ($navStockAgentSales) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockAgentSales ? 'admin-sidebar-sublink-active' : '' }}">Agent Cash Sales</a>
                                <a href="{{ route('admin.stock.agent-credits') }}"
                                    @if ($navStockAgentCredits) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockAgentCredits ? 'admin-sidebar-sublink-active' : '' }}">Agent Credit Sales</a>
                                <a href="{{ route('admin.stock.branch-transfer') }}"
                                    @if ($navStockBranchTransfer) aria-current="page" @endif
                                    class="admin-sidebar-sublink {{ $navStockBranchTransfer ? 'admin-sidebar-sublink-active' : '' }}">Branch transfer</a>
                                <a href="{{ route('admin.stock.device-transfers') }}"
                                    @if ($navStockDeviceTransfers) aria-current="page" @endif
                                    class="admin-sidebar-sublink admin-sidebar-sublink-with-badge {{ $navStockDeviceTransfers ? 'admin-sidebar-sublink-active' : '' }}">
                                    <span>Device transfer</span>
                                    <x-portal-pending-badge :count="$portalPendingCounts['pending_transfer_requests'] ?? 0" />
                                </a>
                                <a href="{{ route('admin.stock.device-returns') }}"
                                    @if ($navStockDeviceReturns) aria-current="page" @endif
                                    class="admin-sidebar-sublink admin-sidebar-sublink-with-badge {{ $navStockDeviceReturns ? 'admin-sidebar-sublink-active' : '' }}">
                                    <span>Device return</span>
                                    <x-portal-pending-badge :count="$portalPendingCounts['pending_return_requests'] ?? 0" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operations Section -->
                <div>
                    <h3 class="admin-sidebar-section-title">Operations</h3>
                    <div class="space-y-1">
                        <a href="{{ route('admin.payment-options.index') }}"
                            @if (request()->routeIs('admin.payment-options.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.payment-options.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Channels
                        </a>
                        <a href="{{ route('admin.expenses.index') }}"
                            @if (request()->routeIs('admin.expenses.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.expenses.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Expenses
                        </a>
                        <a href="{{ route('admin.payout.index') }}"
                            @if (request()->routeIs('admin.payout.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.payout.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                            Pay out
                        </a>
                        <a href="{{ route('admin.reports.index') }}"
                            @if (request()->routeIs('admin.reports.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.reports.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Sales Reports
                        </a>
                        <a href="{{ route('admin.customer-needs.index') }}"
                            @if (request()->routeIs('admin.customer-needs.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.customer-needs.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Leads report
                        </a>
                        <a href="{{ route('admin.tenant.edit') }}"
                            @if (request()->routeIs('admin.tenant.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.tenant.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Subscription
                        </a>
                        <a href="{{ route('admin.settings.index') }}"
                            @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('admin.settings.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Store Settings
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Profile (Bottom) -->
            <div class="p-4 border-t border-white/50 mt-auto" x-data="{ open: false }">
                <div class="relative">
                    <button @click="open = !open" class="w-full flex items-center justify-between group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-full admin-clay-inset flex items-center justify-center text-slate-600 font-bold overflow-hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0 text-left">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-transform"
                            :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute bottom-full left-0 w-full mb-2 rounded-2xl border border-white/90 bg-gradient-to-br from-white/98 to-slate-50/95 py-1 z-50 shadow-[12px_16px_32px_rgba(163,177,198,0.35),-4px_-4px_12px_rgba(255,255,255,0.9),inset_1px_1px_2px_rgba(255,255,255,0.8)]"
                        role="menu">
                        <a href="{{ route('admin.profile') }}"
                            class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-white/80 rounded-xl mx-1"
                            role="menuitem">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile
                            </div>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" @click.stop>
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-white/80 hover:text-red-600 flex items-center gap-2 rounded-xl mx-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
        @endunless

        <!-- Main Content Area -->
        <div class="flex-1 {{ $tenantSuspended ? '' : 'lg:pl-64' }} flex flex-col min-h-[calc(100vh-124px)] sm:min-h-[calc(100vh-128px)] overflow-y-auto">
            <!-- Main Content -->
            <main class="flex-1 bg-transparent p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    @include('layouts.partials.datatables-lib')
    @stack('scripts')
    @include('layouts.partials.datatables-init')
</body>

</html>