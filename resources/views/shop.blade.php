<x-app-layout>
    <div class="relative bg-gray-200">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-300 to-gray-100 h-[520px]"></div>

        <div class="relative max-w-[1500px] mx-auto h-[520px] flex items-center px-4">
            <div class="space-y-4 max-w-lg z-10 p-8 rounded-lg">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 tracking-tight">
                    Premium phones. Trusted deals. <br> <span class="text-[#fa8900]">Phones you'll love</span>
                </h1>
                <p class="text-lg text-gray-700 font-medium">
                    Shop the latest smartphones with fast delivery, secure payments, and exclusive offers.
                </p>
                <a href="{{ route('dealer.register') }}"
                    class="inline-block bg-[#fa8900] hover:bg-[#e87f00] text-white px-8 py-3 rounded-full font-bold shadow-md transition-transform transform hover:scale-105">
                    Register as a Dealer
                </a>
            </div>
            <img src="https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?q=80&w=1000&auto=format&fit=crop"
                class="absolute right-0 top-0 h-full w-1/2 object-cover opacity-50"
                alt="Smartphones">
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-slate-50 to-transparent"></div>
        </div>
    </div>

    <div class="max-w-[1600px] mx-auto px-4 -mt-24 relative z-20 pb-12">
        <livewire:product-showcase />
    </div>
</x-app-layout>
