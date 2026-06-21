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
        'date' => 'date',
        'expiry_date' => 'date',
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
     * Catalog model IDs on this purchase (header, lines, and registered IMEIs).
     */
    public function catalogProductIds(): \Illuminate\Support\Collection
    {
        $this->loadMissing(['lines']);

        $ids = collect();
        if ($this->product_id) {
            $ids->push((int) $this->product_id);
        }
        foreach ($this->lines as $line) {
            if ($line->product_id) {
                $ids->push((int) $line->product_id);
            }
        }

        $fromRegistered = ProductListItem::onPurchaseStock((int) $this->id)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id');

        return $ids->merge($fromRegistered)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    public function registeredCountForProduct(int $productId): int
    {
        return (int) $this->productListItems()
            ->where('product_id', $productId)
            ->count();
    }

    public function effectiveLineQuantity(PurchaseLine $line): int
    {
        $qty = (int) $line->quantity;
        if ($qty > 0) {
            return $qty;
        }

        if ($this->relationLoaded('lines') ? $this->lines->count() === 1 : $this->lines()->count() === 1) {
            return (int) ($this->quantity ?? 0);
        }

        return $qty;
    }

    public function openSlotsForLine(PurchaseLine $line): int
    {
        $productId = (int) $line->product_id;

        return max(0, $this->effectiveLineQuantity($line) - $this->registeredCountForProduct($productId));
    }

    public function openSlotsForHeaderProduct(): int
    {
        $registered = (int) $this->productListItems()->count();
        $maxQty = (int) ($this->quantity ?? 0);

        return max(0, $maxQty - $registered);
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
                $effectiveQty = $this->effectiveLineQuantity($line);
                $remaining = $this->openSlotsForLine($line);
                $updates = [];

                if ((int) $line->quantity !== $effectiveQty && $effectiveQty > 0) {
                    $updates['quantity'] = $effectiveQty;
                }
                if ((int) $line->limit_remaining !== $remaining) {
                    $updates['limit_remaining'] = $remaining;
                }
                if ($updates !== []) {
                    $line->update($updates);
                }
            }

            $this->unsetRelation('lines');
            $this->load('lines');
            $this->syncAggregatesFromLines();

            return;
        }

        $remaining = $this->openSlotsForHeaderProduct();
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

    /**
     * Filter purchases by invoice, distributor, branch, or product name.
     */
    public function scopeListSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $like = '%'.$search.'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('name', 'like', $like)
                ->orWhere('distributor_name', 'like', $like)
                ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $like))
                ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', $like))
                ->orWhereHas('lines.product', fn (Builder $product) => $product->where('name', 'like', $like));
        });
    }
}
