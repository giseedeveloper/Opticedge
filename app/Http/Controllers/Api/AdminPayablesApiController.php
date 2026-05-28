<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use Illuminate\Http\JsonResponse;

class AdminPayablesApiController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Payable::query()
            ->latest('date')
            ->limit(300)
            ->get()
            ->map(fn (Payable $p) => [
                'id' => $p->id,
                'date' => optional($p->date)->toDateString(),
                'item_name' => $p->item_name,
                'amount' => (float) ($p->amount ?? 0),
            ]);

        return response()->json(['data' => $rows]);
    }
}
