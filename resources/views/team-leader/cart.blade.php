<x-team-leader-layout title="Shopping cart">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Shopping cart</h1>
                <p class="admin-prod-subtitle">Review items before checkout.</p>
            </div>
        </div>

        @if ($cart && $cart->items->count() > 0)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <div class="admin-clay-panel overflow-hidden divide-y divide-white/60">
                        @foreach ($cart->items as $item)
                            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                                <div
                                    class="h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-white/80 bg-slate-100">
                                    @php
                                        $images = $item->product->images;
                                        $mainImage =
                                            !empty($images) && count($images) > 0
                                                ? \Illuminate\Support\Facades\Storage::url($images[0])
                                                : 'https://via.placeholder.com/150';
                                    @endphp
                                    <img src="{{ $mainImage }}" alt="{{ $item->product->name }}"
                                        class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap justify-between gap-2">
                                        <h3 class="text-base font-semibold text-[#232f3e]">
                                            <a href="{{ route('product.show', $item->product) }}"
                                                class="hover:text-[#fa8900]">{{ $item->product->name }}</a>
                                        </h3>
                                        <p class="text-sm font-bold text-[#232f3e]">TZS
                                            {{ number_format($item->product->price * $item->quantity, 0) }}</p>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($item->product->description, 80) }}</p>
                                    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="quantity-{{ $item->id }}" class="text-xs font-semibold text-slate-600">Qty</label>
                                            <select id="quantity-{{ $item->id }}" name="quantity" onchange="this.form.submit()"
                                                class="admin-prod-input w-auto py-1.5 text-sm">
                                                @for ($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" @selected($item->quantity == $i)>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </form>
                                        <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="lg:col-span-4">
                    <div class="admin-clay-panel sticky top-4 p-6">
                        <h2 class="text-base font-bold text-[#232f3e]">Order summary</h2>
                        @php
                            $subtotal = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);
                            $tax = $subtotal * 0.18;
                            $total = $subtotal + $tax;
                        @endphp
                        <dl class="mt-4 space-y-3 border-t border-white/60 pt-4 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <dt>Subtotal</dt>
                                <dd class="font-semibold text-[#232f3e]">TZS {{ number_format($subtotal, 0) }}</dd>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <dt>Tax estimate</dt>
                                <dd class="font-semibold text-[#232f3e]">TZS {{ number_format($tax, 0) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-white/60 pt-3 text-base font-bold text-[#232f3e]">
                                <dt>Total</dt>
                                <dd>TZS {{ number_format($total, 0) }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('checkout.create') }}"
                            class="mt-6 flex w-full items-center justify-center rounded-xl bg-gradient-to-br from-[#fa8900] to-[#e07800] px-4 py-3 text-sm font-bold text-white shadow-md hover:opacity-95">
                            Proceed to checkout
                        </a>
                        <p class="mt-4 text-center text-xs text-slate-600">
                            or <a href="{{ route('shop') }}" class="font-semibold text-[#fa8900] hover:underline">continue shopping</a>
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="admin-clay-panel px-8 py-14 text-center">
                <p class="text-sm font-semibold text-[#232f3e]">Your cart is empty</p>
                <p class="mt-2 text-sm text-slate-600">Add products from the shop to see them here.</p>
                <a href="{{ route('shop') }}"
                    class="mt-6 inline-flex items-center rounded-lg bg-gradient-to-br from-[#fa8900] to-[#e07800] px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95">
                    Browse shop
                </a>
            </div>
        @endif
    </div>
</x-team-leader-layout>
