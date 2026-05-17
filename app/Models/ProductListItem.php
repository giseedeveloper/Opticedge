<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductListItem extends Model
{
    protected $table = 'product_list';

    protected $fillable = [
        'stock_id',
        'purchase_id',
        'branch_id',
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
        return $this->belongsTo(Purchase::class);
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
            ->where(function ($q) use ($productId) {
                $q->where('product_id', $productId)
                    ->orWhereHas('purchase', fn ($p) => $p->where('product_id', $productId));
            })
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->when(
                Schema::hasColumn('product_list', 'distribution_sale_id'),
                fn ($q) => $q->whereNull('distribution_sale_id')
            )
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereDoesntHave('agentProductListAssignment');
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

    /**
     * Whether this row is the given catalog product (by id on row or on linked purchase).
     */
    public function isCatalogProduct(int $productId): bool
    {
        if ((int) $this->product_id === $productId) {
            return true;
        }
        $this->loadMissing('purchase');

        return $this->purchase !== null && (int) $this->purchase->product_id === $productId;
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

        return in_array($status, ['paid', 'partial', 'pending'], true)
            || (int) ($purchase->limit_remaining ?? 0) > 0;
    }

    /**
     * Unsold IMEIs for a product, not yet assigned to any agent, from an eligible purchase.
     */
    public function scopeAssignableToAgent($query, int $productId)
    {
        $purchaseOk = function ($p) {
            $p->where('is_passthrough', false)
                ->where(function ($p2) {
                    $p2->whereIn('payment_status', ['paid', 'partial', 'pending'])
                        ->orWhere('limit_remaining', '>', 0);
                });
        };

        return $query
            ->where(function ($q) use ($productId) {
                $q->where('product_id', $productId)
                    ->orWhereHas('purchase', fn ($p) => $p->where('product_id', $productId));
            })
            ->whereNull('sold_at')
            ->whereDoesntHave('agentProductListAssignment')
            ->where(function ($q) use ($purchaseOk) {
                $q->whereHas('purchase', $purchaseOk)
                    ->orWhere(function ($q2) use ($purchaseOk) {
                        $q2->whereNull('purchase_id')
                            ->whereNotNull('product_list.stock_id')
                            ->whereExists(function ($sub) use ($purchaseOk) {
                                $sub->selectRaw('1')
                                    ->from('purchases')
                                    ->whereColumn('purchases.stock_id', 'product_list.stock_id')
                                    ->whereColumn('purchases.product_id', 'product_list.product_id')
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
}
