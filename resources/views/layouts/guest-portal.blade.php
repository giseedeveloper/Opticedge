<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Guest portal' }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('app-icon.png') }}">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    @livewireStyles
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-white to-orange-50/40 text-slate-900 antialiased">
    <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('guest.dashboard') }}" class="text-lg font-bold text-[#232f3e]">Optic<span class="text-[#fa8900]">Guest</span></a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">Sign out</button>
            </form>
        </div>
        <nav class="max-w-3xl mx-auto px-4 pb-3 flex gap-2">
            <a href="{{ route('guest.dashboard') }}"
                class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request()->routeIs('guest.dashboard') ? 'bg-[#fa8900] text-white' : 'text-slate-600 hover:bg-slate-100' }}">Home</a>
            <a href="{{ route('guest.requests') }}"
                class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request()->routeIs('guest.requests') ? 'bg-[#fa8900] text-white' : 'text-slate-600 hover:bg-slate-100' }}">Requests</a>
            <a href="{{ route('guest.profile') }}"
                class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request()->routeIs('guest.profile') ? 'bg-[#fa8900] text-white' : 'text-slate-600 hover:bg-slate-100' }}">Profile</a>
        </nav>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
