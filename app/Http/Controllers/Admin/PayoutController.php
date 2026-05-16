<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\Selcompay;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PayoutController extends Controller
{
    /**
     * Pay out hub: tabs for different disbursement workflows.
     */
    public function index(): View
    {
        $eps = 0.0001;

        $creditRows = AgentCredit::query()
            ->with(['agent:id,name,phone'])
            ->where('commission_paid', '>', $eps)
            ->orderByDesc('id')
            ->get();

        $saleRows = AgentSale::query()
            ->with(['agent:id,name,phone'])
            ->where('commission_paid', '>', $eps)
            ->orderByDesc('id')
            ->get();

        $rows = collect();

        foreach ($creditRows as $c) {
            $rows->push([
                'source' => 'credit',
                'source_id' => $c->id,
                'agent_name' => $c->agent?->name ?? '—',
                'mobile' => $c->agent?->phone ?? '—',
                'commission_amount' => (float) $c->commission_paid,
                'payout_booked' => $c->commission_expense_id !== null,
                'sort_date' => $c->date?->timestamp ?? $c->created_at->timestamp,
            ]);
        }

        foreach ($saleRows as $s) {
            $rows->push([
                'source' => 'sale',
                'source_id' => $s->id,
                'agent_name' => $s->agent?->name ?? '—',
                'mobile' => $s->agent?->phone ?? '—',
                'commission_amount' => (float) $s->commission_paid,
                'payout_booked' => $s->commission_expense_id !== null,
                'sort_date' => $s->date?->timestamp ?? $s->created_at->timestamp,
            ]);
        }

        $rows = $rows->sortByDesc('sort_date')->values();

        $latestSelcom = collect();
        $completedSelcomKeys = [];
        if (Schema::hasTable('selcompays') && Schema::hasColumn('selcompays', 'purpose')) {
            $completedSelcomKeys = Selcompay::query()
                ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT)
                ->where('payment_status', 'completed')
                ->whereNotNull('payout_source_type')
                ->whereNotNull('payout_source_id')
                ->get(['payout_source_type', 'payout_source_id'])
                ->map(fn (Selcompay $s) => $s->payout_source_type . ':' . $s->payout_source_id)
                ->unique()
                ->values()
                ->all();
            $completedSelcomKeys = array_fill_keys($completedSelcomKeys, true);

            $latestSelcom = Selcompay::query()
                ->where('purpose', Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT)
                ->whereNotNull('payout_source_type')
                ->whereNotNull('payout_source_id')
                ->orderByDesc('id')
                ->get()
                ->unique(fn (Selcompay $s) => $s->payout_source_type . ':' . $s->payout_source_id)
                ->keyBy(fn (Selcompay $s) => $s->payout_source_type . ':' . $s->payout_source_id);
        }

        $rows = $rows->map(function (array $row) use ($latestSelcom, $completedSelcomKeys) {
            $key = ($row['source'] === 'credit' ? 'agent_credit' : 'agent_sale') . ':' . $row['source_id'];
            $row['selcom_checkout_completed'] = isset($completedSelcomKeys[$key]);
            $row['selcom'] = $latestSelcom->get($key);

            return $row;
        });

        $totals = [
            'lines' => $rows->count(),
            'commission' => $rows->sum('commission_amount'),
            'booked' => $rows->where('payout_booked', true)->sum('commission_amount'),
            'pending' => $rows->where('payout_booked', false)->sum('commission_amount'),
        ];

        $bulkEligibleCount = $rows->filter(function (array $row) {
            if (($row['selcom_checkout_completed'] ?? false) === true) {
                return false;
            }

            $sel = $row['selcom'] ?? null;

            return $row['commission_amount'] > 0.0001
                && (! $sel || in_array($sel->payment_status, ['failed', 'timeout'], true));
        })->count();

        return view('admin.payout.index', [
            'rows' => $rows,
            'totals' => $totals,
            'bulkEligibleCount' => $bulkEligibleCount,
        ]);
    }
}
