<?php

namespace App\Support;

use App\Models\AgentProductTransfer;
use App\Models\RegionalManagerProductTransfer;
use App\Models\TeamLeaderProductTransfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminHierarchyTransferList
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function collect(?string $status = null): Collection
    {
        $rows = collect();

        if (Schema::hasTable('regional_manager_product_transfers')) {
            $q = RegionalManagerProductTransfer::query()
                ->with(['createdByAdmin', 'toRegionalManager', 'items'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $t) {
                $rows->push([
                    'kind' => 'admin_regional_manager',
                    'id' => (int) $t->id,
                    'created_at' => $t->created_at,
                    'route_label' => 'Admin → Regional manager',
                    'from_name' => $t->createdByAdmin?->name ?? 'Admin',
                    'from_email' => $t->createdByAdmin?->email,
                    'from_role' => $t->createdByAdmin?->role ?? 'admin',
                    'to_name' => $t->toRegionalManager?->name ?? '—',
                    'to_email' => $t->toRegionalManager?->email,
                    'to_role' => 'regional_manager',
                    'units' => $t->items->count(),
                    'status' => $t->status,
                    'message' => $t->message,
                    'show_url' => route('admin.stock.device-transfers.show-admin-rm', $t),
                ]);
            }
        }

        if (Schema::hasTable('team_leader_product_transfers')) {
            $q = TeamLeaderProductTransfer::query()
                ->with(['fromRegionalManager', 'toTeamLeader', 'items'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $t) {
                $rows->push([
                    'kind' => 'regional_manager_team_leader',
                    'id' => (int) $t->id,
                    'created_at' => $t->created_at,
                    'route_label' => 'Regional manager → Team leader',
                    'from_name' => $t->fromRegionalManager?->name ?? '—',
                    'from_email' => $t->fromRegionalManager?->email,
                    'from_role' => 'regional_manager',
                    'to_name' => $t->toTeamLeader?->name ?? '—',
                    'to_email' => $t->toTeamLeader?->email,
                    'to_role' => 'teamleader',
                    'units' => $t->items->count(),
                    'status' => $t->status,
                    'message' => $t->message,
                    'show_url' => route('admin.stock.device-transfers.show-rm-tl', $t),
                ]);
            }
        }

        if (Schema::hasTable('agent_product_transfers')) {
            $q = AgentProductTransfer::query()
                ->with(['fromAgent', 'toAgent', 'items'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $t) {
                $rows->push([
                    'kind' => 'agent_agent',
                    'id' => (int) $t->id,
                    'created_at' => $t->created_at,
                    'route_label' => 'Agent → Agent',
                    'from_name' => $t->fromAgent?->name ?? '—',
                    'from_email' => $t->fromAgent?->email,
                    'from_role' => 'agent',
                    'to_name' => $t->toAgent?->name ?? '—',
                    'to_email' => $t->toAgent?->email,
                    'to_role' => 'agent',
                    'units' => $t->items->count(),
                    'status' => $t->status,
                    'message' => $t->message,
                    'show_url' => route('admin.stock.agent-transfers.show', $t),
                ]);
            }
        }

        return $rows->sortByDesc(fn ($row) => $row['created_at']?->timestamp ?? 0)->values();
    }
}
