<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamLeaderController extends Controller
{
    public function dashboard()
    {
        $leader = Auth::user()->load(['branch', 'region', 'regionalManager']);

        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', $leader->id)
            ->with('branch')
            ->orderBy('name')
            ->get();

        $agentIds = $agents->pluck('id');

        $assignmentTotals = $agentIds->isEmpty()
            ? collect()
            : AgentAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->selectRaw('agent_id, SUM(quantity_assigned) as assigned, SUM(quantity_sold) as sold')
                ->groupBy('agent_id')
                ->get()
                ->keyBy('agent_id');

        $totalAssigned = (int) $assignmentTotals->sum('assigned');
        $totalSold = (int) $assignmentTotals->sum('sold');
        $totalRemaining = max(0, $totalAssigned - $totalSold);

        $activeAgents = $agents->where('status', 'active')->count();

        $unsoldImeiCount = 0;
        $soldImeiCount = 0;
        $totalImeiCount = 0;
        $pendingSaleImeiCount = 0;
        $agentImeiStats = collect();
        $productImeiStats = collect();

        if ($agentIds->isNotEmpty()) {
            $unsoldImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
                ->count();

            $soldImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'))
                ->count();

            $totalImeiCount = $unsoldImeiCount + $soldImeiCount;

            $pendingSaleImeiCount = AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', function ($q) {
                    $q->whereNull('sold_at')->whereNotNull('pending_sale_id');
                })
                ->count();

            $agentImeiStats = AgentProductListAssignment::query()
                ->whereIn('agent_product_list_assignments.agent_id', $agentIds)
                ->join('product_list as pl', 'pl.id', '=', 'agent_product_list_assignments.product_list_id')
                ->selectRaw('agent_product_list_assignments.agent_id, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('agent_product_list_assignments.agent_id')
                ->get()
                ->keyBy('agent_id');

            $productTable = (new Product)->getTable();
            $productImeiStats = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->leftJoin("{$productTable} as p", 'p.id', '=', 'pl.product_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->selectRaw('pl.product_id, MAX(p.name) as product_name, COUNT(*) as imei_total, SUM(CASE WHEN pl.sold_at IS NULL THEN 1 ELSE 0 END) as imei_unsold, SUM(CASE WHEN pl.sold_at IS NOT NULL THEN 1 ELSE 0 END) as imei_sold')
                ->groupBy('pl.product_id')
                ->orderByDesc('imei_total')
                ->limit(12)
                ->get();
        }

        return view('team-leader.dashboard', compact(
            'leader',
            'agents',
            'assignmentTotals',
            'agentImeiStats',
            'productImeiStats',
            'totalAssigned',
            'totalSold',
            'totalRemaining',
            'activeAgents',
            'unsoldImeiCount',
            'soldImeiCount',
            'totalImeiCount',
            'pendingSaleImeiCount'
        ));
    }

    public function teamInventory(Request $request)
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id']);

        $agents->load('branch');

        $agentIds = $agents->pluck('id');

        $productTable = (new Product)->getTable();
        $productChoices = collect();

        if ($agentIds->isNotEmpty()) {
            $productChoices = DB::table('agent_product_list_assignments as apla')
                ->join('product_list as pl', 'pl.id', '=', 'apla.product_list_id')
                ->leftJoin("{$productTable} as p", 'p.id', '=', 'pl.product_id')
                ->whereIn('apla.agent_id', $agentIds)
                ->whereNotNull('pl.product_id')
                ->select('pl.product_id as id', DB::raw('MAX(p.name) as name'))
                ->groupBy('pl.product_id')
                ->orderBy('name')
                ->get();
        }

        $query = AgentProductListAssignment::query()
            ->whereIn('agent_id', $agentIds)
            ->with([
                'agent:id,name,email',
                'productListItem' => function ($q) {
                    $q->with(['product:id,name', 'branch:id,name', 'category:id,name']);
                },
            ]);

        if ($request->filled('agent_id')) {
            $aid = (int) $request->input('agent_id');
            if ($agentIds->contains($aid)) {
                $query->where('agent_id', $aid);
            }
        }

        if ($request->filled('product_id')) {
            $pid = (int) $request->input('product_id');
            $query->whereHas('productListItem', fn ($q) => $q->where('product_id', $pid));
        }

        $status = $request->input('status', 'all');
        if ($status === 'unsold') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'));
        } elseif ($status === 'sold') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'));
        } elseif ($status === 'pending') {
            $query->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at')->whereNotNull('pending_sale_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->input('q'), '%_\\').'%';
            $query->whereHas('productListItem', fn ($q) => $q->where('imei_number', 'like', $term));
        }

        $rows = $agentIds->isEmpty()
            ? new LengthAwarePaginator([], 0, 35, 1, ['path' => $request->url(), 'query' => $request->query()])
            : $query->orderByDesc('id')->paginate(35)->withQueryString();

        $summary = [
            'total' => 0,
            'unsold' => 0,
            'sold' => 0,
            'pending' => 0,
        ];
        if ($agentIds->isNotEmpty()) {
            $summary['total'] = (int) AgentProductListAssignment::query()->whereIn('agent_id', $agentIds)->count();
            $summary['unsold'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
                ->count();
            $summary['sold'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNotNull('sold_at'))
                ->count();
            $summary['pending'] = (int) AgentProductListAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at')->whereNotNull('pending_sale_id'))
                ->count();
        }

        $filterStatus = $status;

        return view('team-leader.team-inventory', compact(
            'agents',
            'rows',
            'productChoices',
            'summary',
            'filterStatus'
        ));
    }

    public function profile()
    {
        return view('team-leader.profile');
    }

    public function orders()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with('items.product')
            ->orderByDesc('created_at')
            ->get();

        return view('team-leader.orders', compact('orders'));
    }

    public function cart()
    {
        $cart = Cart::with(['items.product'])->firstOrCreate(['user_id' => Auth::id()]);

        return view('team-leader.cart', compact('cart'));
    }

    public function assignAgentForm(Request $request)
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->where('team_leader_id', Auth::id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $products = Product::inTeamLeaderCustodyForAgentAssignment((int) Auth::id())->get();

        $selectedAgent = $request->query('agent_id');
        if ($selectedAgent !== null && ! $agents->contains('id', (int) $selectedAgent)) {
            $selectedAgent = null;
        }

        return view('team-leader.assign-agent', compact('agents', 'products', 'selectedAgent'));
    }

    public function assignableImeisForAgent(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::assignableToAgentByTeamLeader((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'imei_number' => $i->imei_number,
                'model' => $i->model,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
                'selectable' => true,
            ])->values()->all(),
            'summary' => ['available' => $items->count(), 'total' => $items->count()],
        ]);
    }

    public function storeAssignAgent(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1|max:500',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $agent = User::findOrFail($validated['agent_id']);

        try {
            $count = $hierarchyService->assignToAgent(
                Auth::user(),
                $agent,
                (int) $validated['product_id'],
                $validated['product_list_ids']
            );
            $message = $count === 1
                ? '1 device assigned to agent.'
                : "{$count} devices assigned to agent.";
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('team-leader.assign-agent')->with('success', $message);
    }

    public function returnDevicesForm()
    {
        $products = Product::returnableByTeamLeaderToRegionalManager((int) Auth::id())->get();

        return view('team-leader.return-devices', compact('products'));
    }

    public function returnableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::returnableByTeamLeader((int) $validated['product_id'], (int) Auth::id())
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
            ])->values()->all(),
        ]);
    }

    public function storeReturnDevices(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        try {
            $count = $hierarchyService->returnFromTeamLeaderToRegionalManager(
                Auth::user(),
                $validated['product_list_ids']
            );
            $message = $count === 1
                ? '1 device returned to regional manager.'
                : "{$count} devices returned to regional manager.";
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('team-leader.return-devices')->with('success', $message);
    }

    public function addressesIndex()
    {
        $addresses = Auth::user()->addresses;

        return view('team-leader.addresses.index', compact('addresses'));
    }

    public function addressesCreate()
    {
        return view('team-leader.addresses.create');
    }

    public function addressesEdit(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        return view('team-leader.addresses.edit', compact('address'));
    }
}
