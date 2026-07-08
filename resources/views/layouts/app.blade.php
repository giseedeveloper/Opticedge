<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'opticadgeafrica') }}</title>
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
        [x-cloak] {
            display: none !important;
        }

        @theme {
            --color-brand-black: #232f3e;
            --color-brand-dark: #19212c;
            --color-brand-yellow: #febd69;
            --color-brand-orange: #fa8900;
            --color-brand-blue: #007185;

            --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;

            --shadow-card: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-card-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
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
    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-900">

    <!-- Header -->
    <header class="bg-[#232f3e] text-white sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto">
            <!-- Main Bar -->
            <div class="flex items-center gap-2 lg:gap-4 p-2 px-4 h-14 lg:h-16">
                <!-- Mobile Menu Icon (Visible on mobile only) -->
                <button class="lg:hidden p-1 hover:border border-transparent hover:border-white rounded-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Logo -->
                <a href="{{ route('shop') }}"
                    class="flex items-center pt-1 px-2 border border-transparent hover:border-white rounded-sm transition-all duration-200">
                    <span class="text-xl lg:text-2xl font-bold tracking-tight">opticedge<span
                            class="text-[#fa8900]">africa</span></span>
                </a>

                <!-- Location Picker (Hidden on mobile) -->
                <div
                    class="hidden lg:flex items-center px-2 py-1 border border-transparent hover:border-white rounded-sm cursor-pointer min-w-[120px] transition-all duration-200 group">
                    <div class="mr-1 mt-auto mb-1 text-slate-300 group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex flex-col justify-center leading-tight">
                        <span class="text-[12px] text-slate-300 block leading-none">Deliver to</span>
                        <span class="font-bold text-sm text-white leading-none mt-0.5">Dar es salaam</span>
                    </div>
                </div>

                <!-- Desktop Search (Visible on Desktop only) -->
                <livewire:global-search />

                <div class="flex-grow lg:hidden"></div> <!-- Spacer for mobile center-ish logo / right align icons -->

                <!-- Language (Simulated) (Hidden on mobile) -->
                <div
                    class="hidden lg:flex items-center gap-1 p-2 border border-transparent hover:border-white rounded-sm cursor-pointer min-w-[60px]">
                    <img src="https://flagcdn.com/w20/us.png" alt="US" class="w-5 h-3.5 object-cover">
                    <span class="font-bold text-sm">EN</span>
                    <svg class="w-2.5 h-2.5 fill-slate-300" viewBox="0 0 20 20">
                        <path
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                </div>

                <!-- Account/Sign In -->
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center lg:flex-col lg:justify-center px-2 py-1 border border-transparent hover:border-white rounded-sm cursor-pointer leading-tight lg:min-w-[124px] relative group gap-0.5 sm:gap-1">
                        <span class="hidden lg:block text-[12px] text-slate-300 h-3.5 leading-tight">Hello,
                            {{ Auth::user()->name }}</span>
                        <div class="flex items-center gap-0.5">
                            <span class="font-bold text-sm leading-tight hidden lg:block">Account & Lists</span>
                            <span class="text-xs mr-0.5 lg:hidden">{{ explode(' ', Auth::user()->name)[0] }} ›</span>
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="lg:hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <svg class="hidden lg:block w-2.5 h-2.5 fill-slate-300 text-slate-400 mt-1" viewBox="0 0 20 20">
                                <path
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="flex items-center lg:flex-col lg:justify-center px-2 py-1 border border-transparent hover:border-white rounded-sm cursor-pointer leading-tight lg:min-w-[124px] relative group gap-0.5 sm:gap-1">
                        <span class="hidden lg:block text-[12px] text-slate-300 h-3.5 leading-tight">Hello, sign in</span>
                        <div class="flex items-center gap-0.5">
                            <span class="font-bold text-sm leading-tight hidden lg:block">Account & Lists</span>
                            <span class="text-xs mr-0.5 lg:hidden">Sign in ›</span>
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="lg:hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <svg class="hidden lg:block w-2.5 h-2.5 fill-slate-300 text-slate-400 mt-1" viewBox="0 0 20 20">
                                <path
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </div>
                    </a>
                @endauth

                <div
                    class="hidden md:flex flex-col justify-center px-2 py-1 border border-transparent hover:border-white rounded-sm cursor-pointer leading-tight min-w-[75px]">
                    <span class="text-[12px] text-slate-300 block h-3.5 leading-tight">Returns</span>
                    <span class="font-bold text-sm leading-tight">& Orders</span>
                </div>

                <!-- Cart -->
                <a href="/cart"
                    class="flex items-end px-1 sm:px-2 py-1 border border-transparent hover:border-white rounded-sm transition-all gap-1 relative">
                    <div class="relative flex items-end">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 lg:h-9 lg:w-9 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            class="absolute -top-1 left-[15px] lg:left-[17px] -translate-x-1/2 text-[#f08804] font-bold text-sm lg:text-[16px] leading-none">{{ $cartCount }}</span>
                    </div>
                    <span class="font-bold text-sm mb-1 hidden sm:block">Cart</span>
                </a>
            </div>

            <!-- Mobile Search Bar (Visible on mobile only) -->
            <div class="lg:hidden px-4 pb-3">
                <div
                    class="flex h-11 rounded-md overflow-hidden ring-2 ring-transparent focus-within:ring-[#fa8900] shadow-sm">
                    <input type="text" placeholder="Search for devices"
                        class="flex-grow px-4 text-slate-900 bg-white placeholder:text-slate-500 focus:outline-none text-base font-medium">
                    <button
                        class="bg-[#febd69] hover:bg-[#fa8900] text-[#131921] px-5 transition-colors duration-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>





    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-12 bg-[#232f3e] text-white border-t border-slate-700">
        <div class="border-t border-slate-700/50 py-10 flex flex-col items-center gap-6 bg-[#131921]">
            <div class="text-2xl font-bold italic tracking-tighter flex items-baseline gap-1">
                <span>opticedge</span><span class="text-[#ff9900] text-sm not-italic">africa</span>
            </div>

            <div class="flex flex-wrap justify-center gap-1 sm:gap-6 px-4">
                <button
                    class="border border-slate-500 rounded px-4 py-1.5 text-xs text-slate-300 hover:border-slate-300">English</button>
                <button
                    class="border border-slate-500 rounded px-4 py-1.5 text-xs text-slate-300 hover:border-slate-300">
                    TZS</button>
                <button
                    class="border border-slate-500 rounded px-4 py-1.5 text-xs text-slate-300 hover:border-slate-300 flex items-center gap-2">
                    <img src="https://flagcdn.com/w20/tz.png" class="w-4" alt=""> Tanzania
                </button>
            </div>

            <div class="flex flex-wrap justify-center gap-4 text-xs text-slate-400 mt-4">
                <a href="{{ route('terms') }}" class="hover:underline hover:text-white">Terms of Service</a>
                <a href="{{ route('privacy') }}" class="hover:underline hover:text-white">Privacy Policy</a>
            </div>
            <p class="text-[10px] text-slate-500">© 2026 OpticEdgeAfrica, Inc. All rights reserved.</p>
        </div>
    </footer>

    @stack('scripts')
    @livewireScripts
</body>

</html>