<x-app-layout>
    <div class="bg-slate-50 min-h-screen pb-12">
        <!-- Category Hero -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-[1600px] mx-auto px-4 py-8 lg:py-12">
                <nav class="flex mb-4 text-sm text-gray-500" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li><a href="{{ route('shop') }}" class="hover:text-[#fa8900]">Home</a></li>
                        <li>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </li>
                        <li class="font-semibold text-gray-900">{{ $category->name }}</li>
                    </ol>
                </nav>

                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div
                        class="w-32 h-32 md:w-48 md:h-48 rounded-2xl overflow-hidden bg-gray-100 shadow-inner flex-shrink-0">
                        @if($category->image)
                            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}"
                                class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 m0 2.828l.793-.793M11 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-5xl font-black text-gray-900 mb-4 tracking-tight">
                            {{ $category->name }}
                        </h1>
                        <p class="text-lg text-gray-600 max-w-2xl leading-relaxed">
                            Explore our premium selection of {{ strtolower($category->name) }}. We offer the best
                            quality and professional gear tailored for your needs.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="max-w-[1600px] mx-auto px-4 py-12">
            <livewire:product-showcase :category-id="$category->id" :show-categories="false" />
        </div>
    </div>
</x-app-layout>