<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerNeed;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLeadsReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $selectedPeriod = $request->query('period', 'week');
        $today = Carbon::today();
        $start = $today->copy()->startOfWeek();
        $end = $today->copy()->endOfWeek();

        if ($selectedPeriod === 'month') {
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->endOfMonth();
        } elseif ($selectedPeriod === 'year') {
            $start = $today->copy()->startOfYear();
            $end = $today->copy()->endOfDay();
        }

        $needs = CustomerNeed::query()
            ->with(['agent:id,name', 'category:id,name', 'product:id,name', 'branch:id,name'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->limit(500)
            ->get()
            ->map(fn (CustomerNeed $n) => [
                'id' => $n->id,
                'created_at' => $n->created_at?->toISOString(),
                'agent_name' => $n->agent?->name,
                'category_name' => $n->category?->name,
                'product_name' => $n->product?->name,
                'branch_name' => $n->branch?->name,
                'customer_name' => $n->customer_name,
                'customer_phone' => $n->customer_phone,
            ]);

        return response()->json([
            'data' => $needs,
            'period' => $selectedPeriod,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'count' => $needs->count(),
        ]);
    }
}
