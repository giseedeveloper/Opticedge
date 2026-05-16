<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentProductAssignmentService
{
    /**
     * Assign specific IMEIs (product_list rows) to an agent.
     *
     * @param  array<int, int>  $productListIds
     * @return int number of devices assigned
     *
     * @throws \InvalidArgumentException
     */
    public function assignToAgent(User $agent, int $productId, array $productListIds): int
    {
        if ($agent->role !== 'agent') {
            throw new \InvalidArgumentException('Selected user is not an agent.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));

        return DB::transaction(function () use ($agent, $productId, $ids) {
            $added = 0;

            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);

                if (! $item || ! $item->isCatalogProduct($productId)) {
                    throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
                }

                if ($item->isSold()) {
                    throw new \InvalidArgumentException('One or more devices are already sold.');
                }

                if (! $item->isPurchasePaid()) {
                    throw new \InvalidArgumentException('One or more devices are not from an eligible purchase (paid, partial, unpaid, or purchase still has IMEI limit remaining).');
                }

                if ($item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already assigned to an agent.');
                }

                AgentProductListAssignment::create([
                    'agent_id' => $agent->id,
                    'product_list_id' => $item->id,
                ]);
                $added++;
            }

            if ($added === 0) {
                throw new \InvalidArgumentException('No devices were assigned.');
            }

            $assignment = AgentAssignment::firstOrNew([
                'agent_id' => $agent->id,
                'product_id' => $productId,
                'assignment_type' => AgentAssignment::TYPE_IMEI,
            ]);
            $assignment->quantity_assigned = (int) ($assignment->quantity_assigned ?? 0) + $added;
            $assignment->save();

            return $added;
        });
    }

    /**
     * Assign a product to an agent by total quantity (no specific IMEIs locked).
     * Multiple admin actions on the same agent+product accumulate into the same row.
     *
     * @return int new total quantity_assigned for the (agent, product, total) row
     *
     * @throws \InvalidArgumentException
     */
    public function assignTotalToAgent(User $agent, int $productId, int $quantity, ?int $purchaseId = null): int
    {
        if ($agent->role !== 'agent') {
            throw new \InvalidArgumentException('Selected user is not an agent.');
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! Product::query()->whereKey($productId)->exists()) {
            throw new \InvalidArgumentException('Selected product does not exist.');
        }

        return DB::transaction(function () use ($agent, $productId, $quantity, $purchaseId) {
            $purchase = null;
                if ($purchaseId !== null) {
                $purchase = Purchase::query()
                    ->with(['product', 'lines'])
                    ->find($purchaseId);
                if (! $purchase) {
                    throw new \InvalidArgumentException('Selected purchase does not exist.');
                }
                $matches = (int) $purchase->product_id === $productId;
                if ($purchase->lines()->exists()) {
                    $matches = $purchase->lines()->where('product_id', $productId)->exists();
                }
                if (! $matches) {
                    throw new \InvalidArgumentException('Selected purchase does not match the selected model.');
                }
            }

            $assignment = AgentAssignment::firstOrNew([
                'agent_id' => $agent->id,
                'product_id' => $productId,
                'assignment_type' => AgentAssignment::TYPE_TOTAL,
                'purchase_id' => $purchase?->id,
            ]);
            $assignment->quantity_assigned = (int) ($assignment->quantity_assigned ?? 0) + $quantity;
            $assignment->quantity_sold = (int) ($assignment->quantity_sold ?? 0);
            $assignment->save();

            return (int) $assignment->quantity_assigned;
        });
    }
}
