@php
    $capabilities = [
        [
            'title' => 'Stock & IMEI',
            'desc' => 'Track devices by serial, branch transfers, purchases, and passthrough sales.',
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        ],
        [
            'title' => 'Agent network',
            'desc' => 'Regional managers, team leaders, and agents with assignments and commissions.',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        ],
        [
            'title' => 'Sales & credit',
            'desc' => 'Cash sales, agent credit, distribution channels, and payment options.',
            'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        [
            'title' => 'Branches',
            'desc' => 'Multi-branch stock, transfers, and location-based operations.',
            'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008H17.25v-.008zm0 3h.008v.008H17.25v-.008zm0 3h.008v.008H17.25v-.008z',
        ],
        [
            'title' => 'Reports',
            'desc' => 'Sales reports, agent stock, expenses, and financial overview.',
            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        ],
        [
            'title' => 'Storefront',
            'desc' => 'Optional customer-facing shop for dealers and end customers.',
            'icon' => 'M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z',
        ],
    ];

    $featureLabels = \App\Models\Package::FEATURES;
@endphp

<x-marketing-layout title="OpticEdge Africa — Phone distribution platform">
    {{-- Hero --}}
    <section class="max-w-6xl mx-auto px-4 pt-8 pb-10 sm:pt-12 sm:pb-14" aria-labelledby="hero-heading">
        <div class="admin-clay-panel p-8 sm:p-12 text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#fa8900] mb-3">B2B distribution platform</p>
            <h1 id="hero-heading"
                class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#232f3e] tracking-tight leading-tight max-w-3xl mx-auto">
                Run your phone business with stock, agents, and sales in one system
            </h1>
            <p class="mt-5 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                OpticEdge Africa helps mobile retailers manage inventory by IMEI, coordinate agents and branches,
                track distribution and credit sales, and grow with clear reports — built for the Tanzanian market.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row flex-wrap justify-center gap-3">
            <a href="{{ route('welcome') }}#packages"
                class="cursor-pointer inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white font-bold shadow-md transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">
                View packages
            </a>
                <a href="{{ route('login') }}"
                    class="cursor-pointer inline-flex items-center justify-center px-6 py-3 rounded-xl admin-clay-inset text-[#232f3e] font-semibold transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">
                    Sign in
                </a>
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <section id="about" class="max-w-6xl mx-auto px-4 pb-14 sm:pb-16" aria-labelledby="about-heading">
        <div class="admin-clay-panel p-6 sm:p-10">
            <h2 id="about-heading" class="text-2xl font-bold text-[#232f3e]">What we do</h2>
            <p class="mt-2 text-slate-600 max-w-3xl leading-relaxed">
                We provide a multi-tenant SaaS platform so each vendor gets their own admin workspace while the platform
                team manages shared catalogs, regions, brands, and subscription packages.
            </p>
            <ul class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 list-none p-0 m-0">
                @foreach ($capabilities as $item)
                    <li>
                        <article class="h-full p-5 rounded-2xl border border-white/80 bg-white/50 shadow-sm">
                            <div
                                class="w-10 h-10 rounded-xl admin-clay-inset flex items-center justify-center text-[#fa8900] mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                                    stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-[#232f3e]">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $item['desc'] }}</p>
                        </article>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Packages --}}
    <section id="packages" class="max-w-6xl mx-auto px-4 pb-14 sm:pb-20" aria-labelledby="packages-heading">
        <header class="text-center mb-8 sm:mb-10">
            <h2 id="packages-heading" class="text-2xl sm:text-3xl font-bold text-[#232f3e]">Subscription packages</h2>
            <p class="mt-2 text-slate-600 max-w-xl mx-auto">Choose a plan that fits your vendor size. Contact us for
                custom enterprise needs.</p>
        </header>

        @if ($packages->isEmpty())
            <div class="admin-clay-panel p-10 text-center">
                <svg class="mx-auto w-12 h-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.875 3 5.379 3 6v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <p class="mt-4 font-medium text-[#232f3e]">Packages coming soon</p>
                <p class="mt-1 text-sm text-slate-600">Check back soon — subscription packages will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($packages as $package)
                    <article
                        class="admin-clay-panel flex flex-col {{ $loop->first ? 'ring-2 ring-[#fa8900]/35' : '' }}"
                        aria-labelledby="package-{{ $package->id }}-name">
                        <div class="p-6 flex-1 flex flex-col">
                            @if ($loop->first)
                                <span
                                    class="inline-block self-start mb-3 rounded-lg bg-[#fa8900]/15 text-[#c76a00] px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide">Popular</span>
                            @endif
                            <h3 id="package-{{ $package->id }}-name" class="text-xl font-bold text-[#232f3e]">
                                {{ $package->name }}</h3>
                            @if ($package->description)
                                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $package->description }}</p>
                            @endif
                            <p class="mt-5">
                                <span class="text-3xl font-extrabold text-[#232f3e]">{{ $package->formattedPrice() }}</span>
                                <span class="text-slate-600 text-sm">/ {{ strtolower($package->intervalLabel()) }}</span>
                            </p>
                            @if (is_array($package->features_json) && count($package->features_json) > 0)
                                <ul class="mt-5 space-y-2.5 flex-1" role="list">
                                    @foreach ($package->features_json as $key => $value)
                                        @if ($value)
                                            <li class="flex gap-2.5 text-sm text-slate-600">
                                                <x-icons.check class="text-emerald-600" />
                                                <span>
                                                    @if (is_string($key) && $key !== '0')
                                                        {{ $featureLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @elseif ($package->max_users)
                                <p class="mt-4 text-sm text-slate-600">Up to {{ $package->max_users }} users</p>
                            @endif
                        </div>
                        <div class="px-6 pb-6 pt-0">
                            <a href="{{ route('vendor.subscribe', $package) }}"
                                class="cursor-pointer block w-full text-center py-3 rounded-xl font-bold text-white bg-[#232f3e] hover:bg-[#1a2430] transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]"
                                aria-label="Get started with {{ $package->name }}">
                                Get started
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Final CTA --}}
    <section class="max-w-6xl mx-auto px-4 pb-16 sm:pb-20" aria-labelledby="cta-heading">
        <div class="rounded-[1.75rem] bg-[#232f3e] text-white p-8 sm:p-12 text-center shadow-lg">
            <h2 id="cta-heading" class="text-2xl sm:text-3xl font-bold">Ready to digitize your phone business?</h2>
            <p class="mt-3 text-slate-300 max-w-xl mx-auto leading-relaxed">
                Join vendors using OpticEdge Africa to manage stock, teams, and revenue from one dashboard.
            </p>
            <a href="#packages"
                class="cursor-pointer inline-flex mt-6 px-8 py-3 rounded-xl bg-[#fa8900] hover:bg-[#e07800] font-bold transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Choose a package
            </a>
        </div>
    </section>
</x-marketing-layout>
