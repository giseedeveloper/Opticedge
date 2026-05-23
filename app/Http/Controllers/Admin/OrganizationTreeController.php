<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class OrganizationTreeController extends Controller
{
    public function index()
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
            ->get();

        $teamLeadersQuery = User::query()
            ->where('role', 'teamleader')
            ->when($with !== [], fn ($q) => $q->with($with))
            ->orderBy('name');

        if ($hasManagerLink) {
            $teamLeadersQuery->with('regionalManager:id,name');
        }

        $teamLeaders = $teamLeadersQuery->get();

        $agentsQuery = User::query()
            ->where('role', 'agent')
            ->when($with !== [], fn ($q) => $q->with($with))
            ->orderBy('name');

        if ($hasTeamLeaderLink) {
            $agentsQuery->with('teamLeader:id,name');
        }

        $agents = $agentsQuery->get();

        $teamLeadersByManager = $hasManagerLink
            ? $teamLeaders->groupBy('regional_manager_id')
            : collect();

        $agentsByTeamLeader = $hasTeamLeaderLink
            ? $agents->groupBy('team_leader_id')
            : collect();

        $unassignedTeamLeaders = $hasManagerLink
            ? $teamLeaders->filter(fn (User $tl) => $tl->regional_manager_id === null)->values()
            : $teamLeaders;

        $unassignedAgents = $hasTeamLeaderLink
            ? $agents->filter(fn (User $a) => $a->team_leader_id === null)->values()
            : $agents;

        $stats = [
            'regional_managers' => $regionalManagers->count(),
            'team_leaders' => $teamLeaders->count(),
            'agents' => $agents->count(),
            'unassigned_team_leaders' => $unassignedTeamLeaders->count(),
            'unassigned_agents' => $unassignedAgents->count(),
        ];

        return view('admin.organization-tree.index', compact(
            'regionalManagers',
            'teamLeadersByManager',
            'agentsByTeamLeader',
            'unassignedTeamLeaders',
            'unassignedAgents',
            'hasManagerLink',
            'hasTeamLeaderLink',
            'stats',
        ));
    }
}
