<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <a href="{{ route('admin.stock.stocks') }}" class="admin-prod-back mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to stocks
        </a>

        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">IMEI search</h1>
                <p class="admin-prod-subtitle">Enter part or all of an IMEI or serial. Open a row for the full device record.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-clay-panel p-6 mb-6">
            <form method="get" action="{{ route('admin.stock.imei-search') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1 min-w-0">
                    <label for="imei_q" class="admin-prod-label">IMEI / serial</label>
                    <input type="search" name="q" id="imei_q" value="{{ old('q', $q) }}"
                        class="admin-prod-input w-full font-mono"
                        placeholder="e.g. 352123456789012"
                        autocomplete="off"
                        minlength="3"
                        inputmode="numeric">
                </div>
                <button type="submit" class="admin-prod-btn-primary shrink-0">Search</button>
            </form>
            <p class="text-xs text-slate-500 mt-3">At least 3 characters after spaces are removed. Up to 100 matches.</p>
        </div>

        @if($normalized !== '' && strlen($normalized) < 3)
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="status">Enter at least 3 characters to search.</div>
        @endif

        @if($normalized !== '' && strlen($normalized) >= 3)
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">IMEI</th>
                                <th scope="col" class="admin-prod-th">Model</th>
                                <th scope="col" class="admin-prod-th">Product</th>
                                <th scope="col" class="admin-prod-th">Category</th>
                                <th scope="col" class="admin-prod-th">Stock</th>
                                <th scope="col" class="admin-prod-th">Status</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--index"><span class="sr-only">Open</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $row)
                                <tr class="hover:bg-white/50">
                                    <td class="font-mono text-sm">
                                        <a href="{{ route('admin.stock.imei-item', $row) }}" class="text-[#232f3e] font-medium hover:underline">
                                            {{ $row->imei_number ?? '—' }}
                                        </a>
                                    </td>
                                    <td>{{ $row->model ?? '–' }}</td>
                                    <td>{{ $row->product?->name ?? '–' }}</td>
                                    <td>{{ $row->category?->name ?? '–' }}</td>
                                    <td>{{ $row->stock?->name ?? '–' }}</td>
                                    <td>
                                        @if($row->sold_at)
                                            <span class="admin-prod-status admin-prod-status--sold">Sold</span>
                                        @else
                                            <span class="admin-prod-status admin-prod-status--ok">Available</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.stock.imei-item', $row) }}" class="admin-prod-link text-sm font-medium whitespace-nowrap">Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-slate-500 py-10">No devices match that search.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
