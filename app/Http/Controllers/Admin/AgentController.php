<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentAssignment;
use App\Models\Branch;
use App\Models\SubadminRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortParams = User::resolveDirectorySort(
            $request->string('sort')->trim()->toString(),
            $request->string('direction')->trim()->toString(),
            'agent'
        );
        $agents = User::where('role', 'agent')
            ->directorySearch($search)
            ->directoryOrder($sortParams['sort'], $sortParams['direction'], 'agent')
            ->withLocationRelations()
            ->paginate(50)
            ->withQueryString();

        $teamLeaders = $this->teamLeadersForSelect();

        return view('admin.agents.index', compact('agents', 'teamLeaders', 'search') + $sortParams);
    }

    public function subadminsIndex(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortParams = User::resolveDirectorySort(
            $request->string('sort')->trim()->toString(),
            $request->string('direction')->trim()->toString(),
            'subadmin'
        );
        $subadmins = User::where('role', 'subadmin')
            ->directorySearch($search)
            ->directoryOrder($sortParams['sort'], $sortParams['direction'], 'subadmin')
            ->with('subadminRole')
            ->get();

        return view('admin.subadmins.index', compact('subadmins', 'search') + $sortParams);
    }

    public function show(User $agent)
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }
        $agent->load(['branch', 'teamLeader']);
        $assignments = AgentAssignment::where('agent_id', $agent->id)->with('product.category')->get();
        $teamLeaders = $this->teamLeadersForSelect();
        $branches = Branch::orderBy('name')->get();

        return view('admin.agents.show', compact('agent', 'assignments', 'teamLeaders', 'branches'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $teamLeaders = $this->teamLeadersForSelect();

        return view('admin.agents.create', compact('branches', 'teamLeaders'));
    }

    public function createSubadmin()
    {
        $roles = SubadminRole::orderBy('name')->get();

        return view('admin.subadmins.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
        ];

        if (Schema::hasColumn('users', 'team_leader_id')) {
            $rules['team_leader_id'] = [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'teamleader')->where('status', 'active');
                }),
            ];
        }

        $validated = $request->validate($rules);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'agent';
        if (Schema::hasColumn('users', 'ability')) {
            $validated['ability'] = 'fullaccess';
        }
        $validated['status'] = 'active';
        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = null;
        }

        if (Schema::hasColumn('users', 'team_leader_id')) {
            $tlId = $validated['team_leader_id'] ?? null;
            if ($tlId === '' || $tlId === null) {
                $validated['team_leader_id'] = null;
            } else {
                $validated['team_leader_id'] = (int) $tlId;
                $tl = User::query()->whereKey($validated['team_leader_id'])->where('role', 'teamleader')->first();
                if ($tl && $tl->branch_id && $validated['branch_id'] && (int) $tl->branch_id !== (int) $validated['branch_id']) {
                    return back()->withInput()->withErrors([
                        'team_leader_id' => 'Team leader must belong to the same branch you selected for this agent.',
                    ]);
                }
            }
        } else {
            unset($validated['team_leader_id']);
        }

        $user = User::create($validated);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.agents.index')->with('success', 'Agent created. They can log in and will see their dashboard.');
    }

    public function update(Request $request, User $agent)
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($agent->id)],
            'phone' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        if ($branchId === '') {
            $branchId = null;
        }

        if (Schema::hasColumn('users', 'team_leader_id') && $agent->team_leader_id) {
            $tl = User::query()->whereKey($agent->team_leader_id)->where('role', 'teamleader')->first();
            if ($tl && $tl->branch_id && $branchId && (int) $tl->branch_id !== (int) $branchId) {
                return back()->withInput()->withErrors([
                    'branch_id' => 'This agent’s team leader belongs to a different branch. Change the team leader below or pick the matching branch.',
                ]);
            }
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'branch_id' => $branchId,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $agent->update($payload);

        return redirect()->route('admin.agents.show', $agent)->with('success', 'Agent information updated.');
    }

    public function storeSubadmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
            'subadmin_role_id' => 'required|exists:subadmin_roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'subadmin';
        $validated['status'] = 'active';
        $validated['branch_id'] = null;

        $user = User::create($validated);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.subadmins.index')->with('success', 'Leader created successfully.');
    }

    public function updateTeamLeader(Request $request, User $agent)
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }

        if (! Schema::hasColumn('users', 'team_leader_id')) {
            return back()->withErrors(['error' => 'Run migrations to enable team leader assignment.']);
        }

        $validated = $request->validate([
            'team_leader_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'teamleader')->where('status', 'active');
                }),
            ],
        ]);

        $tlId = $validated['team_leader_id'] ?? null;
        if ($tlId === '' || $tlId === null) {
            $tlId = null;
        } else {
            $tlId = (int) $tlId;
            $tl = User::query()->whereKey($tlId)->where('role', 'teamleader')->first();
            if ($agent->branch_id && $tl && $tl->branch_id && (int) $agent->branch_id !== (int) $tl->branch_id) {
                return back()->withErrors([
                    'team_leader_id' => 'Team leader must belong to the same branch as this agent.',
                ])->withInput();
            }
        }

        $agent->update(['team_leader_id' => $tlId]);

        return back()->with('success', 'Team leader updated.');
    }

    public function transferBranch(Request $request, User $agent)
    {
        if ($agent->role !== 'agent') {
            abort(404);
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        if ($branchId === '') {
            $branchId = null;
        }

        $agent->update(['branch_id' => $branchId]);

        if (Schema::hasColumn('users', 'team_leader_id') && $agent->team_leader_id) {
            $tl = User::query()->whereKey($agent->team_leader_id)->where('role', 'teamleader')->first();
            if ($tl && $tl->branch_id && $branchId && (int) $tl->branch_id !== (int) $branchId) {
                $agent->update(['team_leader_id' => null]);

                return redirect()->route('admin.agents.index')
                    ->with('error', 'Branch changed to one that does not match this agent’s team leader; team leader was cleared. Assign a team leader again if needed.');
            }
        }

        return redirect()->route('admin.agents.index')->with('success', 'Agent transferred successfully.');
    }

    public function deactivate(User $user)
    {
        if (! in_array($user->role, ['agent', 'subadmin'], true)) {
            abort(404);
        }

        $user->update(['status' => 'inactive']);

        $targetRoute = $user->role === 'agent'
            ? 'admin.agents.index'
            : 'admin.subadmins.index';
        $label = $user->role === 'agent' ? 'Agent' : 'Leader';

        return redirect()->route($targetRoute)->with('success', $label.' deactivated successfully.');
    }

    public function activate(User $user)
    {
        if (! in_array($user->role, ['agent', 'subadmin'], true)) {
            abort(404);
        }

        $user->update(['status' => 'active']);

        $targetRoute = $user->role === 'agent'
            ? 'admin.agents.index'
            : 'admin.subadmins.index';
        $label = $user->role === 'agent' ? 'Agent' : 'Leader';

        return redirect()->route($targetRoute)->with('success', $label.' activated successfully.');
    }

    public function destroy(User $user)
    {
        if (! in_array($user->role, ['agent', 'subadmin'], true)) {
            abort(404);
        }

        if ((int) $user->id === (int) auth()->id()) {
            $targetRoute = $user->role === 'agent' ? 'admin.agents.index' : 'admin.subadmins.index';

            return redirect()->route($targetRoute)
                ->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $targetRoute = $user->role === 'agent' ? 'admin.agents.index' : 'admin.subadmins.index';
        $label = $user->role === 'agent' ? 'Agent' : 'Leader';

        try {
            $user->delete();
        } catch (QueryException $e) {
            return redirect()->route($targetRoute)
                ->withErrors(['error' => 'Cannot delete this '.strtolower($label).' because it is linked to existing records.']);
        }

        return redirect()->route($targetRoute)->with('success', $label.' deleted successfully.');
    }

    /**
     * @return Collection<int, User>
     */
    private function teamLeadersForSelect(): Collection
    {
        return User::query()
            ->where('role', 'teamleader')
            ->where('status', 'active')
            ->with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }
}
