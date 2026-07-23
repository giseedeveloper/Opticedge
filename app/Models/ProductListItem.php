<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductListItem extends Model
{
    use BelongsToTenantStrict;

    protected $table = 'product_list';

    protected $fillable = [
        'stock_id',
        'purchase_id',
        'branch_id',
        'tenant_id',
        'category_id',
        'model',
        'imei_number',
        'product_id',
        'sold_at',
        'agent_sale_id',
        'distribution_sale_id',
        'pending_sale_id',
        'agent_credit_id',
    ];

    public function purchase()
    {
        // Include soft-deleted purchases so sold/history rows keep their purchase info.
        return $this->belongsTo(Purchase::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Filter rows whose effective branch matches $branchId (row branch or purchase branch).
     * When $branchId is null, no filter is applied.
     */
    public function scopeWhereEffectiveBranch($query, ?int $branchId)
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhere(function ($inner) use ($branchId) {
                    $inner->whereNull('branch_id')
                        ->whereHas('purchase', fn ($p) => $p->where('branch_id', $branchId));
                });
        });
    }

    /**
     * Location branch: explicit on row, else from linked purchase.
     */
    public function effectiveBranchId(): ?int
    {
        if ($this->branch_id !== null) {
            return (int) $this->branch_id;
        }
        $this->loadMissing('purchase');

        return $this->purchase?->branch_id !== null ? (int) $this->purchase->branch_id : null;
    }

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function agentSale()
    {
        return $this->belongsTo(AgentSale::class, 'agent_sale_id');
    }

    public function distributionSale()
    {
        return $this->belongsTo(DistributionSale::class, 'distribution_sale_id');
    }

    /**
     * Unsold warehouse devices for a catalog product (not assigned to agents, not on credit/pending).
     */
    public function scopeAvailableForDistribution($query, int $productId)
    {
        return $query
            ->matchingCatalogProduct($productId)
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->when(
                Schema::hasColumn('product_list', 'distribution_sale_id'),
                fn ($q) => $q->whereNull('distribution_sale_id')
            )
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereDoesntHave('agentProductListAssignment')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment');
    }

    /**
     * Limit distribution IMEIs to a specific purchase row.
     */
    public function scopeFromPurchase($query, int $purchaseId)
    {
        return $query->where('purchase_id', $purchaseId);
    }

    public function pendingSale()
    {
        return $this->belongsTo(PendingSale::class, 'pending_sale_id');
    }

    public function agentCredit()
    {
        return $this->belongsTo(AgentCredit::class, 'agent_credit_id');
    }

    public function agentProductListAssignment()
    {
        return $this->hasOne(AgentProductListAssignment::class, 'product_list_id');
    }

    public function agentProductTransferItems()
    {
        return $this->hasMany(AgentProductTransferItem::class, 'product_list_id');
    }

    public function regionalManagerProductListAssignment()
    {
        return $this->hasOne(RegionalManagerProductListAssignment::class, 'product_list_id');
    }

    public function regionalManagerProductTransferItems()
    {
        return $this->hasMany(RegionalManagerProductTransferItem::class, 'product_list_id');
    }

    public function teamLeaderProductTransferItems()
    {
        return $this->hasMany(TeamLeaderProductTransferItem::class, 'product_list_id');
    }

    public function teamLeaderProductListAssignment()
    {
        return $this->hasOne(TeamLeaderProductListAssignment::class, 'product_list_id');
    }

    public function agentDeviceReturnItems()
    {
        return $this->hasMany(AgentDeviceReturnItem::class, 'product_list_id');
    }

    public function teamLeaderDeviceReturnItems()
    {
        return $this->hasMany(TeamLeaderDeviceReturnItem::class, 'product_list_id');
    }

    public function regionalManagerDeviceReturnItems()
    {
        return $this->hasMany(RegionalManagerDeviceReturnItem::class, 'product_list_id');
    }

    /**
     * Whether this row is the given catalog product (by id on row or on linked purchase).
     */
    public function isCatalogProduct(int $productId): bool
    {
        if ((int) $this->product_id === $productId) {
            return true;
        }
        $this->loadMissing('purchase.lines');

        if ($this->purchase === null) {
            return false;
        }

        if ((int) $this->purchase->product_id === $productId) {
            return true;
        }

        return $this->purchase->lines->contains(fn ($line) => (int) $line->product_id === $productId);
    }

    /**
     * Purchase row is allowed for agent IMEI assignment: paid, partial, unpaid (pending),
     * or quantity cap not yet reached (more IMEIs can still be added).
     */
    public static function purchaseQualifiesForAgentAssignment(?Purchase $purchase): bool
    {
        if ($purchase === null || $purchase->isPassthrough()) {
            return false;
        }

        $status = (string) ($purchase->payment_status ?? '');

        if (in_array($status, ['paid', 'partial', 'pending'], true)) {
            return true;
        }

        if ((int) ($purchase->limit_remaining ?? 0) > 0) {
            return true;
        }

        // Fully registered purchase — IMEIs are in warehouse even when limit_remaining is 0.
        if (($purchase->limit_status ?? '') === 'complete') {
            return true;
        }

        return false;
    }

    /**
     * IMEI rows linked to a purchase invoice and/or its stock bucket.
     */
    public function scopeOnPurchaseStock($query, int $purchaseId)
    {
        $purchase = Purchase::query()->find($purchaseId);
        $stockId = $purchase?->stock_id ? (int) $purchase->stock_id : null;

        return $query->where(function ($q) use ($purchaseId, $stockId) {
            $q->where('purchase_id', $purchaseId);
            if ($stockId !== null) {
                $q->orWhere(function ($q2) use ($stockId, $purchaseId) {
                    $q2->where('stock_id', $stockId)
                        ->where(function ($q3) use ($purchaseId) {
                            $q3->whereNull('purchase_id')->orWhere('purchase_id', $purchaseId);
                        });
                });
            }
        });
    }

    /**
     * Match catalog product on the row or on the linked purchase (header or line items).
     */
    public function scopeMatchingCatalogProduct($query, int $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->where('product_id', $productId)
                ->orWhereHas('purchase', function ($p) use ($productId) {
                    $p->where('product_id', $productId)
                        ->orWhereHas('lines', fn ($l) => $l->where('product_id', $productId));
                });
        });
    }

    /**
     * @return array{code: string, label: string, selectable: bool}
     */
    public function custodyStatusForAdminAssign(): array
    {
        if (Schema::hasColumn('product_list', 'distribution_sale_id') && $this->distribution_sale_id) {
            $this->loadMissing('distributionSale');
            $dealer = $this->distributionSale?->dealer_name ?: 'Dealer';

            return ['code' => 'distribution', 'label' => 'In distribution · '.$dealer, 'selectable' => false];
        }

        if ($this->sold_at !== null) {
            return ['code' => 'sold', 'label' => 'Sold', 'selectable' => false];
        }

        if ($this->agent_sale_id) {
            return ['code' => 'agent_sale', 'label' => 'Agent sale', 'selectable' => false];
        }

        if ($this->pending_sale_id) {
            return ['code' => 'pending_sale', 'label' => 'Pending sale', 'selectable' => false];
        }

        if ($this->agent_credit_id) {
            return ['code' => 'agent_credit', 'label' => 'Agent credit', 'selectable' => false];
        }

        if ($this->regionalManagerProductListAssignment) {
            $this->loadMissing('regionalManagerProductListAssignment.regionalManager');
            $name = $this->regionalManagerProductListAssignment?->regionalManager?->name ?? 'Regional manager';

            return ['code' => 'regional_manager', 'label' => 'With '.$name, 'selectable' => false];
        }

        if ($this->teamLeaderProductListAssignment) {
            $this->loadMissing('teamLeaderProductListAssignment.teamLeader');
            $name = $this->teamLeaderProductListAssignment?->teamLeader?->name ?? 'Team leader';

            return ['code' => 'team_leader', 'label' => 'With '.$name, 'selectable' => false];
        }

        if ($this->agentProductListAssignment) {
            $this->loadMissing('agentProductListAssignment.agent');
            $name = $this->agentProductListAssignment?->agent?->name ?? 'Agent';

            return ['code' => 'agent', 'label' => 'With agent · '.$name, 'selectable' => false];
        }

        if ($this->regionalManagerProductTransferItems()
            ->whereHas('transfer', fn ($q) => $q->where('status', RegionalManagerProductTransfer::STATUS_PENDING))
            ->exists()) {
            return ['code' => 'pending_transfer', 'label' => 'Pending transfer request', 'selectable' => false];
        }

        return ['code' => 'available', 'label' => 'Available', 'selectable' => true];
    }

    /**
     * Filter unsold devices to those currently held by a hierarchy role. The holder is
     * the deepest assignment level a device has (agent > team leader > regional manager);
     * with no assignment it is still in the admin warehouse. Mirrors the counts in
     * {@see \App\Services\StockSummaryInsightsService::inventoryByRole()}.
     *
     * @param  'admin'|'regional_manager'|'team_leader'|'agent'  $role
     */
    public function scopeHeldByRole($query, string $role)
    {
        $query->whereNull('sold_at');

        return match ($role) {
            'admin' => $query
                ->whereDoesntHave('regionalManagerProductListAssignment')
                ->whereDoesntHave('teamLeaderProductListAssignment')
                ->whereDoesntHave('agentProductListAssignment'),
            'regional_manager' => $query
                ->whereHas('regionalManagerProductListAssignment')
                ->whereDoesntHave('teamLeaderProductListAssignment')
                ->whereDoesntHave('agentProductListAssignment'),
            'team_leader' => $query
                ->whereHas('teamLeaderProductListAssignment')
                ->whereDoesntHave('agentProductListAssignment'),
            'agent' => $query
                ->whereHas('agentProductListAssignment'),
            default => $query,
        };
    }

    /**
     * Current hierarchy holder for display, using the same deepest-wins rule as
     * {@see scopeHeldByRole()}. Returns a null role for sold devices.
     *
     * @return array{role: ?string, label: string}
     */
    public function currentHolder(): array
    {
        if ($this->sold_at !== null) {
            return ['role' => null, 'label' => 'Sold'];
        }

        if ($this->agentProductListAssignment) {
            $this->loadMissing('agentProductListAssignment.agent');

            return ['role' => 'agent', 'label' => $this->agentProductListAssignment?->agent?->name ?? 'Agent'];
        }

        if ($this->teamLeaderProductListAssignment) {
            $this->loadMissing('teamLeaderProductListAssignment.teamLeader');

            return ['role' => 'team_leader', 'label' => $this->teamLeaderProductListAssignment?->teamLeader?->name ?? 'Team leader'];
        }

        if ($this->regionalManagerProductListAssignment) {
            $this->loadMissing('regionalManagerProductListAssignment.regionalManager');

            return ['role' => 'regional_manager', 'label' => $this->regionalManagerProductListAssignment?->regionalManager?->name ?? 'Regional manager'];
        }

        return ['role' => 'admin', 'label' => 'Admin warehouse'];
    }

    /**
     * Unsold warehouse devices on a specific purchase (and its stock), ready for admin hierarchy assignment.
     */
    public function scopeAssignableFromAdminOnPurchase($query, int $purchaseId, ?int $productId = null)
    {
        $query = $query->onPurchaseStock($purchaseId)
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->when(
                Schema::hasColumn('product_list', 'distribution_sale_id'),
                fn ($q) => $q->whereNull('distribution_sale_id')
            )
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->whereDoesntHave('regionalManagerProductTransferItems', function ($q) {
                $q->whereHas('transfer', fn ($t) => $t->where('status', RegionalManagerProductTransfer::STATUS_PENDING));
            });

        if ($productId !== null) {
            $query->matchingCatalogProduct($productId);
        }

        return $query;
    }

    /**
     * Unsold IMEIs in admin warehouse (not in hierarchy), from an eligible purchase.
     */
    public function scopeAssignableFromAdmin($query, int $productId)
    {
        return static::applyEligiblePurchaseScope(
            $query->where(function ($q) use ($productId) {
                $q->where('product_id', $productId)
                    ->orWhereHas('purchase', fn ($p) => $p->where('product_id', $productId));
            })
                ->whereNull('sold_at')
                ->whereDoesntHave('regionalManagerProductListAssignment')
                ->whereDoesntHave('teamLeaderProductListAssignment')
                ->whereDoesntHave('agentProductListAssignment'),
            $productId
        );
    }

    /** @deprecated Use assignableFromAdmin for admin pool */
    public function scopeAssignableToAgent($query, int $productId)
    {
        return $query->assignableFromAdmin($productId);
    }

    /**
     * Devices held by a regional manager (from admin), not yet with team leader or agent.
     */
    public function scopeInRegionalManagerCustodyForTeamLeaderAssignment($query, int $regionalManagerId)
    {
        return $query
            ->whereNull('sold_at')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->whereDoesntHave('teamLeaderProductTransferItems', function ($q) {
                $q->whereHas('transfer', fn ($t) => $t->where('status', TeamLeaderProductTransfer::STATUS_PENDING));
            })
            ->whereHas('regionalManagerProductListAssignment', fn ($q) => $q->where('regional_manager_id', $regionalManagerId));
    }

    /**
     * Devices held by a regional manager (from admin), not yet with team leader or agent.
     */
    public function scopeAssignableToTeamLeaderByRegionalManager($query, int $productId, int $regionalManagerId)
    {
        return $query
            ->inRegionalManagerCustodyForTeamLeaderAssignment($regionalManagerId)
            ->matchingCatalogProduct($productId);
    }

    /**
     * Catalog product ids for models the regional manager currently holds (admin-assigned, not yet with TL/agent).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function catalogProductIdsInRegionalManagerCustody(int $regionalManagerId): \Illuminate\Support\Collection
    {
        $items = static::query()
            ->inRegionalManagerCustodyForTeamLeaderAssignment($regionalManagerId)
            ->with(['purchase.lines'])
            ->get(['id', 'product_id', 'purchase_id']);

        return static::catalogProductIdsFromLoadedItems($items);
    }

    /**
     * Devices held by a team leader (from regional manager), not yet with an agent.
     */
    public function scopeInTeamLeaderCustodyForAgentAssignment($query, int $teamLeaderId)
    {
        return $query
            ->whereNull('sold_at')
            ->whereDoesntHave('agentProductListAssignment')
            ->whereHas('teamLeaderProductListAssignment', fn ($q) => $q->where('team_leader_id', $teamLeaderId));
    }

    /**
     * Devices held by a team leader (from regional manager), not yet with an agent.
     */
    public function scopeAssignableToAgentByTeamLeader($query, int $productId, int $teamLeaderId)
    {
        return $query
            ->inTeamLeaderCustodyForAgentAssignment($teamLeaderId)
            ->matchingCatalogProduct($productId);
    }

    /**
     * Catalog product ids for models the team leader currently holds (from RM, not yet with an agent).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function catalogProductIdsInTeamLeaderCustody(int $teamLeaderId): \Illuminate\Support\Collection
    {
        $items = static::query()
            ->inTeamLeaderCustodyForAgentAssignment($teamLeaderId)
            ->with(['purchase.lines'])
            ->get(['id', 'product_id', 'purchase_id']);

        return static::catalogProductIdsFromLoadedItems($items);
    }

    /**
     * Devices assigned to an agent that can be returned to their team leader.
     */
    public function scopeInAgentCustodyForReturnToTeamLeader($query, int $agentId)
    {
        return $query
            ->whereNull('sold_at')
            ->whereHas('agentProductListAssignment', fn ($q) => $q->where('agent_id', $agentId));
    }

    /**
     * Devices with an agent that can be returned to their team leader.
     */
    public function scopeReturnableByAgent($query, int $productId, int $agentId)
    {
        return $query
            ->inAgentCustodyForReturnToTeamLeader($agentId)
            ->matchingCatalogProduct($productId)
            ->whereDoesntHave('agentDeviceReturnItems', function ($q) {
                $q->whereHas('returnRequest', fn ($r) => $r->where('status', AgentDeviceReturn::STATUS_PENDING));
            });
    }

    /**
     * Catalog product ids for models the agent currently holds (unsold, returnable to team leader).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function catalogProductIdsReturnableByAgent(int $agentId): \Illuminate\Support\Collection
    {
        $items = static::query()
            ->inAgentCustodyForReturnToTeamLeader($agentId)
            ->with(['purchase.lines'])
            ->get(['id', 'product_id', 'purchase_id']);

        return static::catalogProductIdsFromLoadedItems($items);
    }

    /**
     * Devices assigned to an agent that can be sent in a transfer request (not in a pending transfer).
     */
    public function scopeTransferableByAgent($query, int $productId, int $agentId)
    {
        return $query
            ->returnableByAgent($productId, $agentId)
            ->whereDoesntHave('agentProductTransferItems', function ($q) {
                $q->whereHas('transfer', fn ($t) => $t->where('status', AgentProductTransfer::STATUS_PENDING));
            });
    }

    /**
     * Devices with a team leader (not with agent) that can be returned to regional manager.
     */
    public function scopeReturnableByTeamLeader($query, int $productId, int $teamLeaderId)
    {
        return $query
            ->inTeamLeaderCustodyForAgentAssignment($teamLeaderId)
            ->matchingCatalogProduct($productId)
            ->whereDoesntHave('teamLeaderDeviceReturnItems', function ($q) {
                $q->whereHas('returnRequest', fn ($r) => $r->where('status', TeamLeaderDeviceReturn::STATUS_PENDING));
            });
    }

    /**
     * Devices with regional manager only (not with TL or agent) that can be returned to admin.
     */
    public function scopeReturnableByRegionalManager($query, int $productId, int $regionalManagerId)
    {
        return $query
            ->inRegionalManagerCustodyForTeamLeaderAssignment($regionalManagerId)
            ->matchingCatalogProduct($productId)
            ->whereDoesntHave('regionalManagerDeviceReturnItems', function ($q) {
                $q->whereHas('returnRequest', fn ($r) => $r->where('status', RegionalManagerDeviceReturn::STATUS_PENDING));
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, self>  $items
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected static function catalogProductIdsFromLoadedItems(\Illuminate\Support\Collection $items): \Illuminate\Support\Collection
    {
        return $items->flatMap(function (self $item) {
            $ids = collect();
            if ($item->product_id !== null) {
                $ids->push((int) $item->product_id);
            }
            $purchase = $item->purchase;
            if ($purchase !== null) {
                if ($purchase->product_id !== null) {
                    $ids->push((int) $purchase->product_id);
                }
                foreach ($purchase->lines as $line) {
                    $ids->push((int) $line->product_id);
                }
            }

            return $ids;
        })->filter(fn (int $id) => $id > 0)->unique()->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyEligiblePurchaseScope($query, int $productId)
    {
        $purchaseOk = function ($p) {
            $p->where('is_passthrough', false)
                ->where(function ($p2) {
                    $p2->whereIn('payment_status', ['paid', 'partial', 'pending'])
                        ->orWhere('limit_remaining', '>', 0);
                });
        };

        return $query->where(function ($q) use ($purchaseOk) {
            $q->whereHas('purchase', $purchaseOk)
                ->orWhere(function ($q2) use ($purchaseOk) {
                    $q2->whereNull('purchase_id')
                        ->whereNotNull('product_list.stock_id')
                        ->whereExists(function ($sub) use ($purchaseOk) {
                            $sub->selectRaw('1')
                                ->from('purchases')
                                ->whereColumn('purchases.stock_id', 'product_list.stock_id')
                                ->whereColumn('purchases.product_id', 'product_list.product_id')
                                ->whereNull('purchases.deleted_at')
                                ->where($purchaseOk);
                        });
                });
        });
    }

    /**
     * Whether the linked purchase (or stock+product purchase) allows agent assignment.
     */
    public function isPurchasePaid(): bool
    {
        if ($this->purchase_id) {
            $this->loadMissing('purchase');

            return self::purchaseQualifiesForAgentAssignment($this->purchase);
        }

        if ($this->stock_id && $this->product_id) {
            $p = Purchase::where('stock_id', $this->stock_id)
                ->where('product_id', $this->product_id)
                ->latest('date')
                ->first();

            return self::purchaseQualifiesForAgentAssignment($p);
        }

        return false;
    }

    public function isSold(): bool
    {
        return $this->sold_at !== null;
    }

    /**
     * Admin → regional manager → team leader → agent custody chain for IMEI tracking.
     *
     * @return array<int, array{role: string, label: string, name: string, email: ?string}>
     */
    public function hierarchyChain(): array
    {
        $this->loadMissing([
            'regionalManagerProductListAssignment.regionalManager:id,name,email',
            'teamLeaderProductListAssignment.teamLeader:id,name,email',
            'agentProductListAssignment.agent:id,name,email',
        ]);

        $chain = [];

        $regionalManager = $this->regionalManagerProductListAssignment?->regionalManager;
        if ($this->regionalManagerProductListAssignment) {
            $chain[] = [
                'role' => 'regional_manager',
                'label' => 'Regional manager',
                'name' => $regionalManager?->name ?? 'Regional manager',
                'email' => $regionalManager?->email,
            ];
        }

        $teamLeader = $this->teamLeaderProductListAssignment?->teamLeader;
        if ($this->teamLeaderProductListAssignment) {
            $chain[] = [
                'role' => 'team_leader',
                'label' => 'Team leader',
                'name' => $teamLeader?->name ?? 'Team leader',
                'email' => $teamLeader?->email,
            ];
        }

        $agent = $this->agentProductListAssignment?->agent;
        if ($this->agentProductListAssignment) {
            $chain[] = [
                'role' => 'agent',
                'label' => 'Agent',
                'name' => $agent?->name ?? 'Agent',
                'email' => $agent?->email,
            ];
        }

        return $chain;
    }
}
