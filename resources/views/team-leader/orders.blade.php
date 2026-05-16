<x-team-leader-layout title="Your orders">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Your orders</h1>
                <p class="admin-prod-subtitle">Status of recent storefront orders and totals.</p>
            </div>
        </div>

        @if ($orders->count() > 0)
            <div class="space-y-6">
                @foreach ($orders as $order)
                    <div class="admin-clay-panel overflow-hidden">
                        <div
                            class="flex flex-col gap-4 border-b border-white/60 bg-gradient-to-r from-white/80 to-slate-50/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap gap-6 sm:gap-8">
                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Placed</span>
                                    <span class="text-sm font-semibold text-[#232f3e]">{{ $order->created_at->format('M j, Y') }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Total</span>
                                    <span class="text-sm font-semibold text-[#232f3e]">TZS {{ number_format($order->total_price, 0) }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Ship to</span>
                                    <span class="text-sm text-slate-700 max-w-[200px] truncate"
                                        title="{{ $order->address ? $order->address->address : ($order->shipping_address ?? 'N/A') }}">
                                        {{ $order->address ? $order->address->address : ($order->shipping_address ?? 'N/A') }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-slate-600">Order #{{ $order->id }}</span>
                            </div>
                        </div>

                        <div class="px-6 py-5">
                            <h3 class="text-base font-bold text-[#232f3e] mb-4">{{ ucfirst($order->status) }}</h3>
                            <div class="space-y-4">
                                @foreach ($order->items as $item)
                                    <div class="flex gap-4 border-t border-white/50 pt-4 first:border-0 first:pt-0">
                                        <div
                                            class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-white/80 bg-slate-100">
                                            @php
                                                $images = $item->product && $item->product->images ? $item->product->images : [];
                                                $mainImage =
                                                    !empty($images) && count($images) > 0
                                                        ? \Illuminate\Support\Facades\Storage::url($images[0])
                                                        : 'https://via.placeholder.com/150';
                                            @endphp
                                            <img src="{{ $mainImage }}" alt="" class="h-full w-full object-cover">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('product.show', $item->product_id) }}"
                                                class="text-sm font-semibold text-[#fa8900] hover:underline">
                                                {{ $item->product->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-slate-500">Sold by {{ config('app.name', 'OpticEdgeAfrica') }}</p>
                                            <p class="mt-1 text-sm font-bold text-[#232f3e]">TZS {{ number_format($item->price, 0) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="admin-clay-panel px-8 py-14 text-center">
                <p class="text-sm font-semibold text-[#232f3e]">No orders yet</p>
                <p class="mt-2 text-sm text-slate-600">When you place orders from the shop, they will show up here.</p>
                <a href="{{ route('welcome') }}"
                    class="mt-6 inline-flex items-center rounded-lg bg-gradient-to-br from-[#fa8900] to-[#e07800] px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95">
                    Browse shop
                </a>
            </div>
        @endif
    </div>
</x-team-leader-layout>
