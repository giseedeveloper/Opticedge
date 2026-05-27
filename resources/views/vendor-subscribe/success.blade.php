<x-marketing-layout title="Welcome — OpticEdge Africa">
    <div class="max-w-xl mx-auto px-4 py-12">
        <div class="admin-clay-panel p-8 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#232f3e]">Your vendor account is ready</h1>
            <p class="mt-2 text-slate-600">
                <strong>{{ $intent->vendor_name }}</strong> is subscribed to
                <strong>{{ $intent->package->name }}</strong>.
            </p>

            @if (!empty($credentials))
                <div class="mt-8 text-left rounded-xl border border-[#fa8900]/30 bg-orange-50/80 p-5">
                    <p class="text-sm font-bold text-[#232f3e] mb-3">Admin login credentials</p>
                    <p class="text-xs text-slate-600 mb-4">Save these now. The password is shown only once.</p>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Email</dt>
                            <dd class="font-mono font-semibold text-[#232f3e] break-all">{{ $credentials['email'] }}</dd>
                        </div>
                        @if (!empty($credentials['password']))
                            <div>
                                <dt class="text-slate-500">Password</dt>
                                <dd class="font-mono font-semibold text-[#232f3e] break-all">{{ $credentials['password'] }}</dd>
                            </div>
                        @elseif (!empty($credentials['note']))
                            <p class="text-slate-600">{{ $credentials['note'] }}</p>
                        @endif
                        <div>
                            <dt class="text-slate-500">Admin URL</dt>
                            <dd class="font-mono text-[#007185] break-all">{{ $credentials['login_url'] ?? route('login') }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('login') }}"
                    class="cursor-pointer inline-flex justify-center px-6 py-3 rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white font-bold text-sm transition-colors duration-200">
                    Sign in to admin
                </a>
                <a href="{{ route('welcome') }}"
                    class="cursor-pointer inline-flex justify-center px-6 py-3 rounded-xl admin-clay-inset text-sm font-semibold text-[#232f3e]">
                    Back to home
                </a>
            </div>
        </div>
    </div>
</x-marketing-layout>
