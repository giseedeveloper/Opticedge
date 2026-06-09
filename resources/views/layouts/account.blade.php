<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'opticadgeafrica') }} - Account</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('app-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style type="text/css">
        @theme {
            --color-brand-black: #232f3e;
            --color-brand-dark: #19212c;
            --color-brand-orange: #fa8900;
            --color-brand-yellow: #febd69;
            --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.datatables-styles')
    @stack('styles')
</head>

<body class="font-sans antialiased text-slate-600 bg-slate-50 min-h-full" x-data="{ sidebarOpen: false }">

    <!-- Top Header -->
    <header class="bg-[#232f3e] text-white sticky top-0 z-50">
        <!-- Main Bar -->
        <div class="max-w-[1600px] mx-auto flex items-center gap-2 lg:gap-4 p-2 px-4">
            <!-- Sidebar Toggle Button -->
            <button @click="sidebarOpen = !sidebarOpen"
                class="flex items-center gap-1 p-2 border border-transparent hover:border-white rounded-sm transition-all duration-200"
                aria-label="Toggle Sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Logo -->
            @php
                $isFieldPortalUser = in_array(Auth::user()->role ?? '', ['agent', 'teamleader', 'regional_manager'], true);
                $portalLogoHref = match (Auth::user()->role ?? '') {
                    'agent' => route('agent.dashboard'),
                    'teamleader' => route('team-leader.dashboard'),
                    'regional_manager' => route('regional-manager.dashboard'),
                    default => route('shop'),
                };
            @endphp
            <a href="{{ $isFieldPortalUser ? $portalLogoHref : route('shop') }}"
                class="flex items-center pt-2 px-2 border border-transparent hover:border-white rounded-sm transition-all duration-200">
                <span class="text-2xl font-bold tracking-tight">opticedg<span class="text-[#fa8900]">eafrica</span></span>
            </a>

            <!-- Spacer -->
            <div class="flex-grow"></div>

            @unless ($isFieldPortalUser)
            <!-- Cart -->
            <a href="/cart"
                class="flex items-end px-2 py-1 border border-transparent hover:border-white rounded-sm transition-all gap-1 min-w-[80px] relative">
                <div class="relative flex items-end">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="font-bold text-sm mb-1 hidden sm:block">Cart</span>
            </a>
            @endunless

            <!-- User Profile -->
            <div class="relative" x-data="{ userMenuOpen: false }">
                <button @click="userMenuOpen = !userMenuOpen"
                    class="flex items-center gap-2 p-2 border border-transparent hover:border-white rounded-sm transition-all duration-200">
                    <div
                        class="w-8 h-8 rounded-full bg-[#fa8900] flex items-center justify-center text-sm font-bold text-[#232f3e]">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:flex flex-col items-start">
                        <span class="text-xs text-slate-300">{{ ucfirst(Auth::user()->role ?? 'User') }}</span>
                        <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transition-transform" :class="{ 'rotate-180': userMenuOpen }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div x-show="userMenuOpen" @click.away="userMenuOpen = false" x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-slate-200 py-1 z-50">
                    <a href="{{ route('shop') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Home
                        </div>
                    </a>
                    <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
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
                            class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-red-600">
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
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col h-[calc(100vh-64px)] overflow-y-auto transform transition-transform duration-300 ease-in-out custom-scrollbar mt-[64px]">

            <!-- Close button (Mobile) -->
            <div class="lg:hidden flex items-center justify-between p-4 border-b border-slate-100">
                <span class="text-lg font-bold tracking-tight text-[#232f3e]">opticedg<span class="text-[#fa8900]">eafrica</span>
                    Menu</span>
                <button @click="sidebarOpen = false" class="p-1 rounded hover:bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-6">

                <!-- Account Section -->
                <div>
                    <h3 class="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Your Account
                    </h3>
                    <div class="space-y-1">
                        @php
                            $isPortalManager = in_array(Auth::user()->role ?? '', ['teamleader', 'regional_manager'], true);
                            $isFieldPortalUser = in_array(Auth::user()->role ?? '', ['agent', 'teamleader', 'regional_manager'], true);
                            $accountDashboardHref = match (Auth::user()->role ?? '') {
                                'agent' => route('agent.dashboard'),
                                'teamleader' => route('team-leader.dashboard'),
                                'regional_manager' => route('regional-manager.dashboard'),
                                default => route('dashboard'),
                            };
                            $accountDashboardActive = request()->routeIs('dashboard')
                                || request()->routeIs('agent.dashboard')
                                || request()->routeIs('team-leader.dashboard')
                                || request()->routeIs('regional-manager.dashboard');
                            $profileHref = match (Auth::user()->role ?? '') {
                                'teamleader' => route('team-leader.profile'),
                                'regional_manager' => route('regional-manager.profile'),
                                default => route('profile'),
                            };
                            $profileActive = match (Auth::user()->role ?? '') {
                                'teamleader' => request()->routeIs('team-leader.profile'),
                                'regional_manager' => request()->routeIs('regional-manager.profile'),
                                default => request()->routeIs('profile'),
                            };
                        @endphp
                        <a href="{{ $accountDashboardHref }}"
                            class="flex items-center gap-3 px-2 py-2 text-sm font-medium {{ $accountDashboardActive ? 'bg-slate-100 text-slate-900' : 'text-slate-700' }} rounded-md hover:bg-slate-50 group">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 {{ $accountDashboardActive ? 'text-[#fa8900]' : 'text-slate-400 group-hover:text-slate-600' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ $profileHref }}"
                            class="flex items-center gap-3 px-2 py-2 text-sm font-medium {{ $profileActive ? 'bg-slate-100 text-slate-900' : 'text-slate-700' }} rounded-md hover:bg-slate-50 group">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 {{ $profileActive ? 'text-[#fa8900]' : 'text-slate-400 group-hover:text-slate-600' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </a>
                        @unless ($isFieldPortalUser)
                            <a href="{{ route('orders.index') }}"
                                class="flex items-center gap-3 px-2 py-2 text-sm font-medium {{ request()->routeIs('orders.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-700' }} rounded-md hover:bg-slate-50 group">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 {{ request()->routeIs('orders.*') ? 'text-[#fa8900]' : 'text-slate-400 group-hover:text-slate-600' }}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Your Orders
                            </a>
                            <a href="{{ route('addresses.index') }}"
                                class="flex items-center gap-3 px-2 py-2 text-sm font-medium {{ request()->routeIs('addresses.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-700' }} rounded-md hover:bg-slate-50 group">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 {{ request()->routeIs('addresses.*') ? 'text-[#fa8900]' : 'text-slate-400 group-hover:text-slate-600' }}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Your Addresses
                            </a>
                            <a href="{{ route('cart.index') }}"
                                class="flex items-center gap-3 px-2 py-2 text-sm font-medium {{ request()->routeIs('cart.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-700' }} rounded-md hover:bg-slate-50 group">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 {{ request()->routeIs('cart.*') ? 'text-[#fa8900]' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Shopping Cart
                            </a>
                        @endunless
                    </div>
                </div>

                @unless ($isFieldPortalUser ?? false)
                <!-- Shopping Section -->
                <div>
                    <h3 class="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Shopping</h3>
                    <div class="space-y-1">
                        <a href="{{ route('shop') }}"
                            class="flex items-center gap-3 px-2 py-2 text-sm font-medium text-slate-700 rounded-md hover:bg-slate-50 group">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-slate-400 group-hover:text-slate-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Browse Products
                        </a>
                        <a href="#"
                            class="flex items-center gap-3 px-2 py-2 text-sm font-medium text-slate-700 rounded-md hover:bg-slate-50 group">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-slate-400 group-hover:text-slate-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Browsing History
                        </a>
                    </div>
                </div>
                @endunless

            </nav>

            <!-- User Profile (Bottom) -->
            <div class="p-4 border-t border-slate-200 mt-auto">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold overflow-hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 lg:pl-64 flex flex-col min-h-[calc(100vh-64px)] overflow-y-auto">
            <!-- Main Content -->
            <main class="flex-1 bg-slate-50 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('layouts.partials.datatables-lib')
    @stack('scripts')
    @include('layouts.partials.datatables-init')
</body>

</html>