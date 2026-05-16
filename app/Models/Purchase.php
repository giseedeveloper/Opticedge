<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'name',
        'branch_id',
        'stock_id',
        'product_id',
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
    ];

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
