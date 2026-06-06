<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Services\SelcomAgentCommissionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPayoutApiController extends Controller
{
    public function index(): JsonResponse
    {
        $eps = 0.0001;
        $rows = collect();

        foreach (AgentCredit::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', $eps)->orderByDesc('id')->limit(100)->get() as $c) {
            $rows->push([
                'source' => 'credit',
                'source_id' => $c->id,
                'agent_name' => $c->agent?->name ?? '—',
                'mobile' => $c->agent?->phone ?? '—',
                'commission_amount' => (float) $c->commission_paid,
                'payout_booked' => $c->commission_expense_id !== null,
            ]);
        }

        foreach (AgentSale::query()->with(['agent:id,name,phone'])->where('commission_paid', '>', $eps)->orderByDesc('id')->limit(100)->get() as $s) {
            $rows->push([
                'source' => 'sale',
                'source_id' => $s->id,
                'agent_name' => $s->agent?->name ?? '—',
                'mobile' => $s->agent?->phone ?? '—',
                'commission_amount' => (float) $s->commission_paid,
                'payout_booked' => $s->commission_expense_id !== null,
            ]);
        }

        return response()->json([
            'data' => $rows->sortByDesc('commission_amount')->values(),
        ]);
    }

    public function bulkSelcom(): JsonResponse
    {
        $summary = app(SelcomAgentCommissionCheckoutService::class)->bulkInitiateEligibleLines();

        return response()->json([
            'message' => 'Bulk Selcom payout initiated.',
            'data' => $summary,
        ]);
    }
}
