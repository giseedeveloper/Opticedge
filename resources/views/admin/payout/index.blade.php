<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{ tab: 'agent_commission' }">
        <div class="admin-prod-toolbar flex-col sm:flex-row sm:items-start gap-4">
            <div>
                <p class="admin-prod-eyebrow">Operations</p>
                <h1 class="admin-prod-title">Pay out</h1>
                <p class="admin-prod-subtitle">Pay agent commissions via Selcom; expenses are recorded when Selcom completes.</p>
            </div>
        </div>

        @if ($errors->has('selcom'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first('selcom') }}</div>
        @endif
        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if ($summary = session('bulk_selcom_summary'))
            <div class="admin-clay-panel px-4 py-3 mb-4 text-left" role="status">
                <p class="font-semibold text-[#232f3e] mb-2">Selcom Business disbursement</p>
                <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                    <li><strong>{{ (int) ($summary['candidates'] ?? 0) }}</strong> commission line(s) with amount &gt; 0 were considered.</li>
                    <li><strong>{{ (int) ($summary['started'] ?? 0) }}</strong> disbursement(s) submitted (money sent to each agent's wallet).</li>
                    <li><strong>{{ (int) ($summary['skipped'] ?? 0) }}</strong> line(s) skipped (invalid or missing phone, a payout already pending, <strong>or already disbursed</strong> for that line).</li>
                </ul>
                @if (! empty($summary['failures']))
                    <p class="text-sm font-medium text-red-700 mt-3 mb-1">Issues</p>
                    <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                        @foreach ($summary['failures'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div x-data="{ showDeposit: false }" class="admin-clay-panel p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Disbursement wallet</p>
                    <p class="mt-1 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($walletBalance ?? 0, 0) }} <span class="text-lg font-semibold text-slate-500">TZS</span></p>
                    <p class="mt-1 text-xs text-slate-500">Agent commission payouts are funded from this balance. Top it up to pay commissions.</p>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <a href="{{ route('admin.payout.wallet.ledger') }}" class="admin-prod-btn-ghost text-sm py-2 px-4">Wallet history</a>
                    <button type="button" @click="showDeposit = !showDeposit" class="admin-prod-btn-primary text-sm py-2 px-4">
                        <span x-show="!showDeposit">Deposit</span>
                        <span x-show="showDeposit" x-cloak>Cancel</span>
                    </button>
                </div>
            </div>

            <form x-show="showDeposit" x-cloak method="POST" action="{{ route('admin.payout.wallet.deposit') }}"
                class="mt-5 pt-5 border-t border-slate-200/70 flex flex-col sm:flex-row sm:items-end gap-3">
                @csrf
                <div class="grow">
                    <label for="deposit_amount" class="admin-prod-label">Amount (TZS)</label>
                    <input type="number" name="amount" id="deposit_amount" min="500" step="1" required
                        class="admin-prod-input" placeholder="e.g. 100000">
                </div>
                <div class="grow">
                    <label for="deposit_phone" class="admin-prod-label">Mobile money number</label>
                    <input type="text" name="phone" id="deposit_phone" required
                        value="{{ old('phone', auth()->user()->phone) }}"
                        class="admin-prod-input" placeholder="07… / 2557…">
                </div>
                <button type="submit" class="admin-prod-btn-primary px-6 shrink-0"
                    onclick="return confirm('Start a Selcom top-up? You will approve the payment on your phone.');">
                    Top up wallet
                </button>
            </form>
            <p x-show="showDeposit" x-cloak class="mt-2 text-xs text-slate-500">Money is collected via Selcom and credited to your wallet once the payment completes.</p>
        </div>

        <div class="admin-clay-panel p-2 sm:p-3 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="tab = 'agent_commission'"
                    :class="tab === 'agent_commission'
                        ? 'admin-prod-btn-primary text-sm py-2 px-4'
                        : 'admin-prod-btn-ghost text-sm py-2 px-4'"
                >
                    Agent commission disburse
                </button>
                <button
                    type="button"
                    @click="tab = 'more'"
                    :class="tab === 'more'
                        ? 'admin-prod-btn-primary text-sm py-2 px-4'
                        : 'admin-prod-btn-ghost text-sm py-2 px-4'"
                >
                    More (soon)
                </button>
            </div>
        </div>

        <div x-show="tab === 'agent_commission'" x-cloak>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="admin-clay-panel px-4 py-3">
                    <p class="admin-prod-eyebrow !mb-1">Commission lines</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totals['lines']) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3">
                    <p class="admin-prod-eyebrow !mb-1">Total commission (TZS)</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totals['commission'], 0) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3 border-l-4 border-emerald-400/80">
                    <p class="admin-prod-eyebrow !mb-1">Expense booked</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric text-emerald-800">{{ number_format($totals['booked'], 0) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3 border-l-4 border-amber-400/80">
                    <p class="admin-prod-eyebrow !mb-1">Awaiting Selcom payout</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric text-amber-900">{{ number_format($totals['pending'], 0) }}</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 max-w-3xl mb-4">
                Each row is commission saved on an <strong>agent credit</strong> or <strong>agent sale</strong> (from Agent Sales or Agent Credit).
                Clicking <strong>Pay</strong> sends the commission straight to the agent's mobile-money wallet via the Selcom Business API (money out).
                <strong>Not booked</strong> means the disbursement has not completed yet — no expense has been recorded.
                <strong>Booked</strong> means Selcom confirmed the payout and an expense was created on your default commission channel.
                Use <strong>Send all via Selcom</strong> (below the table) to pay every eligible line at once. Lines already <strong>disbursed</strong> are skipped; use <strong>Resume</strong> while a payout is pending.
            </p>

            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">Source</th>
                                <th scope="col" class="admin-prod-th">Agent name</th>
                                <th scope="col" class="admin-prod-th">Mobile</th>
                                <th scope="col" class="admin-prod-th">Commission (TZS)</th>
                                <th scope="col" class="admin-prod-th">Expense booked</th>
                                <th scope="col" class="admin-prod-th">Selcom</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-slate-600">
                                        @if ($row['source'] === 'credit')
                                            Credit #{{ $row['source_id'] }}
                                        @else
                                            Sale #{{ $row['source_id'] }}
                                        @endif
                                    </td>
                                    <td class="font-semibold text-[#232f3e]">{{ $row['agent_name'] }}</td>
                                    <td class="font-variant-numeric whitespace-nowrap">{{ $row['mobile'] }}</td>
                                    <td class="font-bold font-variant-numeric">{{ number_format($row['commission_amount'], 0) }}</td>
                                    <td>
                                        @if ($row['payout_booked'])
                                            <span class="admin-prod-tag border-emerald-200 text-emerald-900 bg-emerald-50/90">Booked</span>
                                        @else
                                            <span class="admin-prod-tag border-amber-200 text-amber-900 bg-amber-50/90">Not booked</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $sc = $row['selcom'] ?? null;
                                            $disbursed = ! empty($row['disburse_completed']);
                                            $payPending = $sc && $sc->payment_status === 'pending';
                                            $canPay = ! $disbursed && ! $payPending && $row['commission_amount'] > 0.0001;
                                        @endphp
                                        @if ($disbursed)
                                            <span class="admin-prod-tag border-emerald-200 text-emerald-900 bg-emerald-50/90 whitespace-nowrap">Disbursed</span>
                                        @elseif ($sc)
                                            <span class="admin-prod-tag whitespace-nowrap">{{ ucfirst($sc->payment_status) }}</span>
                                            @if ($payPending)
                                                <a href="{{ route('admin.payout.business.wait', $sc) }}" class="text-xs text-[#fa8900] font-medium ml-1 underline">Resume</a>
                                            @endif
                                        @else
                                            <span class="text-slate-400 text-sm">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            @if ($canPay)
                                                <form method="POST" action="{{ route('admin.payout.business.pay', ['source' => $row['source'], 'id' => $row['source_id']]) }}"
                                                    onsubmit="return confirm('Send {{ number_format($row['commission_amount'], 0) }} TZS to {{ addslashes($row['agent_name']) }} ({{ $row['mobile'] }}) now?');">
                                                    @csrf
                                                    <button type="submit" class="admin-prod-btn-primary text-xs py-1.5 px-3 inline-flex">Pay</button>
                                                </form>
                                            @endif
                                            @if ($row['source'] === 'credit')
                                                <a href="{{ route('admin.stock.edit-agent-credit', $row['source_id']) }}" class="admin-prod-btn-ghost text-xs py-1.5 px-3 inline-flex">Edit credit</a>
                                            @else
                                                <a href="{{ route('admin.stock.agent-sales') }}" class="admin-prod-btn-ghost text-xs py-1.5 px-3 inline-flex">Agent sales</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">No commission lines yet (commission amount must be greater than zero).</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-clay-panel mt-4 overflow-hidden">
                <div class="px-4 py-4 sm:px-6 sm:py-5 space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Bulk disbursement</p>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Sends the commission to each eligible agent's wallet via the Selcom Business API (money out). Lines already <strong>disbursed</strong> or with a payout pending are not sent again.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.payout.business.bulk') }}"
                        onsubmit="return confirm('Disburse commission to all {{ (int) $bulkEligibleCount }} eligible line(s) now? This sends real money to each agent.');">
                        @csrf
                        <button type="submit"
                            class="admin-prod-btn-primary inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl shadow-sm max-w-full"
                            @disabled($bulkEligibleCount === 0)>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span class="text-left">Send all via Selcom</span>
                            @if ($bulkEligibleCount > 0)
                                <span class="text-xs font-medium opacity-90 tabular-nums">({{ $bulkEligibleCount }} eligible)</span>
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="tab === 'more'" x-cloak>
            <div class="admin-clay-panel p-6 text-slate-600 text-center">
                <p class="font-medium text-[#232f3e] mb-1">More disbursement tabs</p>
                <p class="text-sm">Additional payout types can be added here in a follow-up.</p>
            </div>
        </div>
    </div>
</x-admin-layout>
