<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\User;
use App\Services\DeviceHierarchyAssignmentService;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role') && in_array($request->role, User::customerDirectoryRoleFilters(), true)) {
            $query->where('role', $request->role);
        }

        $customers = $query->latest()->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function regionalManagersIndex()
    {
        $query = User::query()
            ->where('role', 'regional_manager')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'region_id')) {
            $query->with('region:id,name');
        }

        $managers = $query->paginate(20);

        return view('admin.customers.regional-managers.index', compact('managers'));
    }

    public function teamLeadersIndex()
    {
        $query = User::query()
            ->where('role', 'teamleader')
            ->orderBy('name');

        if (Schema::hasColumn('users', 'region_id')) {
            $query->with('region:id,name');
        }

        if (Schema::hasColumn('users', 'branch_id')) {
            $query->with('branch:id,name');
        }

        if (Schema::hasColumn('users', 'regional_manager_id')) {
            $query->with('regionalManager:id,name');
        }

        $teamLeaders = $query->paginate(20);

        return view('admin.customers.team-leaders.index', compact('teamLeaders'));
    }

    public function createRegionalManager()
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('users', 'region_id')) {
            return redirect()->route('admin.customers.regional-managers.index')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        $regions = DB::table('regions')->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.regional-managers.create', compact('regions'));
    }

    public function createTeamLeader()
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('users', 'region_id')) {
            return redirect()->route('admin.customers.team-leaders.index')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        if (! Schema::hasTable('branches') || ! Schema::hasColumn('users', 'branch_id')) {
            return redirect()->route('admin.customers.team-leaders.index')
                ->withErrors(['error' => 'Branches are not set up yet. Create a branch first.']);
        }

        $regions = DB::table('regions')->orderBy('name')->get(['id', 'name']);
        $branches = DB::table('branches')->orderBy('name')->get(['id', 'name']);
        $regionalManagers = $this->regionalManagersForSelect();

        return view('admin.customers.team-leaders.create', compact('regions', 'branches', 'regionalManagers'));
    }

    /**
     * @return Collection<int, object>
     */
    private function regionalManagersForSelect(): Collection
    {
        if (! Schema::hasColumn('users', 'regional_manager_id')) {
            return collect();
        }

        $query = DB::table('users')
            ->where('role', 'regional_manager')
            ->where('status', 'active')
            ->whereNotNull('region_id')
            ->orderBy('name')
            ->select(['id', 'name', 'region_id']);

        $managers = $query->get();

        if (! Schema::hasTable('regions') || $managers->isEmpty()) {
            return $managers;
        }

        $regionNames = DB::table('regions')->pluck('name', 'id');

        return $managers->map(function ($row) use ($regionNames) {
            $row->region_name = $regionNames[$row->region_id] ?? null;

            return $row;
        });
    }

    public function storeRegionalManager(Request $request)
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('users', 'region_id')) {
            return redirect()->route('admin.customers.regional-managers.create')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:100',
            'region_id' => 'required|exists:regions,id',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => 'regional_manager',
            'status' => 'active',
            'region_id' => (int) $validated['region_id'],
            'branch_id' => null,
            'regional_manager_id' => null,
            'business_name' => $validated['business_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if (! Schema::hasColumn('users', 'notes')) {
            unset($payload['notes']);
        }

        if (Schema::hasColumn('users', 'ability')) {
            $payload['ability'] = 'fullaccess';
        }

        $user = User::create($payload);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.customers.regional-managers.index')
            ->with('success', 'Regional manager account created. They can sign in with the email and password you set.');
    }

    public function storeTeamLeader(Request $request)
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('users', 'region_id')) {
            return redirect()->route('admin.customers.team-leaders.create')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        if (! Schema::hasTable('branches') || ! Schema::hasColumn('users', 'branch_id')) {
            return redirect()->route('admin.customers.team-leaders.create')
                ->withErrors(['error' => 'Branches are not set up yet. Create a branch first.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:100',
            'region_id' => 'required|exists:regions,id',
            'branch_id' => 'required|exists:branches,id',
            'regional_manager_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'regional_manager')->where('status', 'active');
                }),
            ],
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
        ]);

        $managerRegionId = DB::table('users')
            ->where('id', $validated['regional_manager_id'])
            ->where('role', 'regional_manager')
            ->where('status', 'active')
            ->value('region_id');

        if ($managerRegionId === null || (int) $managerRegionId !== (int) $validated['region_id']) {
            return redirect()->route('admin.customers.team-leaders.create')
                ->withErrors(['regional_manager_id' => 'The selected regional manager must belong to the same region you selected.'])
                ->withInput();
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => 'teamleader',
            'status' => 'active',
            'region_id' => (int) $validated['region_id'],
            'branch_id' => (int) $validated['branch_id'],
            'regional_manager_id' => (int) $validated['regional_manager_id'],
            'business_name' => $validated['business_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if (! Schema::hasColumn('users', 'notes')) {
            unset($payload['notes']);
        }

        if (Schema::hasColumn('users', 'ability')) {
            $payload['ability'] = 'fullaccess';
        }

        $user = User::create($payload);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.customers.team-leaders.index')
            ->with('success', 'Team leader account created. They can sign in with the email and password you set.');
    }

    public function assignRegionalManagerDevicesForm(Request $request)
    {
        $managers = User::query()
            ->where('role', 'regional_manager')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $products = Product::whereHas('purchases')->with('category')->orderBy('name')->get();

        $selectedManager = $request->query('regional_manager_id');
        if ($selectedManager !== null && ! $managers->contains('id', (int) $selectedManager)) {
            $selectedManager = null;
        }

        return view('admin.customers.regional-managers.assign-devices', compact('managers', 'products', 'selectedManager'));
    }

    public function assignableImeisForRegionalManager(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
        ]);

        $items = ProductListItem::assignableFromAdmin((int) $validated['product_id'])
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model']);

        return response()->json([
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'text' => $i->imei_number.($i->model ? ' – '.$i->model : ''),
            ])->values()->all(),
        ]);
    }

    public function storeAssignRegionalManagerDevices(Request $request, DeviceHierarchyAssignmentService $hierarchyService)
    {
        $validated = $request->validate([
            'regional_manager_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'regional_manager')),
            ],
            'product_id' => 'required|exists:models,id',
            'product_list_ids' => 'required|array|min:1',
            'product_list_ids.*' => 'distinct|integer|exists:product_list,id',
        ]);

        $regionalManager = User::findOrFail($validated['regional_manager_id']);

        try {
            $count = $hierarchyService->assignToRegionalManager(
                $regionalManager,
                (int) $validated['product_id'],
                $validated['product_list_ids']
            );
            $message = $count === 1
                ? '1 device assigned to regional manager.'
                : "{$count} devices assigned to regional manager.";
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.customers.regional-managers.assign-devices', [
                'regional_manager_id' => $regionalManager->id,
            ])
            ->with('success', $message);
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        return redirect()->route('admin.customers.index', request()->query())
            ->with('success', 'User activated successfully.');
    }

    public function deactivate(User $user)
    {
        if (($user->role ?? '') === 'admin') {
            return redirect()->route('admin.customers.index', request()->query())
                ->withErrors(['error' => 'Admin account cannot be deactivated here.']);
        }

        $user->update(['status' => 'inactive']);

        return redirect()->route('admin.customers.index', request()->query())
            ->with('success', 'User deactivated successfully.');
    }

    public function destroy(User $user)
    {
        if (($user->role ?? '') === 'admin') {
            return redirect()->route('admin.customers.index', request()->query())
                ->withErrors(['error' => 'Admin account cannot be deleted here.']);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('admin.customers.index', request()->query())
                ->withErrors(['error' => 'You cannot delete your own account.']);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.customers.index', request()->query())
                ->withErrors(['error' => 'Cannot delete this user because it is linked to existing records.']);
        }

        return redirect()->route('admin.customers.index', request()->query())
            ->with('success', 'User deleted successfully.');
    }
}
