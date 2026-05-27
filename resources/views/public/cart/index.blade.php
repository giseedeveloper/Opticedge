<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-8">Shopping Cart</h1>

        @if($cart && $cart->items->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 overflow-hidden">
                        <ul role="list" class="divide-y divide-slate-200">
                            @foreach($cart->items as $item)
                                <li class="p-6 flex items-start">
                                    <div
                                        class="flex-shrink-0 w-24 h-24 border border-slate-200 rounded-md overflow-hidden bg-slate-50">
                                        @php
                                            $images = $item->product->images;
                                            $mainImage = !empty($images) && count($images) > 0 ? Storage::url($images[0]) : 'https://via.placeholder.com/150';
                                        @endphp
                                        <img src="{{ $mainImage }}" alt="{{ $item->product->name }}"
                                            class="w-full h-full object-center object-cover">
                                    </div>

                                    <div class="ml-4 flex-1 flex flex-col">
                                        <div>
                                            <div class="flex justify-between text-base font-medium text-slate-900">
                                                <h3>
                                                    <a href="{{ route('product.show', $item->product) }}"
                                                        class="hover:text-[#fa8900]">{{ $item->product->name }}</a>
                                                </h3>
                                                <p class="ml-4">TZS
                                                    {{ number_format($item->product->price * $item->quantity, 0) }}</p>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ Str::limit($item->product->description, 50) }}</p>
                                        </div>
                                        <div class="flex-1 flex items-end justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <form action="{{ route('cart.update', $item->id) }}" method="POST"
                                                    class="flex items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="quantity-{{ $item->id }}" class="sr-only">Quantity</label>
                                                    <select id="quantity-{{ $item->id }}" name="quantity"
                                                        onchange="this.form.submit()"
                                                        class="rounded-md border-slate-300 py-1.5 focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-base sm:text-sm">
                                                        @for($i = 1; $i <= 10; $i++)
                                                            <option value="{{ $i }}" {{ $item->quantity == $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </form>
                                            </div>

                                            <div class="flex">
                                                <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="font-medium text-red-600 hover:text-red-500">Remove</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 p-6 sticky top-4">
                        <h2 class="text-lg font-medium text-slate-900 mb-4">Order Summary</h2>

                        @php
                            $subtotal = $cart->items->sum(function ($item) {
                                return $item->product->price * $item->quantity;
                            });
                            $tax = $subtotal * 0.18; // Example tax
                            $total = $subtotal + $tax;
                        @endphp

                        <div class="flow-root">
                            <dl class="-my-4 text-sm divide-y divide-slate-200">
                                <div class="py-4 flex items-center justify-between">
                                    <dt class="text-slate-600">Subtotal</dt>
                                    <dd class="font-medium text-slate-900">TZS {{ number_format($subtotal, 0) }}</dd>
                                </div>
                                <div class="py-4 flex items-center justify-between">
                                    <dt class="text-slate-600">Tax estimate</dt>
                                    <dd class="font-medium text-slate-900">TZS {{ number_format($tax, 0) }}</dd>
                                </div>
                                <div class="py-4 flex items-center justify-between">
                                    <dt class="text-base font-medium text-slate-900">Order total</dt>
                                    <dd class="text-base font-bold text-[#fa8900]">TZS {{ number_format($total, 0) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('checkout.create') }}"
                                class="w-full flex justify-center items-center bg-[#fa8900] border border-transparent rounded-full shadow-sm py-3 px-4 text-base font-medium text-white hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fa8900] shadow-md transition-transform active:scale-95">
                                Proceed to Checkout
                            </a>
                        </div>
                        <div class="mt-6 flex justify-center text-sm text-center text-slate-500">
                            <p>
                                or <a href="{{ route('shop') }}" class="text-[#fa8900] font-medium hover:text-orange-600">Continue
                                    Shopping<span aria-hidden="true"> &rarr;</span></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-xl border border-slate-200 shadow-sm">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">Your cart is empty</h3>
                <p class="mt-1 text-sm text-slate-500">Start adding some items to your cart!</p>
                <div class="mt-6">
                    <a href="{{ route('shop') }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#fa8900] hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fa8900]">
                        Go Shopping
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>