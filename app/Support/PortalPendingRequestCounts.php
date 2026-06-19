<?php

namespace App\Support;

use App\Models\AgentDeviceReturn;
use App\Models\AgentProductTransfer;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\RegionalManagerProductTransfer;
use App\Models\TeamLeaderDeviceReturn;
use App\Models\TeamLeaderProductTransfer;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class PortalPendingRequestCounts
{
    /**
     * @return array{pending_transfer_requests: int, pending_return_requests: int}
     */
    public static function forUser(?User $user): array
    {
        $empty = [
            'pending_transfer_requests' => 0,
            'pending_return_requests' => 0,
        ];

        if (! $user) {
            return $empty;
        }

        $id = (int) $user->id;

        try {
            return match ($user->role) {
                'teamleader' => [
                    'pending_transfer_requests' => self::tableExists('team_leader_product_transfers')
                        ? TeamLeaderProductTransfer::query()
                            ->where('to_team_leader_id', $id)
                            ->where('status', TeamLeaderProductTransfer::STATUS_PENDING)
                            ->count()
                        : 0,
                    'pending_return_requests' => self::tableExists('agent_device_returns')
                        ? AgentDeviceReturn::query()
                            ->where('to_team_leader_id', $id)
                            ->where('status', AgentDeviceReturn::STATUS_PENDING)
                            ->count()
                        : 0,
                ],
                'regional_manager' => [
                    'pending_transfer_requests' => self::tableExists('regional_manager_product_transfers')
                        ? RegionalManagerProductTransfer::query()
                            ->where('to_regional_manager_id', $id)
                            ->where('status', RegionalManagerProductTransfer::STATUS_PENDING)
                            ->count()
                        : 0,
                    'pending_return_requests' => self::tableExists('team_leader_device_returns')
                        ? TeamLeaderDeviceReturn::query()
                            ->where('to_regional_manager_id', $id)
                            ->where('status', TeamLeaderDeviceReturn::STATUS_PENDING)
                            ->count()
                        : 0,
                ],
                'agent' => [
                    'pending_transfer_requests' => AgentProductTransfer::query()
                        ->where('to_agent_id', $id)
                        ->where('status', AgentProductTransfer::STATUS_PENDING)
                        ->count(),
                    'pending_return_requests' => self::tableExists('agent_device_returns')
                        ? AgentDeviceReturn::query()
                            ->where('from_agent_id', $id)
                            ->where('status', AgentDeviceReturn::STATUS_PENDING)
                            ->count()
                        : 0,
                ],
                'admin', 'superadmin' => [
                    'pending_transfer_requests' => self::countPendingTransfersForAdmin(),
                    'pending_return_requests' => self::countPendingReturnsForAdmin(),
                ],
                default => $empty,
            };
        } catch (\Throwable) {
            return $empty;
        }
    }

    private static function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function countPendingTransfersForAdmin(): int
    {
        $total = 0;

        if (self::tableExists('regional_manager_product_transfers')) {
            $total += RegionalManagerProductTransfer::query()
                ->where('status', RegionalManagerProductTransfer::STATUS_PENDING)
                ->count();
        }
        if (self::tableExists('team_leader_product_transfers')) {
            $total += TeamLeaderProductTransfer::query()
                ->where('status', TeamLeaderProductTransfer::STATUS_PENDING)
                ->count();
        }
        if (self::tableExists('agent_product_transfers')) {
            $total += AgentProductTransfer::query()
                ->where('status', AgentProductTransfer::STATUS_PENDING)
                ->count();
        }

        return $total;
    }

    private static function countPendingReturnsForAdmin(): int
    {
        $total = 0;

        if (self::tableExists('agent_device_returns')) {
            $total += AgentDeviceReturn::query()
                ->where('status', AgentDeviceReturn::STATUS_PENDING)
                ->count();
        }
        if (self::tableExists('team_leader_device_returns')) {
            $total += TeamLeaderDeviceReturn::query()
                ->where('status', TeamLeaderDeviceReturn::STATUS_PENDING)
                ->count();
        }
        if (self::tableExists('regional_manager_device_returns')) {
            $total += RegionalManagerDeviceReturn::query()
                ->where('status', RegionalManagerDeviceReturn::STATUS_PENDING)
                ->count();
        }

        return $total;
    }
}
