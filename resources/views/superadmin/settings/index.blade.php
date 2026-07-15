<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{ tab: 'selcom' }">
        <div class="mb-8">
            <p class="admin-prod-eyebrow">Platform</p>
            <h1 class="admin-prod-title">Platform settings</h1>
            <p class="admin-prod-subtitle">Vendor signup payments, Selcom gateway, authentication, and system email.</p>
        </div>

        @include('superadmin.partials.flash')

        <div class="mb-5 inline-flex rounded-xl bg-white/70 p-1 border border-white/80">
            <button type="button" @click="tab = 'selcom'"
                :class="tab === 'selcom' ? 'bg-[#fa8900] text-white' : 'text-slate-600'"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors duration-200">
                Selcom
            </button>
            <button type="button" @click="tab = 'email'"
                :class="tab === 'email' ? 'bg-[#fa8900] text-white' : 'text-slate-600'"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors duration-200">
                Email
            </button>
            <button type="button" @click="tab = 'auth'"
                :class="tab === 'auth' ? 'bg-[#fa8900] text-white' : 'text-slate-600'"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors duration-200">
                Authentication
            </button>
        </div>

        <form action="{{ route('superadmin.settings.update') }}" method="POST">
            @csrf

            <div x-show="tab === 'selcom'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Vendor subscription payments</h2>
                    <p class="admin-prod-form-hint">Controls the public package signup flow at <code class="text-xs">/subscribe/{package}</code>.</p>
                </div>
                <div class="admin-prod-form-body space-y-6">
                    @php
                        $paymentMode = $settings['vendor_subscription_payment_mode'] ?? 'demo';
                    @endphp
                    <div>
                        <label for="vendor_subscription_payment_mode" class="admin-prod-label">Payment mode</label>
                        <select name="vendor_subscription_payment_mode" id="vendor_subscription_payment_mode" class="admin-prod-select">
                            <option value="demo" @selected($paymentMode === 'demo')>Demo — instant success (no mobile money push)</option>
                            <option value="live" @selected($paymentMode === 'live')>Live — Selcom API + USSD approval required</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-2">
                            <strong>Demo:</strong> user picks a package and completes signup without real payment.<br>
                            <strong>Live:</strong> Selcom credentials below must be set; customer must approve payment on their phone.
                        </p>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'selcom'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden mt-6">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Selcom configuration</h2>
                    <p class="admin-prod-form-hint">Required for <strong>Live</strong> vendor signup and used for storefront checkout, commission payouts, and webhooks.</p>
                </div>
                <div class="admin-prod-form-body space-y-6">
                    <div>
                        <label for="selcom_vendor_id" class="admin-prod-label">Vendor ID</label>
                        <input type="text" name="selcom_vendor_id" id="selcom_vendor_id"
                            value="{{ $settings['selcom_vendor_id'] ?? '' }}" class="admin-prod-input">
                    </div>
                    <div>
                        <label for="selcom_api_key" class="admin-prod-label">API key</label>
                        <input type="text" name="selcom_api_key" id="selcom_api_key"
                            value="{{ $settings['selcom_api_key'] ?? '' }}" class="admin-prod-input" autocomplete="off">
                    </div>
                    <div>
                        <label for="selcom_api_secret" class="admin-prod-label">API secret</label>
                        <input type="password" name="selcom_api_secret" id="selcom_api_secret"
                            value="{{ $settings['selcom_api_secret'] ?? '' }}" class="admin-prod-input"
                            autocomplete="new-password">
                    </div>
                    <div>
                        <label for="selcom_is_live" class="admin-prod-label">Environment</label>
                        <select name="selcom_is_live" id="selcom_is_live" class="admin-prod-select">
                            <option value="0" @selected(($settings['selcom_is_live'] ?? '0') == '0')>Test (apigwtest.selcommobile.com)</option>
                            <option value="1" @selected(($settings['selcom_is_live'] ?? '0') == '1')>Live (apigw.selcommobile.com)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-2">Use <strong>Live</strong> for production Selcom gateway; <strong>Test</strong> for Selcom sandbox (still requires real USSD when vendor signup is in Live mode).</p>
                    </div>

                    <div class="pt-2 border-t border-white/60" x-data="selcomApiTest()">
                        <p class="admin-prod-label mb-2">API connection test</p>
                        <button type="button" @click="runTest" :disabled="testing"
                            class="admin-prod-btn-ghost text-sm disabled:opacity-60">
                            <span x-show="!testing">Test Selcom API</span>
                            <span x-show="testing" x-cloak>Testing…</span>
                        </button>
                        <p x-show="resultMessage" x-cloak class="mt-3 text-sm rounded-xl px-3 py-2"
                            :class="resultOk ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'"
                            x-text="resultMessage"></p>
                    </div>

                    <div class="pt-4 border-t border-white/60">
                        <button type="submit" class="admin-prod-btn-primary px-8">Save Selcom settings</button>
                        <p class="text-xs text-slate-500 mt-2">Save your Vendor ID, API key and secret before running the payment tests below.</p>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'selcom'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden mt-6"
                x-data="selcomPaymentTest()">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Test payments</h2>
                    <p class="admin-prod-form-hint">Check that mobile money and card payments work with the credentials above. Uses throwaway test orders — no real order is created.</p>
                </div>
                <div class="admin-prod-form-body space-y-8">
                    {{-- Mobile money test --}}
                    <div>
                        <p class="admin-prod-label mb-2">Mobile money (all mobile money)</p>
                        <p class="text-xs text-slate-500 mb-3">Sends a real USSD approval prompt to the phone number below. Approve it on the phone, then check the status.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="test_mobile_phone" class="text-xs font-medium text-slate-600">Phone number</label>
                                <input type="text" id="test_mobile_phone" x-model="mobile.phone"
                                    placeholder="0712345678" class="admin-prod-input" autocomplete="off">
                            </div>
                            <div>
                                <label for="test_mobile_amount" class="text-xs font-medium text-slate-600">Amount (TZS)</label>
                                <input type="number" id="test_mobile_amount" x-model="mobile.amount"
                                    min="100" class="admin-prod-input">
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" @click="runMobile" :disabled="mobile.testing"
                                class="admin-prod-btn-ghost text-sm disabled:opacity-60">
                                <span x-show="!mobile.testing">Send test push</span>
                                <span x-show="mobile.testing" x-cloak>Sending…</span>
                            </button>
                            <button type="button" x-show="mobile.orderId" x-cloak @click="checkStatus('mobile')"
                                :disabled="mobile.checking" class="admin-prod-btn-ghost text-sm disabled:opacity-60">
                                <span x-show="!mobile.checking">Check status</span>
                                <span x-show="mobile.checking" x-cloak>Checking…</span>
                            </button>
                        </div>
                        <p x-show="mobile.message" x-cloak class="mt-3 text-sm rounded-xl px-3 py-2"
                            :class="mobile.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'"
                            x-text="mobile.message"></p>
                    </div>

                    {{-- Card test --}}
                    <div class="pt-6 border-t border-white/60">
                        <p class="admin-prod-label mb-2">Card / Bank</p>
                        <p class="text-xs text-slate-500 mb-3">Creates a test order and opens the Selcom hosted checkout, where you can pay by card or bank to confirm those channels work.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="test_card_amount" class="text-xs font-medium text-slate-600">Amount (TZS)</label>
                                <input type="number" id="test_card_amount" x-model="card.amount"
                                    min="100" class="admin-prod-input">
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" @click="runCard" :disabled="card.testing"
                                class="admin-prod-btn-ghost text-sm disabled:opacity-60">
                                <span x-show="!card.testing">Create card / bank test link</span>
                                <span x-show="card.testing" x-cloak>Creating…</span>
                            </button>
                            <a x-show="card.gatewayUrl" x-cloak :href="card.gatewayUrl" target="_blank" rel="noopener"
                                class="admin-prod-btn-primary text-sm px-4">Open checkout to pay by card or bank</a>
                            <button type="button" x-show="card.orderId" x-cloak @click="checkStatus('card')"
                                :disabled="card.checking" class="admin-prod-btn-ghost text-sm disabled:opacity-60">
                                <span x-show="!card.checking">Check status</span>
                                <span x-show="card.checking" x-cloak>Checking…</span>
                            </button>
                        </div>
                        <p x-show="card.message" x-cloak class="mt-3 text-sm rounded-xl px-3 py-2"
                            :class="card.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'"
                            x-text="card.message"></p>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'email'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Email configuration</h2>
                    <p class="admin-prod-form-hint">SMTP and sender details for platform emails.</p>
                </div>
                <div class="admin-prod-form-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_mailer" class="admin-prod-label">Mailer</label>
                            <input type="text" name="mail_mailer" id="mail_mailer"
                                value="{{ $settings['mail_mailer'] ?? '' }}" class="admin-prod-input" placeholder="smtp">
                        </div>
                        <div>
                            <label for="mail_host" class="admin-prod-label">Host</label>
                            <input type="text" name="mail_host" id="mail_host"
                                value="{{ $settings['mail_host'] ?? '' }}" class="admin-prod-input"
                                placeholder="smtp.example.com">
                        </div>
                        <div>
                            <label for="mail_port" class="admin-prod-label">Port</label>
                            <input type="number" name="mail_port" id="mail_port"
                                value="{{ $settings['mail_port'] ?? '' }}" class="admin-prod-input" placeholder="587"
                                min="1" max="65535">
                        </div>
                        <div>
                            <label for="mail_encryption" class="admin-prod-label">Encryption</label>
                            <input type="text" name="mail_encryption" id="mail_encryption"
                                value="{{ $settings['mail_encryption'] ?? '' }}" class="admin-prod-input" placeholder="tls">
                        </div>
                        <div>
                            <label for="mail_username" class="admin-prod-label">Username</label>
                            <input type="text" name="mail_username" id="mail_username"
                                value="{{ $settings['mail_username'] ?? '' }}" class="admin-prod-input">
                        </div>
                        <div>
                            <label for="mail_password" class="admin-prod-label">Password</label>
                            <input type="password" name="mail_password" id="mail_password"
                                value="{{ $settings['mail_password'] ?? '' }}" class="admin-prod-input"
                                autocomplete="new-password">
                        </div>
                        <div>
                            <label for="mail_from_address" class="admin-prod-label">From address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address"
                                value="{{ $settings['mail_from_address'] ?? '' }}" class="admin-prod-input"
                                placeholder="no-reply@example.com">
                        </div>
                        <div>
                            <label for="mail_from_name" class="admin-prod-label">From name</label>
                            <input type="text" name="mail_from_name" id="mail_from_name"
                                value="{{ $settings['mail_from_name'] ?? '' }}" class="admin-prod-input"
                                placeholder="OpticEdge Africa">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">
                        Values are applied at runtime for outgoing mail across the platform.
                    </p>
                </div>
            </div>

            <div x-show="tab === 'auth'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Authentication</h2>
                    <p class="admin-prod-form-hint">Control sign-in requirements for all users across the platform.</p>
                </div>
                <div class="admin-prod-form-body space-y-4">
                    @php
                        $emailVerificationRequired = ($settings['require_email_verification_on_login'] ?? '0') === '1';
                    @endphp
                    <input type="hidden" name="require_email_verification_on_login" value="0">
                    <label class="flex items-start justify-between gap-4 cursor-pointer rounded-xl border border-slate-200/80 bg-white/60 px-4 py-4">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900">Require email verification on login</span>
                            <span class="mt-1 block text-xs text-slate-500 leading-relaxed">
                                When enabled, users must verify their email before they can sign in (email/password or Google).
                                Email and password login continues to work normally when this is off.
                            </span>
                        </span>
                        <span class="relative inline-flex h-7 w-12 shrink-0 items-center">
                            <input type="checkbox" name="require_email_verification_on_login" value="1"
                                @checked($emailVerificationRequired)
                                class="peer sr-only">
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-[#fa8900]"></span>
                            <span class="absolute left-0.5 h-6 w-6 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="admin-prod-btn-primary px-8">Save changes</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('selcomApiTest', () => ({
                    testing: false,
                    resultMessage: '',
                    resultOk: false,
                    async runTest() {
                        this.testing = true;
                        this.resultMessage = '';
                        try {
                            const res = await fetch(@json(route('superadmin.settings.test-selcom')), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await res.json();
                            this.resultOk = !!data.ok;
                            this.resultMessage = data.message || (data.ok ? 'OK' : 'Test failed');
                        } catch {
                            this.resultOk = false;
                            this.resultMessage = 'Could not reach the server.';
                        } finally {
                            this.testing = false;
                        }
                    },
                }));

                Alpine.data('selcomPaymentTest', () => ({
                    mobile: { phone: '', amount: 1000, testing: false, checking: false, ok: false, message: '', orderId: '' },
                    card: { amount: 1000, testing: false, checking: false, ok: false, message: '', orderId: '', gatewayUrl: '' },
                    async post(url, body) {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(body || {}),
                        });
                        return res.json();
                    },
                    async runMobile() {
                        this.mobile.testing = true;
                        this.mobile.message = '';
                        this.mobile.orderId = '';
                        try {
                            const data = await this.post(@json(route('superadmin.settings.test-selcom-mobile')), {
                                phone: this.mobile.phone,
                                amount: this.mobile.amount,
                            });
                            this.mobile.ok = !!data.ok;
                            this.mobile.message = data.message || (data.ok ? 'OK' : 'Test failed');
                            this.mobile.orderId = data.order_id || '';
                        } catch {
                            this.mobile.ok = false;
                            this.mobile.message = 'Could not reach the server.';
                        } finally {
                            this.mobile.testing = false;
                        }
                    },
                    async runCard() {
                        this.card.testing = true;
                        this.card.message = '';
                        this.card.orderId = '';
                        this.card.gatewayUrl = '';
                        try {
                            const data = await this.post(@json(route('superadmin.settings.test-selcom-card')), {
                                amount: this.card.amount,
                            });
                            this.card.ok = !!data.ok;
                            this.card.message = data.message || (data.ok ? 'OK' : 'Test failed');
                            this.card.orderId = data.order_id || '';
                            this.card.gatewayUrl = data.gateway_url || '';
                        } catch {
                            this.card.ok = false;
                            this.card.message = 'Could not reach the server.';
                        } finally {
                            this.card.testing = false;
                        }
                    },
                    async checkStatus(kind) {
                        const target = this[kind];
                        if (!target.orderId) return;
                        target.checking = true;
                        try {
                            const data = await this.post(@json(route('superadmin.settings.test-selcom-status')), {
                                order_id: target.orderId,
                            });
                            target.ok = !!data.ok;
                            target.message = data.message || 'Status unknown.';
                        } catch {
                            target.ok = false;
                            target.message = 'Could not reach the server.';
                        } finally {
                            target.checking = false;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-superadmin-layout>
