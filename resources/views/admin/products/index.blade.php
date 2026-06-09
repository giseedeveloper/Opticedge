<x-admin-layout>
    @include('admin.products.partials.styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Catalog</p>
                <h1 class="admin-prod-title">Products</h1>
                <p class="admin-prod-subtitle">Models, media, and quick actions for your inventory.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="admin-prod-btn-primary shrink-0">
                Add product
            </a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush">
                <table id="models-table">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Image</th>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Category</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--desc">Description</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="admin-prod-thumb">
                                        @if(!empty($product->images) && count($product->images) > 0)
                                            <img src="{{ Storage::url($product->images[0]) }}" alt=""
                                                class="w-full h-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                </td>
                                <td class="font-semibold text-[#232f3e]">{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td class="max-w-md">
                                    @if(filled($product->description))
                                        @php($descPlain = strip_tags($product->description))
                                        <p class="line-clamp-3 text-sm" title="{{ e(\Illuminate\Support\Str::limit($descPlain, 500)) }}">{{ \Illuminate\Support\Str::limit($descPlain, 280) }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('admin.products.edit', $product->id) }}"
                                            class="admin-prod-link">Edit</a>
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                            class="inline"
                                            onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link admin-prod-link--danger bg-transparent border-0 cursor-pointer p-0 font-inherit">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
