<?php

namespace App\Http\Controllers;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\PendingSale;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Services\DeviceHierarchyAssignmentService;
use App\Services\DistributionSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    public function dashboard()
    {
        $assignments = AgentAssignment::where('agent_id', Auth::id())
            ->with('product.category')
            ->get();
        $totalAssigned = $assignments->sum('quantity_assigned');
        $totalSold = $assignments->sum('quantity_sold');
        $totalRemaining = $totalAssigned - $totalSold;

        return view('agent.dashboard', compact('assignments', 'totalAssigned', 'totalSold', 'totalRemaining'));
    }

    public function recordSaleForm(AgentAssignment $assignment)
    {
        if ($assignment->agent_id !== Auth::id()) {
            abort(403);
        }
        $assignment->load('product.category');
        $maxQty = $assignment->quantity_assigned - $assignment->quantity_sold;
        if ($maxQty <= 0) {
            return redirect()->route('agent.dashboard')->with('error', 'No quantity remaining to sell for this product.');
        }
        return view('agent.record-sale', compact('assignment', 'maxQty'));
    }

    public function recordSale(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:agent_assignments,id',
            'customer_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $assignment = AgentAssignment::with('product')->findOrFail($validated['assignment_id']);
        if ($assignment->agent_id !== Auth::id()) {
            abort(403);
        }

        $quantitySold = 1;
        $maxQty = $assignment->quantity_assigned - $assignment->quantity_sold;
        if ($quantitySold > $maxQty) {
            return back()->withErrors(['quantity_sold' => "Maximum quantity available is {$maxQty}."])->withInput();
        }

        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($assignment->product_id);
        $totalBuy = $buyPrice * $quantitySold;
        $totalSell = $quantitySold * $validated['selling_price'];
        $profit = $totalSell - $totalBuy;

        // Save to pending sales instead of agent_sales
        PendingSale::create([
            'customer_name' => $validated['customer_name'],
            'seller_name' => Auth::user()->name,
            'product_id' => $assignment->product_id,
            'quantity_sold' => $quantitySold,
            'purchase_price' => $buyPrice,
            'selling_price' => $validated['selling_price'],
            'total_purchase_value' => $totalBuy,
            'total_selling_value' => $totalSell,
            'profit' => $profit,
            'date' => now()->toDateString(),
        ]);

        $assignment->increment('quantity_sold', $quantitySold);

        return redirect()->route('agent.dashboard')->with('success', 'Sale recorded. It will appear in admin Pending Sales for payment option selection.');
    }

    public function returnDevicesForm()
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

        return view('agent.return-devices', compact('products'));
    }

    public function returnableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::returnableByAgent((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
            ])->values()->all(),
        ]);
    }

    public function returnDevicesStore(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        try {
            $count = $hierarchyService->returnFromAgentToTeamLeader(
                Auth::user(),
                $validated['product_list_ids']
            );
            $message = $count === 1
                ? '1 device returned to your team leader.'
                : "{$count} devices returned to your team leader.";
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.return-devices')->with('success', $message);
    }
}
