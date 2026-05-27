<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-8">Order History</h1>

        @if($orders->count() > 0)
            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 overflow-hidden">
                <ul role="list" class="divide-y divide-slate-200">
                    @foreach($orders as $order)
                            <li class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                                    <div>
                                        <h2 class="text-lg font-medium text-slate-900">Order #{{ $order->id }}</h2>
                                        <p class="text-sm text-slate-500">Placed on {{ $order->created_at->format('M d, Y') }}</p>
                                    </div>
                                    <div class="mt-2 sm:mt-0 flex flex-col sm:items-end">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    {{ $order->status === 'delivered' ? 'bg-green-100 text-green-800' :
                                                       ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($order->status === 'processed' ? 'bg-blue-100 text-blue-800' :
                                                       ($order->status === 'on the way' ? 'bg-indigo-100 text-indigo-800' :
                                                       ($order->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')))) }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                        <p class="mt-1 text-lg font-bold text-slate-900">TZS
                                            {{ number_format($order->total_price, 0) }}</p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4 mt-4">
                                    <h3 class="text-sm font-medium text-slate-900 mb-2">Items</h3>
                                    <div class="space-y-3">
                                        @foreach($order->items as $item)
                                            <div class="flex items-center">
                                                <div
                                                    class="flex-shrink-0 w-12 h-12 border border-slate-200 rounded-md overflow-hidden bg-slate-50">
                                                    @php
                                                        $images = $item->product->images;
                                                        $mainImage = !empty($images) && count($images) > 0 ? Storage::url($images[0]) : 'https://via.placeholder.com/150';
                                                    @endphp
                                                    <img src="{{ $mainImage }}" class="w-full h-full object-cover">
                                                </div>
                                                <div class="ml-4 flex-1">
                                                    <div class="flex justify-between text-sm">
                                                        <span class="font-medium text-slate-900">{{ $item->product->name }}</span>
                                                        <span class="text-slate-600">x{{ $item->quantity }}</span>
                                                    </div>
                                                    <div class="text-xs text-slate-500">TZS {{ number_format($item->price, 0) }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-xl border border-slate-200 shadow-sm">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No orders yet</h3>
                <p class="mt-1 text-sm text-slate-500">When you place orders, they will appear here.</p>
                <div class="mt-6">
                    <a href="{{ route('shop') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#fa8900] hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fa8900]">
                        Start Shopping
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>