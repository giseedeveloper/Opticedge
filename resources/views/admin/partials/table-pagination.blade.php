@props(['paginator', 'label' => 'entries'])

@if ($paginator->total() > 0)
    <div class="border-t border-slate-200/70 px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
        <p>
            Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }}
            of {{ number_format($paginator->total()) }} {{ $label }}
        </p>
        {{ $paginator->links() }}
    </div>
@endif
