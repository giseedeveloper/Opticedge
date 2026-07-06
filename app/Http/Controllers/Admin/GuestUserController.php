<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\GuestVendorInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class GuestUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $guests = User::withoutGlobalScopes()
            ->where('role', 'guest')
            ->whereNull('tenant_id')
            ->when($search !== '', fn ($q) => $q->directorySearch($search))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.guest-users.index', compact('guests', 'search'));
    }

    public function assignForm(int $guestUser): View
    {
        $guest = $this->findGuest($guestUser);

        $branches = Schema::hasTable('branches')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        $regions = Schema::hasTable('regions')
            ? DB::table('regions')->orderBy('name')->get(['id', 'name'])
            : collect();

        $teamLeaders = User::where('role', 'teamleader')->orderBy('name')->get(['id', 'name', 'branch_id']);
        $regionalManagers = User::where('role', 'regional_manager')
            ->where('status', 'active')
            ->when(Schema::hasColumn('users', 'region_id'), fn ($q) => $q->whereNotNull('region_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        return view('admin.guest-users.assign', compact('guest', 'branches', 'regions', 'teamLeaders', 'regionalManagers'));
    }

    public function assign(Request $request, int $guestUser, GuestVendorInvitationService $invitations): RedirectResponse
    {
        $guest = $this->findGuest($guestUser);
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;

        if ($tenantId === null) {
            return back()->withErrors(['error' => 'Your admin account is not linked to a vendor.']);
        }

        $role = $request->input('role', 'agent');
        $rules = [
            'role' => ['required', Rule::in(['agent', 'teamleader', 'regional_manager'])],
            'phone' => 'nullable|string|max:100',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
            'message' => 'nullable|string|max:2000',
        ];

        if (in_array($role, ['agent', 'teamleader', 'regional_manager'], true)) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
        }
        if ($role === 'agent' && Schema::hasColumn('users', 'team_leader_id')) {
            $rules['team_leader_id'] = 'nullable|integer';
        }
        if ($role === 'regional_manager' && Schema::hasColumn('users', 'region_id')) {
            $rules['region_id'] = 'required|exists:regions,id';
        }
        if ($role === 'teamleader') {
            if (Schema::hasColumn('users', 'region_id')) {
                $rules['region_id'] = 'required|exists:regions,id';
            }
            if (Schema::hasColumn('users', 'branch_id')) {
                $rules['branch_id'] = 'required|exists:branches,id';
            }
            if (Schema::hasColumn('users', 'regional_manager_id')) {
                $rules['regional_manager_id'] = 'required|integer';
            }
        }

        $validated = $request->validate($rules);
        $payload = $invitations->buildAssignmentPayload($role, $validated, $guest);

        $invitations->sendInvitation(
            $guest,
            $admin,
            (int) $tenantId,
            $role,
            $payload,
            $validated['message'] ?? null,
        );

        return redirect()
            ->route('admin.guest-users.index')
            ->with('success', "Invitation sent to {$guest->name}. They must accept before joining your vendor.");
    }

    private function findGuest(int $id): User
    {
        $user = User::withoutGlobalScopes()
            ->whereKey($id)
            ->where('role', 'guest')
            ->whereNull('tenant_id')
            ->first();

        if (! $user) {
            abort(404);
        }

        return $user;
    }
}
