<x-account-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-900">Your Orders</h2>
        <p class="text-sm text-gray-500">Check the status of recent orders, manage returns, and download invoices.</p>
    </div>

    @if($orders->count() > 0)
        <div class="space-y-6">
            @foreach($orders as $order)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <!-- Order Header -->
                    <div
                        class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex gap-8">
                            <div>
                                <span class="block text-xs uppercase font-bold text-gray-500">Order Placed</span>
                                <span class="text-sm text-gray-900">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-gray-500">Total</span>
                                <span class="text-sm text-gray-900">TZS {{ number_format($order->total_price, 0) }}</span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase font-bold text-gray-500">Ship To</span>
                                <span class="text-sm text-gray-900 truncate max-w-[150px]"
                                    title="{{ $order->address ? $order->address->address : ($order->shipping_address ?? 'N/A') }}">
                                    {{ $order->address ? $order->address->address : ($order->shipping_address ?? 'N/A') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-sm font-medium text-gray-500">Order # {{ $order->id }}</span>
                            <div class="flex gap-2 text-sm mt-1">
                                <a href="#" class="text-blue-600 hover:underline">View invoice</a>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ ucfirst($order->status) }}
                            </h3>
                        </div>

                        <div class="space-y-4">
                            @foreach($order->items as $item)
                                <div class="flex gap-4">
                                    <div
                                        class="flex-shrink-0 w-20 h-20 border border-gray-200 rounded-md overflow-hidden bg-gray-50">
                                        @php
                                            $images = $item->product && $item->product->images ? $item->product->images : [];
                                            $mainImage = !empty($images) && count($images) > 0 ? Storage::url($images[0]) : 'https://via.placeholder.com/150';
                                         @endphp
                                        <img src="{{ $mainImage }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-blue-600 hover:underline">
                                            <a href="{{ route('product.show', $item->product_id) }}">{{ $item->product->name }}</a>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">Sold by: {{ config('app.name', 'OpticEdgeAfrica') }}
                                        </p>
                                        <p class="text-sm mt-1 font-bold text-[#b12704]">TZS {{ number_format($item->price, 0) }}
                                        </p>
                                        <div class="mt-2 text-sm">
                                            <button
                                                class="bg-[#ffd814] border border-[#fcd200] rounded-lg px-3 py-1 hover:bg-[#f7ca00] text-sm shadow-sm">Buy
                                                it again</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No orders placed </h3>
            <p class="mt-1 text-sm text-gray-500">You have no orders yet.</p>
            <div class="mt-6">
                <a href="{{ route('shop') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Start shopping<span
                        aria-hidden="true"> &rarr;</span></a>
            </div>
        </div>
    @endif
</x-account-layout>