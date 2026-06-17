<x-team-leader-layout title="Record sale">
    <div class="admin-prod-page max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('team-leader.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Team overview</a>
            <h1 class="admin-prod-title mt-2">Record sale</h1>
            <p class="admin-prod-subtitle">Sell a device in your custody on credit (Watu), or submit a customer lead.</p>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden" x-data="{ tab: 'credit' }">
            <div class="flex gap-2 p-4 border-b border-slate-200/80">
                <button type="button" @click="tab = 'credit'"
                    :class="tab === 'credit' ? 'bg-[#fa8900]/15 text-[#232f3e] border-[#fa8900]/40' : 'text-slate-600 border-transparent'"
                    class="px-4 py-2 rounded-xl border text-sm font-semibold">Credit sale</button>
                <button type="button" @click="tab = 'lead'"
                    :class="tab === 'lead' ? 'bg-[#fa8900]/15 text-[#232f3e] border-[#fa8900]/40' : 'text-slate-600 border-transparent'"
                    class="px-4 py-2 rounded-xl border text-sm font-semibold">Lead</button>
            </div>

            <div class="p-6" x-show="tab === 'credit'" x-cloak>
                @if($availableProducts->isEmpty())
                    <p class="text-slate-600 text-sm">No unsold devices in your custody. Ask your regional manager to assign IMEIs first.</p>
                @else
                    <form method="POST" action="{{ route('team-leader.record-sale.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="product_list_id" class="admin-prod-label">Device</label>
                            <select id="product_list_id" name="product_list_id" class="admin-prod-input mt-1 w-full" required>
                                <option value="">-- Select device --</option>
                                @foreach($availableProducts as $product)
                                    <option value="{{ $product['id'] }}" @selected(old('product_list_id') == $product['id'])>{{ $product['label'] }}</option>
                                @endforeach
                            </select>
                            @error('product_list_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            @error('sale')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="customer_name" class="admin-prod-label">Customer name</label>
                            <input id="customer_name" name="customer_name" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('customer_name') }}" required>
                        </div>

                        <div>
                            <label for="customer_phone" class="admin-prod-label">Customer phone</label>
                            <input id="customer_phone" name="customer_phone" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('customer_phone') }}">
                        </div>

                        <div>
                            <label for="kin_name" class="admin-prod-label">Kin name</label>
                            <input id="kin_name" name="kin_name" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('kin_name') }}">
                        </div>

                        <div>
                            <label for="kin_phone" class="admin-prod-label">Kin phone</label>
                            <input id="kin_phone" name="kin_phone" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('kin_phone') }}">
                        </div>

                        <div>
                            <label for="selling_price" class="admin-prod-label">Sell price (per unit)</label>
                            <input id="selling_price" name="selling_price" type="number" step="0.01" min="0" class="admin-prod-input mt-1 w-full" value="{{ old('selling_price') }}" required>
                        </div>

                        <div>
                            <label class="admin-prod-label">Payment channel</label>
                            <p class="mt-1 text-sm text-slate-700 font-medium">{{ $watuChannel['name'] ?? 'Default Watu channel (set by admin)' }}</p>
                        </div>

                        <div>
                            <label for="description" class="admin-prod-label">Description</label>
                            <textarea id="description" name="description" rows="3" class="admin-prod-input mt-1 w-full">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit" class="admin-prod-btn-primary">Complete Watu sale</button>
                    </form>
                @endif
            </div>

            <div class="p-6" x-show="tab === 'lead'" x-cloak>
                <form method="POST" action="{{ route('team-leader.leads.store') }}" class="space-y-4"
                    x-data="{
                        categoryId: '{{ old('category_id') }}',
                        products: [],
                        async loadProducts() {
                            if (!this.categoryId) { this.products = []; return; }
                            const res = await fetch(`{{ url('team-leader/leads/products') }}/${this.categoryId}`);
                            const json = await res.json();
                            this.products = json.data || [];
                        }
                    }" x-init="if (categoryId) loadProducts()">
                    @csrf
                    <div>
                        <label for="category_id" class="admin-prod-label">Category</label>
                        <select id="category_id" name="category_id" class="admin-prod-input mt-1 w-full" required
                            x-model="categoryId" @change="loadProducts()">
                            <option value="">-- Select category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="product_id" class="admin-prod-label">Model</label>
                        <select id="product_id" name="product_id" class="admin-prod-input mt-1 w-full" required>
                            <option value="">-- Select model --</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.name" :selected="p.id == '{{ old('product_id') }}'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="lead_customer_name" class="admin-prod-label">Customer name</label>
                        <input id="lead_customer_name" name="customer_name" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('customer_name') }}" required>
                    </div>

                    <div>
                        <label for="lead_customer_phone" class="admin-prod-label">Customer phone</label>
                        <input id="lead_customer_phone" name="customer_phone" type="text" class="admin-prod-input mt-1 w-full" value="{{ old('customer_phone') }}" required>
                    </div>

                    @if($branches->isNotEmpty())
                        <div>
                            <label for="branch_id" class="admin-prod-label">Branch</label>
                            <select id="branch_id" name="branch_id" class="admin-prod-input mt-1 w-full">
                                <option value="">-- Select branch --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @error('lead')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                    <button type="submit" class="admin-prod-btn-primary">Submit lead</button>
                </form>
            </div>
        </div>
    </div>
</x-team-leader-layout>
