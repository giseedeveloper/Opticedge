<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'OpticEdge') }} - Platform</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('app-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    @include('layouts.partials.admin-surface-styles')
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.datatables-styles')
    @stack('styles')
</head>

<body
    class="font-sans antialiased text-slate-600 min-h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]"
    x-data="{ sidebarOpen: false }">

    <!-- Header (clay slab) -->
    <header class="sticky top-0 z-[100] px-3 pt-3 sm:px-4 sm:pt-4">
        <div
            class="max-w-[1600px] mx-auto rounded-[1.75rem] border border-white/80 bg-gradient-to-br from-white/95 via-slate-50/90 to-slate-100/85 text-slate-700 shadow-[10px_14px_28px_rgba(163,177,198,0.28),-6px_-8px_20px_rgba(255,255,255,0.95),inset_2px_2px_4px_rgba(255,255,255,0.9),inset_-1px_-2px_6px_rgba(148,163,184,0.06)] overflow-visible">
            <div class="flex items-center gap-2 lg:gap-4 px-3 py-2.5 sm:px-4 sm:py-3">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="flex items-center gap-1 p-2 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200"
                    aria-label="Toggle Sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <a href="{{ route('superadmin.dashboard') }}"
                    class="flex items-center pt-1 px-2 rounded-xl transition-all duration-200 hover:bg-white/60">
                    <span class="text-2xl font-bold tracking-tight text-[#232f3e]">opticedg<span
                            class="text-[#fa8900]">eafrica</span></span>
                    <span
                        class="ml-2 text-xs font-semibold bg-slate-800 text-white px-2.5 py-1 rounded-lg shadow-[3px_3px_8px_rgba(30,41,59,0.35),inset_1px_1px_2px_rgba(255,255,255,0.2)]">PLATFORM</span>
                </a>

                <div class="flex-grow"></div>

                <div class="hidden md:flex items-center gap-2 mr-2">
                    <x-web-notification-bell />
                </div>

                <div class="hidden md:flex items-center gap-2">
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

                <div class="relative z-[110]" x-data="{ userMenuOpen: false, displayName: @js(Auth::user()->name) }"
                    @keydown.escape.window="userMenuOpen = false"
                    @profile-updated.window="displayName = $event.detail.name">
                    <button type="button" @click.stop="userMenuOpen = !userMenuOpen"
                        :aria-expanded="userMenuOpen"
                        aria-haspopup="menu"
                        class="flex items-center gap-2 p-1.5 pr-2 rounded-2xl admin-clay-inset text-slate-700 hover:text-[#232f3e] transition-all duration-200">
                        <div
                            class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-sm font-bold text-white shadow-[inset_1px_2px_4px_rgba(255,255,255,0.2),2px_3px_8px_rgba(30,41,59,0.3)]">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="hidden md:flex flex-col items-start">
                            <span class="text-xs text-slate-500">Platform</span>
                            <span class="text-sm font-medium text-slate-800" x-text="displayName">{{ Auth::user()->name }}</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="userMenuOpen" x-cloak @click.outside="userMenuOpen = false" @click.stop
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 top-full mt-2 w-52 rounded-2xl border border-white/90 bg-gradient-to-br from-white/98 to-slate-50/95 py-1 z-[120] shadow-[12px_16px_32px_rgba(163,177,198,0.35),-4px_-4px_12px_rgba(255,255,255,0.9),inset_1px_1px_2px_rgba(255,255,255,0.8)]"
                        role="menu">
                        <a href="{{ route('superadmin.profile') }}"
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

            <!-- Sub Navigation -->
            <div
                class="admin-clay-inset flex items-center gap-1 py-1.5 px-2 sm:px-3 mx-2 mb-2 text-sm font-medium overflow-x-auto whitespace-nowrap custom-scrollbar">
                <a href="{{ route('superadmin.dashboard') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.dashboard') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Dashboard</a>
                <a href="{{ route('superadmin.tenants.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.tenants.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Vendors</a>
                <a href="{{ route('superadmin.packages.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.packages.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Packages</a>
                <a href="{{ route('superadmin.subscription-profits.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.subscription-profits.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Subscription</a>
                <a href="{{ route('superadmin.subscription-revenue.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.subscription-revenue.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Revenue</a>
                <a href="{{ route('superadmin.vendor-wallets.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.vendor-wallets.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Wallets</a>
                <a href="{{ route('superadmin.settings.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.settings.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Settings</a>
                <a href="{{ route('superadmin.command.center') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.command.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Command Center</a>
                <a href="{{ route('superadmin.regions.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.regions.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Regions</a>
                <a href="{{ route('superadmin.brands.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.brands.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Brands</a>
                <a href="{{ route('superadmin.models.index') }}"
                    class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-[#232f3e] hover:bg-white/70 transition-all shrink-0 {{ request()->routeIs('superadmin.models.*') ? 'bg-white/80 text-[#232f3e] font-semibold' : '' }}">Models</a>
            </div>
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
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 flex flex-col h-[calc(100vh-124px)] overflow-y-auto transform transition-transform duration-300 ease-in-out custom-scrollbar mt-[124px] sm:mt-[128px] rounded-r-[1.75rem] border border-white/70 border-l-0 bg-gradient-to-b from-white/95 via-slate-50/92 to-slate-100/88 shadow-[8px_12px_28px_rgba(163,177,198,0.22),inset_2px_0_8px_rgba(255,255,255,0.65)]">

            <div class="lg:hidden flex items-center justify-between p-4 border-b border-white/50">
                <span class="text-lg font-bold tracking-tight text-[#232f3e]">opticedg<span
                        class="text-[#fa8900]">eafrica</span> Menu</span>
                <button @click="sidebarOpen = false" class="p-2 rounded-xl admin-clay-inset text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-5 sm:px-4 sm:py-6 space-y-5">
                <div>
                    <h3 class="admin-sidebar-section-title">Platform</h3>
                    <div class="space-y-1">
                        <a href="{{ route('superadmin.dashboard') }}"
                            @if (request()->routeIs('superadmin.dashboard')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.dashboard') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('superadmin.tenants.index') }}"
                            @if (request()->routeIs('superadmin.tenants.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.tenants.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Vendors
                        </a>
                        <a href="{{ route('superadmin.packages.index') }}"
                            @if (request()->routeIs('superadmin.packages.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.packages.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Packages
                        </a>
                        <a href="{{ route('superadmin.subscription-profits.index') }}"
                            @if (request()->routeIs('superadmin.subscription-profits.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.subscription-profits.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Subscription
                        </a>
                        <a href="{{ route('superadmin.subscription-revenue.index') }}"
                            @if (request()->routeIs('superadmin.subscription-revenue.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.subscription-revenue.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3v18h18M9 17V9m4 8V5m4 12v-6" />
                            </svg>
                            Revenue
                        </a>
                        <a href="{{ route('superadmin.vendor-wallets.index') }}"
                            @if (request()->routeIs('superadmin.vendor-wallets.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.vendor-wallets.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 7.5m18 4.5v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V7.5m18 4.5h-3.75a1.5 1.5 0 100 3H21M3 7.5A2.25 2.25 0 015.25 5.25h12A2.25 2.25 0 0119.5 7.5" />
                            </svg>
                            Vendor wallets
                        </a>
                        <a href="{{ route('superadmin.settings.index') }}"
                            @if (request()->routeIs('superadmin.settings.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.settings.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Platform settings
                        </a>
                        <a href="{{ route('superadmin.command.center') }}"
                            @if (request()->routeIs('superadmin.command.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.command.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Command Center
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="admin-sidebar-section-title">Master catalog</h3>
                    <div class="space-y-1">
                        <a href="{{ route('superadmin.regions.index') }}"
                            @if (request()->routeIs('superadmin.regions.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.regions.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Regions
                        </a>
                        <a href="{{ route('superadmin.brands.index') }}"
                            @if (request()->routeIs('superadmin.brands.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.brands.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Brands
                        </a>
                        <a href="{{ route('superadmin.models.index') }}"
                            @if (request()->routeIs('superadmin.models.*')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('superadmin.models.*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Models
                        </a>
                    </div>
                </div>
            </nav>

            <div class="p-4 border-t border-white/50 mt-auto" x-data="{ open: false }">
                <div class="relative">
                    <button @click="open = !open" class="w-full flex items-center justify-between group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-full admin-clay-inset flex items-center justify-center text-slate-600 font-bold overflow-hidden">
                                {{ substr(Auth::user()->name, 0, 1) }}
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

                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute bottom-full left-0 w-full mb-2 rounded-2xl border border-white/90 bg-gradient-to-br from-white/98 to-slate-50/95 py-1 z-50 shadow-[12px_16px_32px_rgba(163,177,198,0.35),-4px_-4px_12px_rgba(255,255,255,0.9),inset_1px_1px_2px_rgba(255,255,255,0.8)]"
                        role="menu">
                        <a href="{{ route('superadmin.profile') }}"
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

        <!-- Main Content Area -->
        <div class="flex-1 lg:pl-64 flex flex-col min-h-[calc(100vh-124px)] sm:min-h-[calc(100vh-128px)] overflow-y-auto">
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
