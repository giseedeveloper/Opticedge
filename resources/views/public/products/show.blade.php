@php
    $productImages = is_string($product->images ?? null) ? json_decode($product->images, true) : ($product->images ?? []);
    $productImages = is_array($productImages) ? $productImages : [];
    $hasProductImages = count($productImages) > 0;
    $mainImageUrl = $hasProductImages ? asset('storage/' . $productImages[0]) : 'https://via.placeholder.com/600?text=' . urlencode($product->name);
@endphp
<x-app-layout>
    <div class="max-w-[1400px] mx-auto px-4 py-8 bg-white min-h-screen">
        <!-- Breadcrumb (Optional) -->
        <nav class="flex text-sm text-slate-500 mb-6 gap-2">
            <a href="{{ route('shop') }}" class="hover:text-[#fa8900]">Home</a>
            <span>/</span>
            <span class="text-slate-900 font-medium truncate">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Left Column: Images (Gallery) -->
            <div class="md:col-span-5 lg:col-span-4"
                x-data="{ activeImage: '{{ $mainImageUrl }}' }">
                <!-- Main Image -->
                <div
                    class="aspect-square bg-slate-50 rounded-xl overflow-hidden mb-4 border border-slate-100 relative group">
                    <img :src="activeImage" alt="{{ $product->name }}"
                        onerror="this.onerror=null; this.src='https://via.placeholder.com/600?text={{ urlencode($product->name) }}'; this.parentElement.classList.add('bg-gray-100', 'p-8');"
                        class="w-full h-full object-contain mix-blend-multiply transition-transform duration-300 group-hover:scale-105">
                </div>

                <!-- Thumbnails -->
                @if($hasProductImages && count($productImages) > 1)
                    <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                        @foreach($productImages as $image)
                            @php $imgUrl = asset('storage/' . $image); @endphp
                            <button @click="activeImage = '{{ $imgUrl }}'"
                                class="w-16 h-16 flex-shrink-0 rounded-md border-2 overflow-hidden hover:border-[#fa8900] transition-all"
                                :class="activeImage === '{{ $imgUrl }}' ? 'border-[#fa8900]' : 'border-slate-200'">
                                <img src="{{ $imgUrl }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Middle Column: Product Info -->
            <div class="md:col-span-7 lg:col-span-5 space-y-4">
                <h1 class="text-2xl font-bold text-slate-900 leading-tight">
                    {{ $product->name }}
                </h1>

                <!-- Rating & Sold -->
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1 text-yellow-400">
                        @for($i = 0; $i < 5; $i++)
                            @if($i < floor($product->rating))
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                        <span class="text-slate-500 text-xs ml-1">{{ number_format($product->rating, 1) }}</span>
                    </div>
                    <span class="text-slate-300">|</span>
                    <div class="text-slate-500">2,300+ Reviews</div>
                    <span class="text-slate-300">|</span>
                    <div class="text-slate-900 font-medium">50,000+ sold</div>
                </div>

                <!-- Price Block -->
                <div class="bg-red-50 p-4 rounded-lg border border-red-100 mt-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-sm">Welcome
                            deal</span>
                        <span class="text-red-600 text-xs font-semibold">Extra 5% off with coins</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold text-[#fa8900]">TZS{{ number_format($product->price, 0) }}</span>
                        <span
                            class="text-sm text-slate-500 line-through">TZS{{ number_format($product->price * 1.5, 0) }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Price includes VAT</p>
                </div>

                <!-- Color/Options Selection -->
                <div class="py-4 border-y border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Color: <span
                            class="font-normal text-slate-600">Samsung Variant</span></h3>
                    @if($hasProductImages)
                        <div class="flex flex-wrap gap-2">
                            @foreach($productImages as $image)
                                @php $imgUrl = asset('storage/' . $image); @endphp
                                <button @click="activeImage = '{{ $imgUrl }}'"
                                    class="w-12 h-12 rounded-lg border-2 p-0.5 bg-slate-50 relative overflow-hidden transition-all"
                                    :class="activeImage === '{{ $imgUrl }}' ? 'border-[#fa8900]' : 'border-slate-200 hover:border-slate-300'">
                                    <img src="{{ $imgUrl }}" class="w-full h-full object-cover rounded-md">
                                    @if($loop->first)
                                        <div
                                            class="absolute -top-2 -right-2 bg-red-600 text-white text-[9px] px-1.5 py-0.5 rounded-full transform scale-75">
                                            Hot</div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400">No color options available.</p>
                    @endif
                </div>

                <!-- Description Preview -->
                <div class="text-sm text-slate-600 leading-relaxed">
                    {{ Str::limit($product->description ?? 'No description available for this product.', 150) }}
                </div>

                <!-- Shipping -->
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span class="font-bold text-green-700">Free shipping</span>
                    </div>
                    <p class="text-slate-500 pl-6">Delivery by <span class="font-bold text-slate-900">Feb 22</span></p>
                </div>
            </div>

            <!-- Right Column: Buy Actions (Sticky on Desktop) -->
            <div class="md:col-span-12 lg:col-span-3">
                <div class="border border-slate-200 rounded-xl p-4 shadow-sm sticky top-4 bg-white"
                    x-data="{ quantity: 1 }">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm font-medium text-slate-600">Ship to</span>
                        <span class="text-sm font-bold text-slate-900 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Tanzania
                        </span>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-900 mb-1">Quantity</label>
                        <div class="flex items-center w-full border border-slate-300 rounded-lg">
                            <button @click="quantity > 1 ? quantity-- : null"
                                class="px-3 py-1.5 text-slate-500 hover:bg-slate-100 border-r">-</button>
                            <input type="text" x-model="quantity"
                                class="flex-1 w-full text-center border-none focus:ring-0 py-1.5 font-bold text-slate-900">
                            <button @click="quantity < {{ $product->stock_quantity }} ? quantity++ : null"
                                class="px-3 py-1.5 text-slate-500 hover:bg-slate-100 border-l">+</button>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">{{ $product->stock_quantity }} items available</p>
                    </div>

                    <div class="space-y-3">
                        <button
                            class="w-full bg-[#d9232d] hover:bg-red-700 text-white font-bold py-3 rounded-full shadow-md transition-transform active:scale-95">
                            Buy Now
                        </button>

                        <form action="{{ route('cart.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" :value="quantity">
                            <button type="submit"
                                class="w-full bg-[#fa8900] hover:bg-orange-600 text-white font-bold py-3 rounded-full shadow-md transition-transform active:scale-95">
                                Add to Cart
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 space-y-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Security & Privacy</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Return & Refund policy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Description Section -->
        <div class="mt-12 border-t border-slate-200 pt-8 max-w-4xl">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Product Description</h2>
            <div class="prose prose-slate max-w-none">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>

        <!-- Related Products Section -->
        <div class="mt-16 border-t border-slate-200 pt-8">
            <h2 class="text-xl font-bold text-slate-900 mb-6">More to Love</h2>

            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($relatedProducts as $related)
                    @php
                        $rImages = is_string($related->images ?? null) ? json_decode($related->images, true) : ($related->images ?? []);
                        $rImages = is_array($rImages) ? $rImages : [];
                        $rMainImage = count($rImages) > 0 ? asset('storage/' . $rImages[0]) : 'https://via.placeholder.com/300x300?text=No+Image';
                    @endphp

                    <a href="{{ route('product.show', $related->id) }}"
                        class="group flex flex-col h-full bg-white hover:shadow-lg rounded-lg border border-transparent hover:border-slate-200 transition-all duration-200 overflow-hidden relative p-2">

                        <!-- Image Container -->
                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden relative mb-2">
                            <img src="{{ $rMainImage }}" alt="{{ $related->name }}"
                                onerror="this.onerror=null; this.src='https://via.placeholder.com/300x300?text=No+Available+Image'; this.classList.add('bg-gray-50', 'p-4', 'object-contain');"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 mix-blend-multiply">

                            @if($related->stock_quantity > 0)
                                <div
                                    class="absolute top-2 left-2 bg-[#fa8900] text-white text-[10px] font-bold px-2 py-0.5 rounded-sm shadow-sm">
                                    Choice
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex flex-col flex-grow px-1">
                            <h3
                                class="text-sm font-medium text-slate-800 line-clamp-2 leading-snug mb-1 group-hover:text-[#fa8900] transition-colors">
                                <span
                                    class="bg-yellow-300 text-[10px] font-bold px-1 rounded-sm mr-1 align-middle">Choice</span>
                                {{ $related->name }}
                            </h3>

                            <div class="mt-1">
                                <div class="flex items-baseline gap-0.5">
                                    <span class="text-xs font-semibold">TZS</span>
                                    <span
                                        class="text-xl font-extrabold text-slate-900">{{ number_format($related->price, 0) }}</span>
                                    <span class="text-[10px] font-medium text-slate-500 line-through ml-1.5">
                                        TZS{{ number_format($related->price * 1.3, 0) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 mt-1">
                                <div class="flex text-yellow-400 text-xs">
                                    @for($i = 0; $i < 5; $i++)
                                        @if($i < floor($related->rating))
                                            ★
                                        @else
                                            ☆
                                        @endif
                                    @endfor
                                </div>
                                <span class="text-[10px] text-slate-500">{{ number_format($related->rating, 1) }} | 100+
                                    sold</span>
                            </div>

                            <div class="mt-2 text-[10px] font-medium text-blue-600 flex items-center gap-1 hover:underline">
                                Bundle deals <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>