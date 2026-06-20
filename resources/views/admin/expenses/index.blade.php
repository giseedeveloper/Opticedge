<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Finance</p>
                <h1 class="admin-prod-title">Expenses</h1>
                <p class="admin-prod-subtitle">Business spend by channel.</p>
            </div>
            <div class="w-full sm:w-auto flex flex-col items-stretch sm:items-end gap-2">
                <form method="GET" action="{{ route('admin.expenses.index') }}" class="flex flex-wrap items-end gap-2 justify-start sm:justify-end">
                    <div>
                        <label for="expenses_from" class="admin-prod-label !mb-1">Date filter (from)</label>
                        <input
                            type="date"
                            id="expenses_from"
                            name="from"
                            value="{{ $fromDate }}"
                            class="admin-prod-input text-sm py-2 min-w-[10rem]"
                        >
                    </div>
                    <div>
                        <label for="expenses_to" class="admin-prod-label !mb-1">Date filter (to)</label>
                        <input
                            type="date"
                            id="expenses_to"
                            name="to"
                            value="{{ $toDate }}"
                            class="admin-prod-input text-sm py-2 min-w-[10rem]"
                        >
                    </div>
                    <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Apply</button>
                    @if($fromDate || $toDate)
                        <a href="{{ route('admin.expenses.index') }}" class="admin-prod-btn-ghost text-sm py-2 px-3">Clear</a>
                    @endif
                </form>

                <div class="admin-clay-panel px-4 py-2.5 w-full sm:w-auto">
                    <p class="admin-prod-eyebrow !mb-1">Total expense - amount</p>
                    <p class="admin-prod-form-title !text-lg font-variant-numeric">{{ number_format($totalExpenseAmount, 0) }} TZS</p>
                </div>

                <a href="{{ route('admin.expenses.create') }}" class="admin-prod-btn-primary inline-flex items-center gap-2 shrink-0 self-start sm:self-end">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add expense
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Activity</th>
                            <th scope="col" class="admin-prod-th">Amount (TZS)</th>
                            <th scope="col" class="admin-prod-th">Channel</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td class="text-slate-600">{{ $expense->date }}</td>
                                <td class="font-semibold text-[#232f3e]">{{ $expense->activity }}</td>
                                <td class="font-bold font-variant-numeric">{{ number_format($expense->amount, 0) }}</td>
                                <td>
                                    @if($expense->paymentOption)
                                        <span class="admin-prod-tag {{ $expense->paymentOption->type === 'mobile' ? 'border-blue-200 text-blue-800 bg-blue-50/80' : 'admin-prod-tag--accent' }}">
                                            {{ $expense->paymentOption->name }} ({{ ucfirst($expense->paymentOption->type) }})
                                        </span>
                                    @else
                                        <span class="admin-prod-tag">N/A</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions flex-wrap gap-x-3 gap-y-1 justify-end">
                                        <a href="{{ route('admin.expenses.edit', $expense) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this expense?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-btn-inline admin-prod-link--danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">
                                    No expenses yet.
                                    <a href="{{ route('admin.expenses.create') }}" class="admin-prod-link">Add one</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $expenses, 'label' => 'expenses'])
        </div>
    </div>
</x-admin-layout>
