<x-marketing-layout title="Processing payment — OpticEdge Africa">
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
            <h1 class="text-xl font-bold text-[#232f3e]" x-text="status === 'completed' ? 'Payment successful' : (status === 'pending' ? 'Approve payment on your phone' : 'Payment issue')"></h1>
            <p class="mt-3 text-slate-600" x-text="message"></p>
            @if ($paymentPhone)
                <p class="mt-2 text-sm text-slate-500">Number ending <strong>{{ substr($paymentPhone, -4) }}</strong></p>
            @endif

            <div x-show="status === 'failed' || status === 'error'" x-cloak class="mt-6">
                <a href="{{ route('vendor.subscribe', $intent->package) }}"
                    class="cursor-pointer inline-flex px-6 py-3 rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white font-bold text-sm">
                    Try again
                </a>
            </div>
        </div>
    </div>

    @php
        $statusUrl = route('vendor.subscribe.status', $intent);
        $successUrl = route('vendor.subscribe.success', $intent);
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vendorPaymentPoller', () => ({
                status: 'pending',
                message: 'Waiting for Selcom confirmation…',
                pollCount: 0,
                maxPolls: 120,
                statusUrl: @json($statusUrl),
                successUrl: @json($successUrl),
                init() {
                    this.poll();
                },
                poll() {
                    if (this.status !== 'pending') return;
                    if (this.pollCount >= this.maxPolls) {
                        this.status = 'error';
                        this.message = 'Payment timed out. Please try again.';
                        return;
                    }
                    this.pollCount++;
                    fetch(this.statusUrl)
                        .then(res => res.json())
                        .then(data => {
                            if (data.message) this.message = data.message;
                            if (data.status === 'completed') {
                                this.status = 'completed';
                                setTimeout(() => {
                                    window.location.href = this.successUrl;
                                }, 1500);
                            } else if (data.status === 'failed' || data.status === 'error') {
                                this.status = data.status;
                            } else {
                                setTimeout(() => this.poll(), 3000);
                            }
                        })
                        .catch(() => {
                            this.status = 'error';
                            this.message = 'Could not verify payment. Check your connection.';
                        });
                }
            }));
        });
    </script>
</x-marketing-layout>
