<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-12 text-center" x-data="vendorPaymentPoller()">
        <div x-show="status === 'pending'" class="mb-4 flex justify-center">
            <svg class="h-20 w-20 text-[#fa8900] animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
        </div>
        <div x-show="status === 'completed'" x-cloak class="mb-4 flex justify-center">
            <svg class="h-20 w-20 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div x-show="status === 'failed' || status === 'error'" x-cloak class="mb-4 flex justify-center">
            <svg class="h-20 w-20 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="admin-clay-panel p-8">
            <h1 class="text-xl font-bold text-[#232f3e]" x-text="status === 'completed' ? 'Payment successful' : (status === 'pending' ? pendingTitle : 'Payment issue')"></h1>
            <p class="mt-3 text-slate-600" x-text="message"></p>
            @if ($paymentPhone)
                <p class="mt-2 text-sm text-slate-500">Number ending <strong>{{ substr($paymentPhone, -4) }}</strong></p>
            @endif

            <div x-show="status === 'failed' || status === 'error'" x-cloak class="mt-6">
                <a href="{{ route('admin.tenant.edit') }}"
                    class="inline-flex px-6 py-3 rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white font-bold text-sm">
                    Try again
                </a>
            </div>
        </div>
    </div>

    @php
        $statusUrl = route('admin.tenant.subscribe.status', $intent);
        $successUrl = route('admin.tenant.subscribe.success', $intent);
        $isDemoPayment = $isDemoPayment ?? false;
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vendorPaymentPoller', () => ({
                status: 'pending',
                pendingTitle: @json($isDemoPayment ? 'Activating your subscription' : 'Approve payment on your phone'),
                message: @json($isDemoPayment ? 'Demo mode — restoring your account…' : 'Waiting for payment confirmation…'),
                pollIntervalMs: @json($isDemoPayment ? 500 : 3000),
                pollCount: 0,
                maxPolls: @json($isDemoPayment ? 20 : 120),
                async poll() {
                    if (this.status !== 'pending') return;
                    this.pollCount++;
                    try {
                        const res = await fetch(@json($statusUrl), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        this.status = data.status || 'pending';
                        this.message = data.message || this.message;
                        if (this.status === 'completed') {
                            window.location.href = @json($successUrl);
                            return;
                        }
                    } catch (e) {
                        this.message = 'Could not verify payment. Retrying…';
                    }
                    if (this.pollCount < this.maxPolls && this.status === 'pending') {
                        setTimeout(() => this.poll(), this.pollIntervalMs);
                    } else if (this.status === 'pending') {
                        this.status = 'error';
                        this.message = 'Payment verification timed out. Try again from the subscription page.';
                    }
                },
                init() {
                    this.poll();
                }
            }));
        });
    </script>
</x-admin-layout>
