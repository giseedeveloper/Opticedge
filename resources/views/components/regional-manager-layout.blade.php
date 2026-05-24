@props([
    'title' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'opticedge') }} — Regional manager</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('app-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @include('layouts.partials.admin-surface-styles')
    @include('admin.partials.catalog-styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body
    class="font-sans antialiased text-slate-600 min-h-full bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]"
    x-data="{ sidebarOpen: false }">

    <header class="sticky top-0 z-50 px-3 pt-3 sm:px-4 sm:pt-4">
        <div
            class="max-w-[1600px] mx-auto rounded-[1.75rem] border border-white/80 bg-gradient-to-br from-white/95 via-slate-50/90 to-slate-100/85 text-slate-700 shadow-[10px_14px_28px_rgba(163,177,198,0.28),-6px_-8px_20px_rgba(255,255,255,0.95),inset_2px_2px_4px_rgba(255,255,255,0.9),inset_-1px_-2px_6px_rgba(148,163,184,0.06)] overflow-hidden">
            <div class="flex items-center gap-2 lg:gap-4 px-3 py-2.5 sm:px-4 sm:py-3">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="flex items-center gap-1 p-2 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200"
                    type="button" aria-label="Toggle sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <a href="{{ route('regional-manager.dashboard') }}"
                    class="flex items-center pt-1 px-2 rounded-xl transition-all duration-200 hover:bg-white/60">
                    <span class="text-2xl font-bold tracking-tight text-[#232f3e]">opticedg<span
                            class="text-[#fa8900]">eafrica</span></span>
                    <span
                        class="ml-2 text-[10px] sm:text-xs font-semibold whitespace-nowrap bg-gradient-to-br from-slate-700 to-slate-900 text-white px-2.5 py-1 rounded-lg shadow-[3px_3px_8px_rgba(15,23,42,0.25),inset_1px_1px_2px_rgba(255,255,255,0.2)]">REGIONAL MNGR</span>
                </a>

                <div class="flex-grow"></div>

                <div class="hidden md:flex items-center gap-2">
                    <a href="{{ route('welcome') }}"
                        class="flex items-center gap-2 p-2 px-3 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span class="text-sm font-medium">Shop</span>
                    </a>
                </div>

                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen" type="button"
                        class="flex items-center gap-2 p-1.5 pr-2 rounded-2xl admin-clay-inset text-slate-700 hover:text-[#232f3e] transition-all duration-200">
                        <div
                            class="w-9 h-9 rounded-full bg-gradient-to-br from-[#fa8900] to-[#e07800] flex items-center justify-center text-sm font-bold text-white shadow-[inset_1px_2px_4px_rgba(255,255,255,0.35),2px_3px_8px_rgba(250,137,0,0.3)]">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="hidden md:flex flex-col items-start">
                            <span class="text-xs text-slate-500">Regional manager</span>
                            <span class="text-sm font-medium text-slate-800">{{ Auth::user()->name }}</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="userMenuOpen" @click.away="userMenuOpen = false" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-52 rounded-2xl border border-white/90 bg-gradient-to-br from-white/98 to-slate-50/95 py-1 z-50 shadow-[12px_16px_32px_rgba(163,177,198,0.35),-4px_-4px_12px_rgba(255,255,255,0.9),inset_1px_1px_2px_rgba(255,255,255,0.8)]">
                        <a href="{{ route('regional-manager.profile') }}"
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
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-white/80 hover:text-red-600 rounded-xl mx-1 mb-1">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Log out
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div
                class="admin-clay-inset flex items-center gap-1 py-1.5 px-2 sm:px-3 mx-2 mb-2 text-sm font-medium overflow-x-auto whitespace-nowrap custom-scrollbar">
                <a href="{{ route('regional-manager.dashboard') }}"
                    class="px-3 py-1.5 rounded-xl shrink-0 {{ request()->routeIs('regional-manager.dashboard') ? 'bg-white/90 text-[#232f3e] shadow-sm' : 'text-slate-600 hover:text-[#232f3e] hover:bg-white/70' }} transition-all">Overview</a>
                <a href="{{ route('regional-manager.region-inventory') }}"
                    class="px-3 py-1.5 rounded-xl shrink-0 {{ request()->routeIs('regional-manager.region-inventory') ? 'bg-white/90 text-[#232f3e] shadow-sm' : 'text-slate-600 hover:text-[#232f3e] hover:bg-white/70' }} transition-all">IMEIs</a>
                <a href="{{ route('regional-manager.profile') }}"
                    class="px-3 py-1.5 rounded-xl shrink-0 {{ request()->routeIs('regional-manager.profile') ? 'bg-white/90 text-[#232f3e] shadow-sm' : 'text-slate-600 hover:text-[#232f3e] hover:bg-white/70' }} transition-all">Profile</a>
                <a href="{{ route('welcome') }}"
                    class="px-3 py-1.5 rounded-xl shrink-0 {{ request()->routeIs('welcome') ? 'bg-white/90 text-[#232f3e] shadow-sm' : 'text-slate-600 hover:text-[#232f3e] hover:bg-white/70' }} transition-all">Browse shop</a>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        </div>

        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 flex flex-col h-[calc(100vh-124px)] sm:h-[calc(100vh-128px)] overflow-y-auto transform transition-transform duration-300 ease-in-out custom-scrollbar mt-[124px] sm:mt-[128px] rounded-r-[1.75rem] border border-white/70 border-l-0 bg-gradient-to-b from-white/95 via-slate-50/92 to-slate-100/88 shadow-[8px_12px_28px_rgba(163,177,198,0.22),inset_2px_0_8px_rgba(255,255,255,0.65)]">

            <div class="lg:hidden flex items-center justify-between p-4 border-b border-white/50">
                <span class="text-lg font-bold tracking-tight text-[#232f3e]">Menu</span>
                <button type="button" @click="sidebarOpen = false" class="p-2 rounded-xl admin-clay-inset text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-6">
                <div>
                    <p class="admin-sidebar-section-title">Store</p>
                    <div class="space-y-1">
                        <a href="{{ route('welcome') }}"
                            class="admin-sidebar-item {{ request()->routeIs('welcome') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Browse shop
                        </a>
                    </div>
                </div>

                <div>
                    <p class="admin-sidebar-section-title">Region</p>
                    <div class="space-y-1">
                        <a href="{{ route('regional-manager.dashboard') }}"
                            @if (request()->routeIs('regional-manager.dashboard')) aria-current="page" @endif
                            class="admin-sidebar-item {{ request()->routeIs('regional-manager.dashboard') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Regional overview
                        </a>
                        <a href="{{ route('regional-manager.region-inventory') }}"
                            class="admin-sidebar-item {{ request()->routeIs('regional-manager.region-inventory') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17v-6a2 2 0 012-2h8m-6 8h6M5 3H3a2 2 0 00-2 2v14a2 2 0 002 2h6a2 2 0 002-2V5a2 2 0 00-2-2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 11h.01M7 15h.01" />
                            </svg>
                            Regional inventory &amp; IMEIs
                        </a>
                        <a href="{{ route('regional-manager.assign-team-leader') }}"
                            class="admin-sidebar-item {{ request()->routeIs('regional-manager.assign-team-leader*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Assign to team leader
                        </a>
                        <a href="{{ route('regional-manager.return-devices') }}"
                            class="admin-sidebar-item {{ request()->routeIs('regional-manager.return-devices*') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                            </svg>
                            Return to admin
                        </a>
                    </div>
                </div>

                <div>
                    <p class="admin-sidebar-section-title">Account</p>
                    <div class="space-y-1">
                        <a href="{{ route('regional-manager.profile') }}"
                            class="admin-sidebar-item {{ request()->routeIs('regional-manager.profile') ? 'admin-sidebar-item-active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </a>
                    </div>
                </div>

            </nav>

            <div class="p-4 border-t border-white/50 mt-auto">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-full admin-clay-inset flex items-center justify-center text-slate-600 text-sm font-bold">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 lg:pl-64 flex flex-col min-h-[calc(100vh-124px)] sm:min-h-[calc(100vh-128px)] overflow-y-auto">
            <main class="flex-1 bg-transparent p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
