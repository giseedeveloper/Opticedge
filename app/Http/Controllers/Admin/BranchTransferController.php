<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchTransferLog;
use App\Services\BranchTransferService;
use Illuminate\Http\Request;

class BranchTransferController extends Controller
{
    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $products = \App\Models\Product::with('category')->orderBy('name')->get();

        return view('admin.stock.branch-transfer', compact('branches', 'products'));
    }

    public function branchItems(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable',
            'product_id' => 'nullable|exists:models,id',
        ]);

        $service = app(BranchTransferService::class);

        if ($request->boolean('unassigned')) {
            $items = $service->queryUnassignedItems(
                isset($validated['product_id']) ? (int) $validated['product_id'] : null
            )->get();
        } else {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
            ]);
            $items = $service->queryItemsForBranch(
                (int) $validated['branch_id'],
                isset($validated['product_id']) ? (int) $validated['product_id'] : null
            )->get();
        }

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : '').
                    ($i->product ? ' ('.($i->product->category->name ?? '').' – '.$i->product->name.')' : ''),
            ])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $unassigned = $request->boolean('unassigned');

        $validated = $request->validate([
            'from_branch_id' => 'nullable|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $fromBranchId = $unassigned ? null : (isset($validated['from_branch_id']) ? (int) $validated['from_branch_id'] : null);
        if (! $unassigned && $fromBranchId === null) {
            return back()->withInput()->with('error', 'Select a source branch or mark “Unassigned only”.');
        }

        try {
            app(BranchTransferService::class)->transferItems(
                $validated['product_list_ids'],
                $fromBranchId,
                (int) $validated['to_branch_id'],
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.stock.branch-transfer')->with('success', 'Devices moved to the selected branch.');
    }

    public function logs()
    {
        $logs = BranchTransferLog::with([
            'productListItem.product.category',
            'fromBranch',
            'toBranch',
            'admin',
        ])->latest()->get();

        return view('admin.stock.branch-transfer-logs', compact('logs'));
    }
}
