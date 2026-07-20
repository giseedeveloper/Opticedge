<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @php
        $eventStyles = [
            'login' => 'border-emerald-200 text-emerald-800 bg-emerald-50/80',
            'logout' => 'border-slate-200 text-slate-600 bg-slate-50/80',
            'created' => 'border-blue-200 text-blue-800 bg-blue-50/80',
            'updated' => 'border-amber-200 text-amber-900 bg-amber-50/80',
            'deleted' => 'border-red-200 text-red-800 bg-red-50/80',
        ];
    @endphp

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Audit</p>
                <h1 class="admin-prod-title">System Log</h1>
                <p class="admin-prod-subtitle">Every user action in your vendor — logins, logouts, and record changes across admin, regional managers, team leaders and agents.</p>
            </div>
        </div>

        <div class="admin-clay-panel px-4 py-3 mb-4">
            <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="flex-1 min-w-[12rem]">
                    <label for="log_q" class="admin-prod-label !mb-1">Search</label>
                    <input type="text" id="log_q" name="q" value="{{ $filters['q'] }}"
                        placeholder="User or description" class="admin-prod-input text-sm py-2 w-full">
                </div>
                <div>
                    <label for="log_role" class="admin-prod-label !mb-1">Role</label>
                    <select id="log_role" name="role" class="admin-prod-input text-sm py-2 min-w-[9rem]">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="log_event" class="admin-prod-label !mb-1">Action</label>
                    <select id="log_event" name="event" class="admin-prod-input text-sm py-2 min-w-[8rem]">
                        <option value="">All actions</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ ucfirst($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="log_from" class="admin-prod-label !mb-1">From</label>
                    <input type="date" id="log_from" name="from" value="{{ $filters['from'] }}"
                        class="admin-prod-input text-sm py-2 min-w-[9rem]">
                </div>
                <div>
                    <label for="log_to" class="admin-prod-label !mb-1">To</label>
                    <input type="date" id="log_to" name="to" value="{{ $filters['to'] }}"
                        class="admin-prod-input text-sm py-2 min-w-[9rem]">
                </div>
                <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Apply</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.activity-logs.index') }}" class="admin-prod-btn-ghost text-sm py-2 px-3">Clear</a>
                @endif
            </form>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">When</th>
                            <th scope="col" class="admin-prod-th">User</th>
                            <th scope="col" class="admin-prod-th">Role</th>
                            <th scope="col" class="admin-prod-th">Action</th>
                            <th scope="col" class="admin-prod-th">Details</th>
                            <th scope="col" class="admin-prod-th">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-slate-600 whitespace-nowrap">
                                    {{ $log->created_at?->format('d M Y') }}
                                    <span class="block text-xs text-slate-400">{{ $log->created_at?->format('H:i:s') }}</span>
                                </td>
                                <td class="font-semibold text-[#232f3e]">{{ $log->causer_name ?? '—' }}</td>
                                <td>
                                    @if ($log->causer_role)
                                        <span class="admin-prod-tag">{{ ucwords(str_replace('_', ' ', $log->causer_role)) }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-prod-tag {{ $eventStyles[$log->event] ?? '' }}">{{ ucfirst($log->event) }}</span>
                                </td>
                                <td class="text-slate-700 max-w-[28rem]">
                                    <span>{{ $log->description }}</span>
                                    @if (! empty($log->properties['changes']))
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-xs text-slate-500 hover:text-[#232f3e]">
                                                {{ count($log->properties['changes']) }} field(s) changed
                                            </summary>
                                            <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                                                @foreach ($log->properties['changes'] as $field => $change)
                                                    <div>
                                                        <span class="font-medium text-slate-600">{{ $field }}:</span>
                                                        <span class="text-slate-400">{{ \Illuminate\Support\Str::limit((string) ($change['from'] ?? '∅'), 40) }}</span>
                                                        →
                                                        <span>{{ \Illuminate\Support\Str::limit((string) ($change['to'] ?? '∅'), 40) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </td>
                                <td class="text-slate-500 text-xs whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">
                                    No activity recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $logs, 'label' => 'log entries'])
        </div>
    </div>
</x-admin-layout>
