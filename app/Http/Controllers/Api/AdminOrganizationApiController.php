<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class AdminOrganizationApiController extends Controller
{
    public function index(): JsonResponse
    {
        $hasManagerLink = Schema::hasColumn('users', 'regional_manager_id');
        $hasTeamLeaderLink = Schema::hasColumn('users', 'team_leader_id');

        $with = [];
        if (Schema::hasColumn('users', 'region_id')) {
            $with[] = 'region:id,name';
        }
        if (Schema::hasColumn('users', 'branch_id')) {
            $with[] = 'branch:id,name';
        }

        $regionalManagers = User::query()
            ->where('role', 'regional_manager')
            ->when($with !== [], fn ($q) => $q->with($with))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'branch_id', 'region_id']);

        $teamLeaders = User::query()
            ->where('role', 'teamleader')
            ->when($with !== [], fn ($q) => $q->with($with))
            ->when($hasManagerLink, fn ($q) => $q->with('regionalManager:id,name'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'branch_id', 'regional_manager_id']);

        $agents = User::query()
            ->where('role', 'agent')
            ->when($with !== [], fn ($q) => $q->with($with))
            ->when($hasTeamLeaderLink, fn ($q) => $q->with('teamLeader:id,name'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'branch_id', 'team_leader_id']);

        $serializeUser = fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'status' => $u->status ?? 'active',
            'branch_name' => $u->branch?->name,
            'region_name' => $u->region?->name ?? null,
            'regional_manager_id' => $hasManagerLink ? $u->regional_manager_id : null,
            'regional_manager_name' => $hasManagerLink ? $u->regionalManager?->name : null,
            'team_leader_id' => $hasTeamLeaderLink ? $u->team_leader_id : null,
            'team_leader_name' => $hasTeamLeaderLink ? $u->teamLeader?->name : null,
        ];

        return response()->json([
            'data' => [
                'regional_managers' => $regionalManagers->map($serializeUser)->values(),
                'team_leaders' => $teamLeaders->map($serializeUser)->values(),
                'agents' => $agents->map($serializeUser)->values(),
            ],
            'stats' => [
                'regional_managers' => $regionalManagers->count(),
                'team_leaders' => $teamLeaders->count(),
                'agents' => $agents->count(),
                'unassigned_team_leaders' => $hasManagerLink
                    ? $teamLeaders->whereNull('regional_manager_id')->count()
                    : 0,
                'unassigned_agents' => $hasTeamLeaderLink
                    ? $agents->whereNull('team_leader_id')->count()
                    : 0,
            ],
        ]);
    }
}
