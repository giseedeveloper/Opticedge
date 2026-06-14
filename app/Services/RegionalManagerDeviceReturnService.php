<?php

namespace App\Services;

use App\Models\ProductListItem;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\RegionalManagerDeviceReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegionalManagerDeviceReturnService
{
    /**
     * @param  array<int, int>  $productListIds
     */
    public function createByRegionalManager(User $regionalManager, int $productId, array $productListIds, ?string $message = null): RegionalManagerDeviceReturn
    {
        if ($regionalManager->role !== 'regional_manager') {
            throw new \InvalidArgumentException('You are not a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($regionalManager, $productId, $ids, $message) {
            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleForReturn($item, $productId, (int) $regionalManager->id);
            }

            $return = RegionalManagerDeviceReturn::create([
                'from_regional_manager_id' => $regionalManager->id,
                'status' => RegionalManagerDeviceReturn::STATUS_PENDING,
                'message' => $message,
            ]);

            foreach ($ids as $listId) {
                RegionalManagerDeviceReturnItem::create([
                    'regional_manager_device_return_id' => $return->id,
                    'product_list_id' => $listId,
                ]);
            }

            $loaded = $return->load('items');
            app(NotificationDispatchService::class)->returnIncomingAdmins(
                $regionalManager->tenant_id,
                $regionalManager,
                (int) $loaded->id,
                $loaded->items->count(),
            );

            return $loaded;
        });
    }

    public function isProductListInAnyPendingReturn(int $productListId): bool
    {
        return RegionalManagerDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', fn ($q) => $q->where('status', RegionalManagerDeviceReturn::STATUS_PENDING))
            ->exists();
    }

    public function acceptByAdmin(RegionalManagerDeviceReturn $return, User $admin, ?string $note = null): void
    {
        if (! in_array($admin->role, ['admin', 'superadmin'], true)) {
            throw new \InvalidArgumentException('Only an admin can accept this return.');
        }
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }

        DB::transaction(function () use ($return, $admin, $note) {
            $return->load(['items', 'fromRegionalManager']);
            $ids = $return->items->pluck('product_list_id')->all();

            foreach ($ids as $listId) {
                if ($this->isProductListInAnyPendingReturnExcept($listId, $return->id)) {
                    throw new \InvalidArgumentException('One or more devices are locked by another pending return request.');
                }
            }

            app(DeviceHierarchyAssignmentService::class)->returnFromRegionalManagerToAdmin(
                $return->fromRegionalManager,
                $ids
            );

            $return->update([
                'status' => RegionalManagerDeviceReturn::STATUS_APPROVED,
                'recipient_note' => $note,
                'decided_at' => now(),
                'decided_by' => $admin->id,
            ]);
        });

        $requester = User::find($return->from_regional_manager_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnAccepted(
                $requester,
                $admin,
                (int) $return->id,
                'regional_manager_to_admin',
            );
        }
    }

    public function declineByAdmin(RegionalManagerDeviceReturn $return, User $admin, ?string $note = null): void
    {
        if (! in_array($admin->role, ['admin', 'superadmin'], true)) {
            throw new \InvalidArgumentException('Only an admin can decline this return.');
        }
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }

        $return->update([
            'status' => RegionalManagerDeviceReturn::STATUS_REJECTED,
            'recipient_note' => $note,
            'decided_at' => now(),
            'decided_by' => $admin->id,
        ]);

        $requester = User::find($return->from_regional_manager_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnDeclined(
                $requester,
                $admin,
                (int) $return->id,
                'regional_manager_to_admin',
            );
        }
    }

    public function cancelByRegionalManager(RegionalManagerDeviceReturn $return, User $regionalManager): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->from_regional_manager_id !== (int) $regionalManager->id) {
            throw new \InvalidArgumentException('Not your return request.');
        }

        $return->update([
            'status' => RegionalManagerDeviceReturn::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => $regionalManager->id,
        ]);

        app(NotificationDispatchService::class)->notifyTenantAdmins(
            $regionalManager->tenant_id,
            \App\Support\NotificationType::RETURN_CANCELLED,
            'Return cancelled',
            "{$regionalManager->name} cancelled a return request to admin.",
            [
                'entity_type' => 'return',
                'entity_id' => $return->id,
                'meta' => ['scope' => 'regional_manager_to_admin'],
            ],
        );
    }

    private function isProductListInAnyPendingReturnExcept(int $productListId, int $exceptReturnId): bool
    {
        return RegionalManagerDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', function ($q) use ($exceptReturnId) {
                $q->where('status', RegionalManagerDeviceReturn::STATUS_PENDING)
                    ->where('id', '!=', $exceptReturnId);
            })
            ->exists();
    }

    private function assertEligibleForReturn(?ProductListItem $item, int $productId, int $regionalManagerId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        if ($item->teamLeaderProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are still with a team leader.');
        }
        if ($item->agentProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are still with an agent.');
        }
        $rmAssign = $item->regionalManagerProductListAssignment;
        if (! $rmAssign || (int) $rmAssign->regional_manager_id !== $regionalManagerId) {
            throw new \InvalidArgumentException('One or more devices are not assigned to you.');
        }
        if ($this->isProductListInAnyPendingReturn($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending return request.');
        }
    }
}
