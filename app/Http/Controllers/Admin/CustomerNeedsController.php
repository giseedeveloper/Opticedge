<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNeed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerNeedsController extends Controller
{
    /**
     * Agent app → Sell → Needed: category + model requests.
     */
    public function index(Request $request): View
    {
        $selectedPeriod = $request->query('period', 'week');
        $today = Carbon::today();
        $start = null;
        $end = null;

        if ($selectedPeriod === 'week') {
            $start = $today->copy()->startOfWeek();
            $end = $today->copy()->endOfWeek();
        } elseif ($selectedPeriod === 'month') {
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->endOfMonth();
        } elseif ($selectedPeriod === 'year') {
            $start = $today->copy()->startOfYear();
            $end = $today->copy()->endOfDay();
        } else {
            $selectedPeriod = 'week';
            $start = $today->copy()->startOfWeek();
            $end = $today->copy()->endOfWeek();
        }

        $customerNeedsQuery = CustomerNeed::query()
            ->with(['agent', 'teamLeader', 'category', 'product', 'branch'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('id');

        $customerNeeds = $customerNeedsQuery->paginate(50)->withQueryString();

        $topProducts = CustomerNeed::query()
            ->selectRaw('product_id, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return (int) $row->product_id;
            })
            ->filter()
            ->values();

        $productNames = \App\Models\Product::query()
            ->whereIn('id', $topProducts)
            ->pluck('name', 'id');

        $rawSeries = CustomerNeed::query()
            ->selectRaw('DATE(created_at) as day, product_id, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('product_id', $topProducts)
            ->groupByRaw('DATE(created_at), product_id')
            ->orderBy('day')
            ->get();

        $seriesMap = [];
        foreach ($rawSeries as $row) {
            $day = (string) $row->day;
            $pid = (int) $row->product_id;
            $seriesMap[$day][$pid] = (int) $row->total;
        }

        $chartRows = [];
        $header = ['Date'];
        foreach ($topProducts as $pid) {
            $header[] = (string) ($productNames[$pid] ?? ('Product ' . $pid));
        }
        $chartRows[] = $header;

        $cursor = $start->copy();
        $endDate = $end->copy()->startOfDay();
        while ($cursor->lte($endDate)) {
            $day = $cursor->toDateString();
            $row = [$day];
            foreach ($topProducts as $pid) {
                $row[] = (int) ($seriesMap[$day][$pid] ?? 0);
            }
            $chartRows[] = $row;
            $cursor->addDay();
        }

        return view('admin.customer-needs.index', [
            'customerNeeds' => $customerNeeds,
            'selectedPeriod' => $selectedPeriod,
            'chartRows' => $chartRows,
        ]);
    }
}
