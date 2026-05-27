<div>
    <!-- Categories Section -->
    @if($showCategories)
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                {{-- <h2 class="text-2xl font-bold text-gray-800">Shop by Category</h2> --}}
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($categories as $category)
                    <a href="{{ route('category.show', $category->id) }}"
                        class="group cursor-pointer flex flex-col items-center p-4 rounded-2xl transition-all duration-300 {{ $categoryId == $category->id ? 'bg-[#fa8900]/10 ring-2 ring-[#fa8900]' : 'bg-white hover:bg-gray-50 border border-gray-100 shadow-sm hover:shadow-md' }}">
                        <div
                            class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden mb-3 ring-2 ring-white shadow-inner bg-gray-100">
                            @if($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                                    onerror="this.onerror=null; this.src='https://via.placeholder.com/100?text={{ urlencode($category->name) }}'; this.parentElement.classList.add('hide-image-error');"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 m0 2.828l.793-.793M11 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <span
                            class="text-sm font-bold text-center {{ $categoryId == $category->id ? 'text-[#fa8900]' : 'text-gray-700' }} group-hover:text-[#fa8900] transition-colors">
                            {{ $category->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Products Grid -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            @if($categoryId && $showCategories)
                Products in {{ $categories->find($categoryId)->name }}
            @elseif(!$showCategories)
                Category Products
            @else
                Recommended for You
            @endif
        </h2>

        @if($products->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-600">No products found</h3>
                <p class="text-gray-400">We couldn't find any products in this category at the moment.</p>
                <a href="{{ route('shop') }}" class="mt-4 text-[#fa8900] font-medium hover:underline inline-block">Show all products</a>
            </div>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                @foreach($products as $product)
                    @php
                        $images = is_string($product->images ?? null) ? json_decode($product->images, true) : ($product->images ?? []);
                        $cardImage = !empty($images) && is_array($images) ? asset('storage/' . $images[0]) : 'https://via.placeholder.com/300x300?text=No+Image';
                    @endphp

                    <a href="{{ route('product.show', $product->id) }}"
                        class="group flex flex-col h-full bg-white hover:shadow-xl rounded-2xl border border-gray-100 transition-all duration-300 overflow-hidden relative p-3">

                        <!-- Image Container (uses product image from purchases) -->
                        <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden relative mb-3">
                            <img src="{{ $cardImage }}" alt="{{ $product->name }}"
                                onerror="this.onerror=null; this.src='https://via.placeholder.com/400x400?text=No+Available+Image'; this.classList.add('bg-gray-100', 'p-4', 'object-contain');"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">

                            <!-- Badge -->
                            @if($product->stock_quantity > 0)
                                <div
                                    class="absolute top-3 left-3 bg-[#fa8900] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                                    Choice
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex flex-col flex-grow px-1">
                            <!-- Title -->
                            <h3
                                class="text-sm font-semibold text-gray-800 line-clamp-2 leading-tight mb-2 group-hover:text-[#fa8900] transition-colors">
                                {{ $product->name }}
                            </h3>

                            <!-- Price -->
                            <div class="mt-auto">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs font-bold text-[#fa8900]">TZS</span>
                                    <span
                                        class="text-xl font-black text-gray-900">{{ number_format($product->price, 0) }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[11px] font-medium text-gray-400 line-through">
                                        TZS{{ number_format($product->price * 1.3, 0) }}
                                    </span>
                                    <span class="text-[10px] font-bold text-green-500 bg-green-50 px-1.5 py-0.5 rounded">
                                        -30%
                                    </span>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-50">
                                <div class="flex text-yellow-400 text-[10px]">
                                    @for($i = 0; $i < 5; $i++)
                                        <svg class="w-3 h-3 {{ $i < floor($product->rating) ? 'fill-current' : 'text-gray-200 fill-none' }}"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                            </path>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-[10px] font-medium text-gray-500">{{ number_format($product->rating, 1) }}
                                    (120+)</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>