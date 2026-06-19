<?php

namespace App\Support;

use App\Models\AgentDeviceReturn;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminHierarchyReturnList
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function collect(?string $status = null): Collection
    {
        $rows = collect();

        if (Schema::hasTable('agent_device_returns')) {
            $q = AgentDeviceReturn::query()
                ->with(['fromAgent', 'toTeamLeader', 'items'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $r) {
                $rows->push([
                    'kind' => 'agent_team_leader',
                    'id' => (int) $r->id,
                    'model' => $r,
                    'created_at' => $r->created_at,
                    'route_label' => 'Agent → Team leader',
                    'from_name' => $r->fromAgent?->name ?? '—',
                    'from_email' => $r->fromAgent?->email,
                    'from_role' => 'agent',
                    'to_name' => $r->toTeamLeader?->name ?? '—',
                    'to_email' => $r->toTeamLeader?->email,
                    'to_role' => 'teamleader',
                    'units' => $r->items->count(),
                    'status' => $r->status,
                    'message' => $r->message,
                    'recipient_note' => $r->recipient_note,
                    'can_admin_accept' => false,
                    'can_admin_decline' => false,
                    'show_url' => route('admin.stock.device-returns.show-agent-tl', $r),
                ]);
            }
        }

        if (Schema::hasTable('team_leader_device_returns')) {
            $q = TeamLeaderDeviceReturn::query()
                ->with(['fromTeamLeader', 'toRegionalManager', 'items'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $r) {
                $rows->push([
                    'kind' => 'team_leader_regional_manager',
                    'id' => (int) $r->id,
                    'model' => $r,
                    'created_at' => $r->created_at,
                    'route_label' => 'Team leader → Regional manager',
                    'from_name' => $r->fromTeamLeader?->name ?? '—',
                    'from_email' => $r->fromTeamLeader?->email,
                    'from_role' => 'teamleader',
                    'to_name' => $r->toRegionalManager?->name ?? '—',
                    'to_email' => $r->toRegionalManager?->email,
                    'to_role' => 'regional_manager',
                    'units' => $r->items->count(),
                    'status' => $r->status,
                    'message' => $r->message,
                    'recipient_note' => $r->recipient_note,
                    'can_admin_accept' => false,
                    'can_admin_decline' => false,
                    'show_url' => route('admin.stock.device-returns.show-tl-rm', $r),
                ]);
            }
        }

        if (Schema::hasTable('regional_manager_device_returns')) {
            $q = RegionalManagerDeviceReturn::query()
                ->with(['fromRegionalManager', 'items', 'decidedByUser'])
                ->latest();
            if ($status) {
                $q->where('status', $status);
            }
            foreach ($q->get() as $r) {
                $isPending = $r->status === RegionalManagerDeviceReturn::STATUS_PENDING;
                $rows->push([
                    'kind' => 'regional_manager_admin',
                    'id' => (int) $r->id,
                    'model' => $r,
                    'created_at' => $r->created_at,
                    'route_label' => 'Regional manager → Admin',
                    'from_name' => $r->fromRegionalManager?->name ?? '—',
                    'from_email' => $r->fromRegionalManager?->email,
                    'from_role' => 'regional_manager',
                    'to_name' => 'Admin stock',
                    'to_email' => null,
                    'to_role' => 'admin',
                    'units' => $r->items->count(),
                    'status' => $r->status,
                    'message' => $r->message,
                    'recipient_note' => $r->recipient_note,
                    'can_admin_accept' => $isPending,
                    'can_admin_decline' => $isPending,
                    'accept_url' => route('admin.stock.device-returns.accept', $r),
                    'decline_url' => route('admin.stock.device-returns.decline', $r),
                    'show_url' => route('admin.stock.device-returns.show-rm-admin', $r),
                ]);
            }
        }

        return $rows->sortByDesc(fn ($row) => $row['created_at']?->timestamp ?? 0)->values();
    }
}
