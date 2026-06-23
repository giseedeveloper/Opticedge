<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page"
        x-data="{ paymentHistoryOpen: false, payModalOpen: {{ old('amount') || old('paid_date') ? 'true' : 'false' }} }">
        <div class="admin-prod-toolbar !mb-4">
            <div>
                <p class="admin-prod-eyebrow">Agents</p>
                <h1 class="admin-prod-title">Agent credit</h1>
                <p class="admin-prod-subtitle">Loans from agents to customers; record repayments per credit.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="admin-prod-btn-ghost" @click="paymentHistoryOpen = true">Payment history</button>
                <button type="button" class="admin-prod-btn-primary" @click="payModalOpen = true">Pay</button>
                <a href="{{ route('admin.stock.agent-credits.export-csv', request()->query()) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V4.5m0 12 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="status">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <x-admin-page-dashboard label="Summary (current filter)" class="mb-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Credits</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($agentCreditsDashboard['count']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total selling</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($agentCreditsDashboard['total_credit'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total profit</dt>
                    <dd class="text-lg font-semibold text-green-700">{{ number_format($agentCreditsDashboard['total_profit'] ?? 0, 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total paid</dt>
                    <dd class="text-lg font-semibold text-green-700">{{ number_format($agentCreditsDashboard['total_paid'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total pending</dt>
                    <dd class="text-lg font-semibold text-amber-700">{{ number_format($agentCreditsDashboard['total_pending'], 0) }} TZS</dd>
                </div>
            </dl>
        </x-admin-page-dashboard>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Date filter</h2>
            </div>
            <div class="admin-prod-form-body">
                <form method="GET" action="{{ route('admin.stock.agent-credits') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="date_from" class="admin-prod-label">From date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div>
                        <label for="date_to" class="admin-prod-label">To date</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="admin-prod-btn-primary">Filter</button>
                        @if(request('date_from') || request('date_to'))
                            <a href="{{ route('admin.stock.agent-credits') }}" class="admin-prod-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div id="credits-table" class="admin-clay-panel overflow-x-auto">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1280px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Agent</th>
                            <th scope="col" class="admin-prod-th">Customer</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">IMEI</th>
                            <th scope="col" class="admin-prod-th">Buy</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Profit</th>
                            <th scope="col" class="admin-prod-th">Channel</th>
                            <th scope="col" class="admin-prod-th min-w-[180px]">Commision</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($credits as $credit)
                            <tr>
                                <td class="text-slate-600 text-sm">
                                    {{ $credit->date instanceof \Carbon\Carbon ? $credit->date->format('Y-m-d') : $credit->date }}</td>
                                <td class="text-slate-700">{{ $credit->agent?->name ?? '—' }}</td>
                                <td class="font-medium text-[#232f3e]">{{ $credit->customer_name }}</td>
                                <td class="text-slate-600 text-sm">
                                    {{ $credit->product ? (($credit->product->category?->name ?? '—') . ' – ' . $credit->product->name) : 'N/A' }}</td>
                                <td class="font-mono text-xs text-slate-600">{{ $credit->productListItem?->imei_number ?? '—' }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($credit->displayPurchasePrice(), 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($credit->displaySellingPrice(), 0) }}</td>
                                <td class="font-variant-numeric text-green-700">{{ number_format($credit->displayProfit(), 0) }}</td>
                                <td class="align-middle">
                                    <span class="text-slate-600 text-sm">{{ $credit->paymentOption?->name ?? $defaultWatuChannel?->name ?? '—' }}</span>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <form action="{{ route('admin.stock.agent-credits-update-commission', ['id' => $credit->id] + request()->query()) }}" method="POST"
                                        class="inline-flex items-center gap-2 flex-wrap justify-end">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="commission_paid" value="{{ $credit->commission_paid ?? 0 }}" step="0.01" min="0"
                                            class="admin-prod-input w-40 py-1.5 text-sm">
                                        <button type="submit" class="admin-prod-link text-sm whitespace-nowrap">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="admin-prod-dealer-status admin-prod-dealer-status--active">sold</span>
                                </td>
                                <td class="admin-prod-cell-actions whitespace-nowrap">
                                    <a href="{{ route('admin.stock.edit-agent-credit', $credit->id) }}" class="admin-prod-link">Edit</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <a href="{{ route('admin.stock.agent-credit-invoice', $credit->id) }}" class="admin-prod-link">Download receipt</a>
                                    <span class="text-slate-300 mx-1">|</span>
                                    <form action="{{ route('admin.stock.destroy-agent-credit', ['id' => $credit->id] + request()->query()) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Delete this agent credit record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-prod-link text-rose-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-slate-500 py-10">No agent credits yet. Credits appear when an agent sells on credit from the app.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $credits, 'label' => 'credits'])
        </div>

        <div x-show="paymentHistoryOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click.self="paymentHistoryOpen = false">
            <div class="w-full max-w-6xl max-h-[80vh] overflow-y-auto rounded-2xl border border-white/80 bg-white shadow-xl">
                <div class="admin-prod-form-head flex items-center justify-between">
                    <div>
                        <h2 class="admin-prod-form-title">Agent credit payment history</h2>
                        <p class="admin-prod-subtitle">Latest repayments recorded across credits.</p>
                    </div>
                    <button type="button" class="admin-prod-btn-ghost" @click="paymentHistoryOpen = false">Close</button>
                </div>
                <div class="admin-prod-form-body">
                    <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                        <table class="min-w-[900px]" data-no-datatable>
                            <thead>
                                <tr>
                                    <th scope="col" class="admin-prod-th">Paid date</th>
                                    <th scope="col" class="admin-prod-th">Credit #</th>
                                    <th scope="col" class="admin-prod-th">Channel</th>
                                    <th scope="col" class="admin-prod-th">Amount</th>
                                    <th scope="col" class="admin-prod-th w-24"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentHistory as $payment)
                                    <tr>
                                        <td class="text-slate-600 text-sm">{{ $payment->paid_date?->format('Y-m-d') ?? '—' }}</td>
                                        <td class="font-medium text-[#232f3e]">#{{ $payment->agent_credit_id }}</td>
                                        <td class="text-slate-700">{{ $payment->paymentOption?->name ?? '—' }}</td>
                                        <td class="font-variant-numeric font-semibold text-emerald-800">{{ number_format((float) $payment->amount, 2) }} TZS</td>
                                        <td class="text-right">
                                            <form action="{{ route('admin.stock.agent-credit-payment-destroy', ['paymentId' => $payment->id] + request()->query()) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('Delete this payment? The amount will be removed from the credit and payment channel.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-slate-500 py-10">No payment history for the selected filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="payModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-black/40 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click.self="payModalOpen = false">
            <div class="w-full max-w-xl rounded-2xl border border-white/80 bg-white shadow-xl overflow-hidden">
                <div class="admin-prod-form-head flex items-center justify-between">
                    <div>
                        <h2 class="admin-prod-form-title">Record payment</h2>
                        <p class="admin-prod-subtitle">Enter the amount received. It is applied to pending credits starting with the oldest.</p>
                    </div>
                    <button type="button" class="admin-prod-btn-ghost" @click="payModalOpen = false">Close</button>
                </div>
                @php
                    $totalPendingPayable = round((float) ($agentCreditsDashboard['total_pending'] ?? 0), 2);
                    $defaultPayableAmount = old('amount', $totalPendingPayable > 0 ? $totalPendingPayable : null);
                    $hasPendingCredits = $totalPendingPayable > 0;
                @endphp
                <form method="POST"
                    action="{{ route('admin.stock.agent-credits-pay', request()->query()) }}"
                    class="admin-prod-form-body space-y-4"
                    x-data="{
                        totalPending: @js($totalPendingPayable),
                        amount: @js($defaultPayableAmount !== null && $defaultPayableAmount !== '' ? (float) $defaultPayableAmount : null)
                    }">
                    @csrf
                    @if(!$hasPendingCredits)
                        <div class="admin-prod-alert admin-prod-alert--warning mb-0">
                            No pending credits available to pay.
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4">
                            <p class="text-xs uppercase font-semibold text-amber-800">Total outstanding (all agents)</p>
                            <p class="mt-1 text-2xl font-bold text-amber-900"
                                x-text="totalPending.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TZS'">
                                {{ number_format($totalPendingPayable, 2) }} TZS
                            </p>
                            @if(request('date_from') || request('date_to'))
                                <p class="mt-2 text-xs text-amber-800">Based on the current date filter.</p>
                            @endif
                        </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="paid_date" class="admin-prod-label">Date</label>
                            <input type="date" name="paid_date" id="paid_date" value="{{ old('paid_date', now()->toDateString()) }}" required class="admin-prod-input" @disabled(!$hasPendingCredits)>
                        </div>
                        <div>
                            <label for="amount" class="admin-prod-label">Amount to pay</label>
                            <input type="number" name="amount" id="amount" min="0.01" step="0.01" required class="admin-prod-input"
                                x-model.number="amount"
                                :max="totalPending > 0 ? totalPending : null"
                                @disabled(!$hasPendingCredits)>
                            <p class="mt-1 text-xs text-slate-500">
                                Maximum:
                                <span class="font-semibold text-amber-700" x-text="totalPending.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TZS'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="admin-prod-btn-ghost" @click="payModalOpen = false">Cancel</button>
                        <button type="submit" class="admin-prod-btn-primary" @disabled(!$hasPendingCredits)>Submit payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
