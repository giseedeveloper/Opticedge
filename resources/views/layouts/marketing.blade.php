<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="OpticEdge Africa — multi-vendor platform for phone retailers: stock, agents, branches, and sales in one place.">
    <title>{{ $title ?? config('app.name', 'OpticEdge Africa') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @include('layouts.partials.admin-surface-styles')
    @livewireStyles
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased text-slate-600 min-h-screen bg-gradient-to-br from-[#dce3ee] via-[#e8edf5] to-[#d4dce8]"
    x-data="{ mobileNavOpen: false }">

    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-xl focus:bg-white focus:text-[#232f3e] focus:font-semibold focus:shadow-lg">
        Skip to content
    </a>

    <header class="sticky top-0 z-50 px-3 pt-3 sm:px-4 sm:pt-4">
        <div
            class="max-w-6xl mx-auto rounded-[1.75rem] border border-white/80 bg-gradient-to-br from-[#232f3e] via-[#2a3849] to-[#232f3e] text-white shadow-[10px_14px_28px_rgba(35,47,62,0.35),inset_0_1px_0_rgba(255,255,255,0.08)] overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                <a href="{{ route('welcome') }}"
                    class="text-lg sm:text-xl font-bold tracking-tight rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">
                    opticedg<span class="text-[#fa8900]">eafrica</span>
                </a>

                <nav class="hidden md:flex items-center gap-1 text-sm font-medium" aria-label="Primary">
                    <a href="{{ route('welcome') }}#about"
                        class="px-3 py-2 rounded-xl text-slate-200 hover:text-white hover:bg-white/10 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">About</a>
                    <a href="{{ route('welcome') }}#packages"
                        class="px-3 py-2 rounded-xl text-slate-200 hover:text-white hover:bg-white/10 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">Packages</a>
                    <a href="{{ route('shop') }}"
                        class="px-3 py-2 rounded-xl text-slate-200 hover:text-white hover:bg-white/10 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">Shop</a>
                </nav>

                <div class="hidden md:flex items-center gap-2 shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="cursor-pointer px-4 py-2 rounded-xl text-sm font-semibold text-slate-200 hover:text-white hover:bg-white/10 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="cursor-pointer px-4 py-2 rounded-xl text-sm font-semibold text-slate-200 hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">Sign in</a>
                        <a href="{{ route('dealer.register') }}"
                            class="cursor-pointer px-4 py-2 rounded-xl text-sm font-semibold bg-[#fa8900] hover:bg-[#e07800] text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Get started</a>
                    @endauth
                </div>

                <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                    class="md:hidden cursor-pointer p-2 rounded-xl bg-white/10 hover:bg-white/15 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]"
                    :aria-expanded="mobileNavOpen" aria-controls="mobile-nav">
                    <span class="sr-only">Menu</span>
                    <svg x-show="!mobileNavOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileNavOpen" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav id="mobile-nav" x-show="mobileNavOpen" x-cloak x-transition
                class="md:hidden border-t border-white/10 px-4 py-3 space-y-1" aria-label="Mobile">
                <a href="{{ route('welcome') }}#about" @click="mobileNavOpen = false"
                    class="block cursor-pointer px-3 py-2.5 rounded-xl text-slate-200 hover:bg-white/10 hover:text-white transition-colors duration-200">About</a>
                <a href="{{ route('welcome') }}#packages" @click="mobileNavOpen = false"
                    class="block cursor-pointer px-3 py-2.5 rounded-xl text-slate-200 hover:bg-white/10 hover:text-white transition-colors duration-200">Packages</a>
                <a href="{{ route('shop') }}" @click="mobileNavOpen = false"
                    class="block cursor-pointer px-3 py-2.5 rounded-xl text-slate-200 hover:bg-white/10 hover:text-white transition-colors duration-200">Shop</a>
                <div class="pt-2 flex flex-col gap-2 border-t border-white/10 mt-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="cursor-pointer text-center px-4 py-2.5 rounded-xl font-semibold bg-white/10 hover:bg-white/15 transition-colors duration-200">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="cursor-pointer text-center px-4 py-2.5 rounded-xl font-semibold text-slate-200 hover:bg-white/10 transition-colors duration-200">Sign in</a>
                        <a href="{{ route('dealer.register') }}"
                            class="cursor-pointer text-center px-4 py-2.5 rounded-xl font-semibold bg-[#fa8900] hover:bg-[#e07800] text-white transition-colors duration-200">Get started</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-white/50 bg-[#232f3e] text-slate-300">
        <div class="max-w-6xl mx-auto px-4 py-10 flex flex-col sm:flex-row justify-between gap-6 text-sm">
            <div>
                <p class="font-bold text-white text-lg">opticedg<span class="text-[#fa8900]">eafrica</span></p>
                <p class="mt-2 max-w-sm text-slate-400">Phone distribution software for retailers, agents, and regional
                    teams across Tanzania.</p>
            </div>
            <nav class="flex flex-wrap gap-x-6 gap-y-2" aria-label="Footer">
                <a href="{{ route('welcome') }}#about"
                    class="cursor-pointer hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900] rounded">About</a>
                <a href="{{ route('welcome') }}#packages"
                    class="cursor-pointer hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900] rounded">Packages</a>
                <a href="{{ route('shop') }}"
                    class="cursor-pointer hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900] rounded">Browse shop</a>
                <a href="{{ route('login') }}"
                    class="cursor-pointer hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900] rounded">Sign in</a>
                <a href="{{ route('dealer.register') }}"
                    class="cursor-pointer hover:text-white transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900] rounded">Register as dealer</a>
            </nav>
        </div>
        <p class="text-center text-xs text-slate-500 pb-6">&copy; {{ date('Y') }} OpticEdge Africa. All rights reserved.</p>
    </footer>
    @livewireScripts
</body>

</html>
