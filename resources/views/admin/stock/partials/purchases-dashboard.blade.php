<dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <dt class="text-xs uppercase text-slate-500">{{ $isPassthrough ? 'Entries' : 'Purchases' }}</dt>
        <dd class="text-lg font-semibold text-slate-900">{{ number_format($purchaseDashboard['count']) }}</dd>
    </div>
    <div>
        <dt class="text-xs uppercase text-slate-500">Total purchase value</dt>
        <dd class="text-lg font-semibold text-slate-900">{{ number_format($purchaseDashboard['total_value'], 2) }} TZS</dd>
    </div>
    <div>
        <dt class="text-xs uppercase text-slate-500">Pending to pay</dt>
        <dd class="text-lg font-semibold text-amber-700">{{ number_format($purchaseDashboard['pending_amount'], 2) }} TZS</dd>
    </div>
</dl>
