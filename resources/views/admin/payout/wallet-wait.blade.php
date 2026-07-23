<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page max-w-2xl mx-auto text-center py-8" x-data="walletTopupPoller()">
        <div x-show="status === 'pending'" class="mb-6 flex justify-center">
            <svg class="h-20 w-20 text-[#fa8900] animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div x-show="status === 'completed'" x-cloak class="mb-6 flex justify-center">
            <svg class="h-20 w-20 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div x-show="status === 'error' || status === 'failed'" x-cloak class="mb-6 flex justify-center">
            <svg class="h-20 w-20 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div x-show="status === 'pending'">
            <h2 class="admin-prod-title mb-2">Approve on your phone</h2>
            <p class="admin-prod-subtitle mb-6">
                A Selcom prompt was sent to <strong>{{ $selcompay->phone_number }}</strong> for
                <strong>{{ number_format((float) $selcompay->amount, 0) }} TZS</strong>. Enter your PIN to complete the top-up.
            </p>
            <p class="text-sm text-slate-600" x-text="message">Checking status…</p>
        </div>

        <div x-show="status === 'completed'" x-cloak>
            <h2 class="text-xl font-bold text-emerald-700 mb-2">Wallet topped up</h2>
            <p class="text-slate-600 mb-6" x-text="message"></p>
            <a href="{{ route('admin.payout.index') }}" class="admin-prod-btn-primary inline-flex">Back to Pay out</a>
        </div>

        <div x-show="status === 'error' || status === 'failed'" x-cloak>
            <h2 class="text-xl font-bold text-red-700 mb-2">Top-up not completed</h2>
            <p class="text-slate-600 mb-6" x-text="message"></p>
            <a href="{{ route('admin.payout.index') }}" class="admin-prod-btn-primary inline-flex">Back to Pay out</a>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('walletTopupPoller', () => ({
                    status: 'pending',
                    message: 'Checking status…',
                    pollCount: 0,
                    maxPolls: 120,
                    statusUrl: @json(route('admin.payout.wallet.status', $selcompay)),

                    init() {
                        this.poll();
                    },

                    poll() {
                        if (this.status !== 'pending') return;
                        if (this.pollCount >= this.maxPolls) {
                            this.status = 'error';
                            this.message = 'Timed out waiting for Selcom.';
                            return;
                        }
                        this.pollCount++;

                        fetch(this.statusUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.message) this.message = data.message;
                                if (data.status === 'completed') {
                                    this.status = 'completed';
                                } else if (data.status === 'failed') {
                                    this.status = 'failed';
                                } else if (data.status === 'error') {
                                    this.status = 'error';
                                } else {
                                    setTimeout(() => this.poll(), 3000);
                                }
                            })
                            .catch(() => {
                                this.status = 'error';
                                this.message = 'Could not reach the server.';
                            });
                    }
                }));
            });
        </script>
    @endpush
</x-admin-layout>
