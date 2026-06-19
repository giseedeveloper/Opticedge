<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentProductTransfer;
use App\Services\AgentProductTransferService;
use Illuminate\Http\Request;

class AgentTransferController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.stock.device-transfers', $request->only('status'));
    }

    public function show(AgentProductTransfer $agent_product_transfer)
    {
        $agent_product_transfer->load([
            'fromAgent',
            'toAgent',
            'decidedByUser',
            'items.productListItem.product.category',
            'items.productListItem.purchase.branch',
            'items.productListItem.stock',
            'items.productListItem.branch',
        ]);

        return view('admin.stock.agent-transfers-show', ['transfer' => $agent_product_transfer]);
    }

    public function approve(Request $request, AgentProductTransfer $agent_product_transfer)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->approve(
                $agent_product_transfer,
                $request->user(),
                $validated['admin_note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.stock.agent-transfers.show', $agent_product_transfer)->with('success', 'Transfer approved.');
    }

    public function reject(Request $request, AgentProductTransfer $agent_product_transfer)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->reject(
                $agent_product_transfer,
                $request->user(),
                $validated['admin_note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.stock.agent-transfers.show', $agent_product_transfer)->with('success', 'Transfer rejected.');
    }
}
