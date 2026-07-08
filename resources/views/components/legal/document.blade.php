@props([
    'title',
    'effectiveDate',
    'badge' => 'Legal',
    'description' => null,
    'sections' => [],
    'footerNote' => 'By continuing to use OpticEdge Africa services, you acknowledge that you have read and understood this document.',
])

<section class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
    {{-- Hero --}}
    <div class="rounded-3xl bg-gradient-to-br from-[#232f3e] via-[#2a3849] to-[#1a2430] text-white px-6 py-10 sm:px-10 sm:py-12 shadow-xl shadow-[#232f3e]/20 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#fa8900]/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3" aria-hidden="true"></div>
        <div class="relative max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#fa8900]">{{ $badge }}</p>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl">{{ $description }}</p>
            @endif
            <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm text-slate-200 ring-1 ring-white/15">
                <svg class="h-4 w-4 text-[#fa8900]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Effective date: {{ $effectiveDate }}
            </div>
        </div>
    </div>

    <div class="mt-8 lg:mt-10 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start">
        @if (count($sections) > 0)
            <aside class="lg:sticky lg:top-28">
                <div class="rounded-2xl border border-slate-200/80 bg-white/90 backdrop-blur-sm p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400 mb-4">On this page</p>
                    <nav class="space-y-1 max-h-[70vh] overflow-y-auto pr-1" aria-label="Table of contents">
                        @foreach ($sections as $section)
                            <a href="#{{ $section['id'] }}"
                                class="block rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-[#fa8900]/10 hover:text-[#232f3e] transition-colors leading-snug">
                                {{ $section['label'] }}
                            </a>
                        @endforeach
                    </nav>
                    <div class="mt-5 pt-5 border-t border-slate-100 space-y-2">
                        <a href="{{ route('privacy') }}"
                            class="block text-sm font-medium {{ request()->routeIs('privacy') ? 'text-[#fa8900]' : 'text-slate-500 hover:text-[#232f3e]' }}">
                            Privacy Policy
                        </a>
                        <a href="{{ route('terms') }}"
                            class="block text-sm font-medium {{ request()->routeIs('terms') ? 'text-[#fa8900]' : 'text-slate-500 hover:text-[#232f3e]' }}">
                            Terms of Service
                        </a>
                    </div>
                </div>
            </aside>
        @endif

        <article class="legal-document rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 shadow-lg shadow-slate-200/50">
            {{ $slot }}

            <div class="legal-callout mt-10">
                <p class="text-sm leading-relaxed text-slate-600">
                    {{ $footerNote ?? 'By continuing to use OpticEdge Africa services, you acknowledge that you have read and understood this document.' }}
                </p>
            </div>
        </article>
    </div>
</section>

<style>
    .legal-document {
        color: #475569;
        font-size: 1rem;
        line-height: 1.75;
    }

    .legal-document > p,
    .legal-document .legal-lead {
        margin-bottom: 1.25rem;
        color: #475569;
    }

    .legal-document .legal-lead {
        font-size: 1.0625rem;
        line-height: 1.8;
        color: #334155;
    }

    .legal-document h2 {
        margin-top: 2.75rem;
        margin-bottom: 1rem;
        padding-top: 0.25rem;
        font-size: 1.375rem;
        font-weight: 800;
        line-height: 1.35;
        color: #232f3e;
        letter-spacing: -0.02em;
        scroll-margin-top: 7rem;
    }

    .legal-document h2:first-of-type {
        margin-top: 0;
    }

    .legal-document h3 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-size: 1.0625rem;
        font-weight: 700;
        color: #1e293b;
    }

    .legal-document ul {
        margin: 0 0 1.25rem 0;
        padding-left: 0;
        list-style: none;
    }

    .legal-document ul li {
        position: relative;
        margin-bottom: 0.625rem;
        padding-left: 1.5rem;
        color: #475569;
    }

    .legal-document ul li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.72em;
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 9999px;
        background: #fa8900;
    }

    .legal-document a {
        color: #007185;
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .legal-document a:hover {
        color: #fa8900;
    }

    .legal-document strong {
        color: #1e293b;
        font-weight: 700;
    }

    .legal-section {
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .legal-section:last-of-type {
        border-bottom: 0;
    }

    .legal-callout {
        padding: 1.25rem 1.5rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, #f8fafc 0%, #fff7ed 100%);
        border: 1px solid #e2e8f0;
    }
</style>
