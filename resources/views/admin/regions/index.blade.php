<x-admin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page">
        <div class="admin-prod-toolbar flex justify-between mb-4">
            <div><h1 class="admin-prod-title">Regions</h1><p class="admin-prod-subtitle">Platform regions plus your tenant additions.</p></div>
            <a href="{{ route('admin.regions.create') }}" class="admin-prod-btn-primary">Add region</a>
        </div>
        @if(session('success'))<div class="admin-prod-alert admin-prod-alert--success mb-4">{{ session('success') }}</div>@endif
        <div class="admin-clay-panel">
            <ul class="divide-y text-sm">@foreach($regions as $region)
                <li class="p-3 flex justify-between"><span>{{ $region->name }} @if($region->is_platform)<span class="text-xs text-slate-400">(platform)</span>@endif</span></li>
            @endforeach</ul>
        </div>
    </div>
</x-admin-layout>
