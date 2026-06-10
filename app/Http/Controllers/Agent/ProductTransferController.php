<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentProductListAssignment;
use App\Models\AgentProductTransfer;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use App\Services\AgentProductTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductTransferController extends Controller
{
    public function index()
    {
        $agentId = (int) Auth::id();
        $transfers = AgentProductTransfer::query()
            ->with(['fromAgent', 'toAgent', 'items'])
            ->where(function ($q) use ($agentId) {
                $q->where('from_agent_id', $agentId)->orWhere('to_agent_id', $agentId);
            })
            ->latest()
            ->get();

        return view('agent.transfers-index', compact('transfers'));
    }

    public function create()
    {
        $productIds = AgentProductListAssignment::query()
            ->where('agent_id', Auth::id())
            ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
            ->with('productListItem')
            ->get()
            ->pluck('productListItem.product_id')
            ->unique()
            ->filter()
            ->values();

        $products = Product::whereIn('id', $productIds)->with('category')->orderBy('name')->get();
        $agents = User::query()
            ->where('role', 'agent')
            ->where('status', 'active')
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('agent.transfer-create', compact('products', 'agents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'to_agent_id' => 'required|integer|exists:users,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->createTransfer(
                Auth::user(),
                (int) $validated['to_agent_id'],
                $validated['product_list_ids'],
                $validated['message'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.transfers.index')->with('success', 'Transfer request submitted. Waiting for the receiving agent to accept.');
    }

    public function transferableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::transferableByAgent((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
            ])->values()->all(),
        ]);
    }

    public function cancel(AgentProductTransfer $transfer)
    {
        try {
            app(AgentProductTransferService::class)->cancelOwn($transfer, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer cancelled.');
    }

    public function accept(Request $request, AgentProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->acceptByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer accepted. Devices are now assigned to you.');
    }

    public function decline(Request $request, AgentProductTransfer $transfer)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(AgentProductTransferService::class)->declineByRecipient(
                $transfer,
                Auth::user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer declined.');
    }
}
