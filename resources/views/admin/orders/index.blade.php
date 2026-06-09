<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-4">
            <div>
                <p class="admin-prod-eyebrow">Storefront</p>
                <h1 class="admin-prod-title">Orders</h1>
                <p class="admin-prod-subtitle">Customer orders and fulfillment status.</p>
            </div>
        </div>

        <x-admin-page-dashboard label="Summary (all orders)" class="mb-6">
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total orders</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($orderDashboard['total_orders']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total value</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($orderDashboard['total_value'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Pending status</dt>
                    <dd class="text-lg font-semibold text-amber-700">{{ number_format($orderDashboard['pending']) }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-slate-500">Use the table search and pagination controls to browse all orders. Summary figures above cover the full database.</p>
        </x-admin-page-dashboard>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Order ID</th>
                            <th scope="col" class="admin-prod-th">Customer</th>
                            <th scope="col" class="admin-prod-th">Location</th>
                            <th scope="col" class="admin-prod-th">Total</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">#{{ $order->id }}</td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $order->user->name ?? 'Guest' }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->user->email ?? '-' }}</div>
                                </td>
                                <td class="text-slate-600 text-sm">
                                    {{ $order->address->city ?? 'N/A' }}, {{ $order->address->country ?? '' }}
                                </td>
                                <td class="font-bold font-variant-numeric">{{ number_format($order->total_price, 0) }} TZS</td>
                                <td>
                                    <span
                                        class="admin-prod-user-status {{ $order->status === 'delivered' ? 'admin-prod-user-status--active' : ($order->status === 'cancelled' ? 'admin-prod-dealer-status--suspended' : 'admin-prod-dealer-status--pending') }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="text-slate-600 text-sm font-variant-numeric">{{ $order->created_at->format('M j, Y') }}</td>
                                <td class="admin-prod-cell-actions">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-prod-link">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-10">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
