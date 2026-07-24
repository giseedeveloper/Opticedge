<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{
        tab: 'agent_commission',
        showDefaultModal: false,
        showEditModal: false,
        editDate: '',
        editAgentId: '',
        editAgentName: '',
        editDevices: 0,
        editAmount: 0
    }">
        <div class="admin-prod-toolbar flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <p class="admin-prod-eyebrow">Operations</p>
                <h1 class="admin-prod-title">Pay out</h1>
                <p class="admin-prod-subtitle">Default commission is applied on each sale automatically. Review daily agent totals, then disburse via Selcom.</p>
            </div>
            <button type="button" @click="showDefaultModal = true" class="admin-prod-btn-primary text-sm py-2.5 px-4 shrink-0">
                Set default commission
            </button>
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
                @if (! empty($summary['queued']))
                    <p class="text-sm text-slate-700 mb-2">{{ $summary['message'] ?? 'Queued for background processing. Refresh this page to see progress.' }}</p>
                @endif
                <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                    <li><strong>{{ (int) ($summary['candidates'] ?? 0) }}</strong> commission line(s) considered.</li>
                    @unless (! empty($summary['queued']))
                        <li><strong>{{ (int) ($summary['started'] ?? 0) }}</strong> disbursement(s) submitted.</li>
                        <li><strong>{{ (int) ($summary['skipped'] ?? 0) }}</strong> line(s) skipped (pending, already disbursed, or invalid phone).</li>
                    @endunless
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

        @if (! empty($disburseRun) && in_array($disburseRun['status'] ?? '', ['queued', 'running', 'completed', 'failed'], true))
            <div class="admin-clay-panel px-4 py-3 mb-4 text-left" role="status"
                @if (in_array($disburseRun['status'] ?? '', ['queued', 'running'], true))
                    x-data x-init="setTimeout(() => window.location.reload(), 8000)"
                @endif
            >
                <p class="font-semibold text-[#232f3e] mb-1">Background disbursement status</p>
                <p class="text-sm text-slate-700 mb-2">{{ $disburseRun['message'] ?? ucfirst((string) ($disburseRun['status'] ?? '')) }}</p>
                <ul class="text-sm text-slate-700 space-y-1 list-disc list-inside">
                    <li>Status: <strong>{{ $disburseRun['status'] ?? '—' }}</strong></li>
                    <li>Processed <strong>{{ (int) ($disburseRun['processed'] ?? 0) }}</strong> of <strong>{{ (int) ($disburseRun['candidates'] ?? 0) }}</strong></li>
                    <li>Submitted: <strong>{{ (int) ($disburseRun['started'] ?? 0) }}</strong> · Skipped: <strong>{{ (int) ($disburseRun['skipped'] ?? 0) }}</strong></li>
                </ul>
                @if (! empty($disburseRun['failures']))
                    <p class="text-sm font-medium text-red-700 mt-3 mb-1">Issues</p>
                    <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                        @foreach (array_slice($disburseRun['failures'], 0, 8) as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
                @if (in_array($disburseRun['status'] ?? '', ['queued', 'running'], true))
                    <p class="text-xs text-slate-500 mt-2">This page refreshes automatically while the run is active.</p>
                @endif
            </div>
        @endif

        <div class="admin-clay-panel p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Default commission per sale</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-[#232f3e]">
                        {{ number_format($defaultCommissionAmount ?? 0, 0) }}
                        <span class="text-base font-semibold text-slate-500">TZS</span>
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Applied automatically to new cash and credit sales.</p>
                </div>
                <button type="button" @click="showDefaultModal = true" class="admin-prod-btn-ghost text-sm py-2 px-4 shrink-0">Change</button>
            </div>
        </div>

        <div x-data="{ showDeposit: false }" class="admin-clay-panel p-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Disbursement wallet</p>
                    <p class="mt-1 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($walletBalance ?? 0, 0) }} <span class="text-lg font-semibold text-slate-500">TZS</span></p>
                    <p class="mt-1 text-xs text-slate-500">Agent commission payouts are funded from this balance.</p>
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
                    <p class="admin-prod-eyebrow !mb-1">Agent-day groups</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totals['agents']) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3">
                    <p class="admin-prod-eyebrow !mb-1">Devices sold</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totals['devices']) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3">
                    <p class="admin-prod-eyebrow !mb-1">Total commission (TZS)</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totals['commission'], 0) }}</p>
                </div>
                <div class="admin-clay-panel px-4 py-3 border-l-4 border-amber-400/80">
                    <p class="admin-prod-eyebrow !mb-1">Awaiting payout</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric text-amber-900">{{ number_format($totals['pending'], 0) }}</p>
                </div>
            </div>

            @if ($dateTabs->isEmpty())
                <div class="admin-clay-panel p-8 text-center text-slate-500">
                    No commissions yet. Set a default commission above, then new agent sales will appear here by date.
                </div>
            @else
                <div class="admin-clay-panel p-2 sm:p-3 mb-4 overflow-x-auto">
                    <div class="flex flex-wrap gap-2 min-w-max">
                        @foreach ($dateTabs as $tabItem)
                            <a
                                href="{{ route('admin.payout.index', ['date' => $tabItem['date']]) }}"
                                class="{{ ($activeDate === $tabItem['date']) ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-sm py-2 px-4 inline-flex flex-col items-start gap-0.5"
                            >
                                <span>{{ $tabItem['label'] }}</span>
                                <span class="text-[11px] font-medium opacity-80 tabular-nums">
                                    {{ (int) $tabItem['total_devices'] }} device(s) · {{ number_format($tabItem['total_commission'], 0) }} TZS
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                @foreach ($dateTabs as $tabItem)
                    @if ($activeDate !== $tabItem['date'])
                        @continue
                    @endif

                    <div class="admin-clay-panel overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <p class="font-semibold text-[#232f3e]">{{ $tabItem['label'] }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $tabItem['agents']->count() }} agent(s) · {{ (int) $tabItem['total_devices'] }} device(s) · {{ number_format($tabItem['total_commission'], 0) }} TZS
                                </p>
                            </div>
                        </div>
                        <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col" class="admin-prod-th">Agent name</th>
                                        <th scope="col" class="admin-prod-th">Mobile</th>
                                        <th scope="col" class="admin-prod-th">Devices sold</th>
                                        <th scope="col" class="admin-prod-th">Total commission (TZS)</th>
                                        <th scope="col" class="admin-prod-th">Status</th>
                                        <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tabItem['agents'] as $agentRow)
                                        @php
                                            $locked = ! empty($agentRow['edit_locked']) || ! empty($agentRow['disburse_completed']);
                                            $disbursed = ! empty($agentRow['disburse_completed']);
                                            $payPending = ! empty($agentRow['pay_pending']);
                                            $canPay = ! empty($agentRow['can_pay']);
                                        @endphp
                                        <tr class="{{ $disbursed ? 'opacity-70' : '' }}">
                                            <td class="font-semibold text-[#232f3e]">{{ $agentRow['agent_name'] }}</td>
                                            <td class="font-variant-numeric whitespace-nowrap">{{ $agentRow['mobile'] }}</td>
                                            <td class="font-variant-numeric font-semibold">{{ (int) $agentRow['devices'] }}</td>
                                            <td class="font-bold font-variant-numeric">{{ number_format($agentRow['commission_amount'], 0) }}</td>
                                            <td>
                                                @if ($disbursed)
                                                    <span class="admin-prod-tag border-emerald-200 text-emerald-900 bg-emerald-50/90 whitespace-nowrap">Disbursed</span>
                                                @elseif ($payPending)
                                                    <span class="admin-prod-tag border-amber-200 text-amber-900 bg-amber-50/90 whitespace-nowrap">Pending</span>
                                                @elseif (! empty($agentRow['payout_booked']))
                                                    <span class="admin-prod-tag border-emerald-200 text-emerald-900 bg-emerald-50/90">Booked</span>
                                                @else
                                                    <span class="admin-prod-tag border-amber-200 text-amber-900 bg-amber-50/90">Awaiting payout</span>
                                                @endif
                                            </td>
                                            <td class="text-right whitespace-nowrap">
                                                <div class="inline-flex items-center gap-2">
                                                    @if (! $locked)
                                                        <button
                                                            type="button"
                                                            class="admin-prod-btn-ghost text-xs py-1.5 px-3 inline-flex"
                                                            @click="
                                                                showEditModal = true;
                                                                editDate = @js($agentRow['date']);
                                                                editAgentId = @js((string) $agentRow['agent_id']);
                                                                editAgentName = @js($agentRow['agent_name']);
                                                                editDevices = {{ (int) $agentRow['devices'] }};
                                                                editAmount = {{ (float) $agentRow['commission_amount'] }};
                                                            "
                                                        >Edit</button>
                                                    @else
                                                        <span class="text-xs text-slate-400 px-2" title="Locked after disbursement">Locked</span>
                                                    @endif
                                                    @if ($canPay)
                                                        @php
                                                            $runBusy = ! empty($disburseRun) && in_array($disburseRun['status'] ?? '', ['queued', 'running'], true);
                                                        @endphp
                                                        <form method="POST" action="{{ route('admin.payout.group.pay') }}"
                                                            onsubmit="return confirm('Queue {{ number_format($agentRow['commission_amount'], 0) }} TZS to {{ addslashes($agentRow['agent_name']) }} for {{ (int) $agentRow['devices'] }} device(s)? Processing runs in the background.');">
                                                            @csrf
                                                            <input type="hidden" name="date" value="{{ $agentRow['date'] }}">
                                                            <input type="hidden" name="agent_id" value="{{ $agentRow['agent_id'] }}">
                                                            <button type="submit" class="admin-prod-btn-primary text-xs py-1.5 px-3 inline-flex" @disabled($runBusy)>Pay</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="admin-clay-panel mt-4 overflow-hidden">
                <div class="px-4 py-4 sm:px-6 sm:py-5 space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Bulk disbursement</p>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Queues every eligible commission line across all dates for Selcom in the background. Already disbursed or pending lines are skipped.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.payout.business.bulk') }}"
                        onsubmit="return confirm('Queue Selcom disbursement for all eligible lines? This runs in the background and sends real money to each agent.');">
                        @csrf
                        <button type="submit"
                            class="admin-prod-btn-primary inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl shadow-sm max-w-full"
                            @disabled($bulkEligibleCount === 0 || (! empty($disburseRun) && in_array($disburseRun['status'] ?? '', ['queued', 'running'], true)))>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span class="text-left">Send all via Selcom</span>
                            @if ($bulkEligibleCount > 0)
                                <span class="text-xs font-medium opacity-90 tabular-nums">({{ $bulkEligibleCount }} agent-day group(s) eligible)</span>
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

        {{-- Default commission modal --}}
        <div
            x-show="showDefaultModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-slate-900/40" @click="showDefaultModal = false"></div>
            <div class="relative admin-clay-panel w-full max-w-md p-6 shadow-xl">
                <h2 class="text-lg font-bold text-[#232f3e] mb-1">Default commission per sale</h2>
                <p class="text-sm text-slate-600 mb-4">This amount is applied automatically whenever an agent records a cash or credit sale.</p>
                <form method="POST" action="{{ route('admin.payout.default-commission') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="default_commission_amount" class="admin-prod-label">Amount (TZS)</label>
                        <input
                            type="number"
                            name="default_commission_amount"
                            id="default_commission_amount"
                            min="0"
                            step="1"
                            required
                            value="{{ old('default_commission_amount', (int) ($defaultCommissionAmount ?? 0)) }}"
                            class="admin-prod-input"
                        >
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="admin-prod-btn-ghost text-sm py-2 px-4" @click="showDefaultModal = false">Cancel</button>
                        <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit group commission modal --}}
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-slate-900/40" @click="showEditModal = false"></div>
            <div class="relative admin-clay-panel w-full max-w-md p-6 shadow-xl">
                <h2 class="text-lg font-bold text-[#232f3e] mb-1">Edit commission</h2>
                <p class="text-sm text-slate-600 mb-4">
                    <span x-text="editAgentName"></span>
                    · <span x-text="editDevices"></span> device(s)
                    · <span x-text="editDate"></span>
                </p>
                <form method="POST" action="{{ route('admin.payout.group.commission') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="date" :value="editDate">
                    <input type="hidden" name="agent_id" :value="editAgentId">
                    <div>
                        <label for="edit_commission_amount" class="admin-prod-label">Total commission (TZS)</label>
                        <input
                            type="number"
                            name="commission_amount"
                            id="edit_commission_amount"
                            min="0"
                            step="1"
                            required
                            x-model="editAmount"
                            class="admin-prod-input"
                        >
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="admin-prod-btn-ghost text-sm py-2 px-4" @click="showEditModal = false">Cancel</button>
                        <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
