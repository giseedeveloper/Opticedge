<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

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

    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


    <!-- Styles / Scripts -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/css">
        @theme {
            --color-brand-black: #232f3e;
            --color-brand-dark: #19212c;
            --color-brand-yellow: #febd69;
            --color-brand-orange: #fa8900;
            --color-brand-blue: #007185;

            --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
        }

        /* Keep autofilled inputs visually aligned with the design */
        .auth-input:-webkit-autofill,
        .auth-input:-webkit-autofill:hover,
        .auth-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #0f172a;
            transition: background-color 9999s ease-out;
            box-shadow: inset 0 0 0 1000px #f8fafc;
        }

        .auth-input:focus:-webkit-autofill {
            box-shadow: inset 0 0 0 1000px #ffffff;
        }
    </style>
    @livewireStyles
    @vite(['resources/js/app.js'])
</head>

<body
    class="font-sans antialiased text-slate-900 min-h-full flex flex-col justify-center items-center py-10 sm:py-14 px-4 sm:px-6 bg-gradient-to-br from-slate-100 via-white to-orange-50/40">
    <div class="w-full max-w-md text-center mb-8">
        <a href="/" class="inline-flex justify-center group">
            <span class="text-3xl sm:text-4xl font-bold tracking-tight text-[#232f3e] transition group-hover:text-[#19212c]">opticedg<span
                    class="text-[#fa8900]">eafrica</span></span>
        </a>
    </div>

    <div class="w-full max-w-md">
        <div
            class="bg-white/90 backdrop-blur-sm py-9 px-5 sm:px-10 rounded-2xl shadow-[0_25px_50px_-12px_rgba(15,23,42,0.12)] ring-1 ring-slate-900/5 border border-white/80">
            {{ $slot }}
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            <p>&copy; 2026 OpticEdgeAfrica, Inc. All rights reserved.</p>
        </div>
    </div>
    @livewireScripts
</body>

</html>