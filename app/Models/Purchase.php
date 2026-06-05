<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'name',
        'branch_id',
        'stock_id',
        'product_id',
        'tenant_id',
        'quantity',
        'unit_price',
        'distributor_name',
        'total_amount',
        'paid_date',
        'paid_amount',
        'payment_status',
        'payment_receipt_image',
        'payment_option_id',
        'date',
        'limit_status',
        'limit_remaining',
        'sell_price',
        'note',
        'is_passthrough',
    ];

    protected $casts = [
        'is_passthrough' => 'boolean',
    ];

    public function scopeStockPurchases(Builder $query): Builder
    {
        return $query->where('is_passthrough', false);
    }

    public function scopePassthrough(Builder $query): Builder
    {
        return $query->where('is_passthrough', true);
    }

    public function isPassthrough(): bool
    {
        return (bool) $this->is_passthrough;
    }

    public function productListItems()
    {
        return $this->hasMany(ProductListItem::class, 'purchase_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Reconcile limit_remaining from purchase quantity minus registered IMEI rows.
     * Fixes purchases where quantity shows slots but limit_remaining was never set or drifted.
     */
    public function recalculateLimitRemaining(): void
    {
        if ($this->isPassthrough()) {
            return;
        }

        $this->loadMissing('lines');

        if ($this->lines->isNotEmpty()) {
            foreach ($this->lines as $line) {
                $registered = (int) $this->productListItems()
                    ->where('product_id', $line->product_id)
                    ->count();
                $remaining = max(0, (int) $line->quantity - $registered);
                if ((int) $line->limit_remaining !== $remaining) {
                    $line->update(['limit_remaining' => $remaining]);
                }
            }

            $this->unsetRelation('lines');
            $this->load('lines');
            $this->syncAggregatesFromLines();

            return;
        }

        $registered = (int) $this->productListItems()->count();
        $maxQty = (int) ($this->quantity ?? 0);
        $remaining = max(0, $maxQty - $registered);
        $status = $remaining <= 0 ? 'complete' : 'pending';

        if ((int) ($this->limit_remaining ?? 0) !== $remaining || (string) $this->limit_status !== $status) {
            $this->update([
                'limit_remaining' => $remaining,
                'limit_status' => $status,
            ]);
        }
    }

    /**
     * When this purchase has line items, keep header limit fields in sync with lines.
     */
    public function syncAggregatesFromLines(): void
    {
        if (! $this->lines()->exists()) {
            return;
        }

        $sumRemaining = (int) $this->lines()->sum('limit_remaining');
        $this->update([
            'limit_remaining' => $sumRemaining,
            'limit_status' => $sumRemaining <= 0 ? 'complete' : 'pending',
        ]);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class)->latest('paid_date')->latest('created_at');
    }
}
