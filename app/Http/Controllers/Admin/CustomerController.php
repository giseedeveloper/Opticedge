<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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

        $regions = Schema::hasTable('regions')
            ? Region::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.customers.regional-managers.index', compact('managers', 'regions'));
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

        $regions = Schema::hasTable('regions')
            ? Region::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $branches = Schema::hasTable('branches')
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $regionalManagers = $this->regionalManagersForForms();

        return view('admin.customers.team-leaders.index', compact('teamLeaders', 'regions', 'branches', 'regionalManagers'));
    }

    /**
     * @return Collection<int, User>
     */
    private function regionalManagersForForms(): Collection
    {
        $regionalManagersQuery = User::query()
            ->where('role', 'regional_manager')
            ->where('status', 'active');

        if (Schema::hasColumn('users', 'region_id')) {
            $regionalManagersQuery->whereNotNull('region_id');
        }

        if (Schema::hasTable('regions')) {
            $regionalManagersQuery->with('region:id,name');
        }

        $regionalManagerColumns = ['id', 'name'];
        if (Schema::hasColumn('users', 'region_id')) {
            $regionalManagerColumns[] = 'region_id';
        }

        return $regionalManagersQuery
            ->orderBy('name')
            ->get($regionalManagerColumns);
    }

    public function storeRegionalManager(Request $request)
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('users', 'region_id')) {
            return redirect()->route('admin.customers.regional-managers.index')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        $validated = $request->validate([
            'rm.name' => 'required|string|max:255',
            'rm.email' => 'required|string|email|max:255|unique:users,email',
            'rm.phone' => 'nullable|string|max:100',
            'rm.region_id' => 'required|exists:regions,id',
            'rm.password' => 'required|string|min:8|confirmed',
            'rm.business_name' => 'nullable|string|max:255',
            'rm.notes' => 'nullable|string|max:10000',
        ]);

        $rm = $validated['rm'];

        $payload = [
            'name' => $rm['name'],
            'email' => $rm['email'],
            'password' => $rm['password'],
            'phone' => $rm['phone'] ?? null,
            'role' => 'regional_manager',
            'status' => 'active',
            'region_id' => (int) $rm['region_id'],
            'branch_id' => null,
            'regional_manager_id' => null,
            'business_name' => $rm['business_name'] ?? null,
            'notes' => $rm['notes'] ?? null,
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
            return redirect()->route('admin.customers.team-leaders.index')
                ->withErrors(['error' => 'Regions are not set up yet. Run migrations and seed regions first.']);
        }

        if (! Schema::hasTable('branches') || ! Schema::hasColumn('users', 'branch_id')) {
            return redirect()->route('admin.customers.team-leaders.index')
                ->withErrors(['error' => 'Branches are not set up yet. Create a branch first.']);
        }

        $validated = $request->validate([
            'tl.name' => 'required|string|max:255',
            'tl.email' => 'required|string|email|max:255|unique:users,email',
            'tl.phone' => 'nullable|string|max:100',
            'tl.region_id' => 'required|exists:regions,id',
            'tl.branch_id' => 'required|exists:branches,id',
            'tl.regional_manager_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'regional_manager')->where('status', 'active');
                }),
            ],
            'tl.password' => 'required|string|min:8|confirmed',
            'tl.business_name' => 'nullable|string|max:255',
            'tl.notes' => 'nullable|string|max:10000',
        ]);

        $tl = $validated['tl'];

        $manager = User::query()
            ->where('id', $tl['regional_manager_id'])
            ->where('role', 'regional_manager')
            ->first();

        if (! $manager || (int) $manager->region_id !== (int) $tl['region_id']) {
            return redirect()->route('admin.customers.team-leaders.index')
                ->withErrors(['error' => 'The selected regional manager must belong to the same region you selected.'])
                ->withInput();
        }

        $payload = [
            'name' => $tl['name'],
            'email' => $tl['email'],
            'password' => $tl['password'],
            'phone' => $tl['phone'] ?? null,
            'role' => 'teamleader',
            'status' => 'active',
            'region_id' => (int) $tl['region_id'],
            'branch_id' => (int) $tl['branch_id'],
            'regional_manager_id' => (int) $tl['regional_manager_id'],
            'business_name' => $tl['business_name'] ?? null,
            'notes' => $tl['notes'] ?? null,
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
