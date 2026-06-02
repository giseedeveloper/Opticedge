<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class PendingSale extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'customer_name',
        'tenant_id',
        'seller_name',
        'seller_id',
        'product_id',
        'quantity_sold',
        'purchase_price',
        'selling_price',
        'total_purchase_value',
        'total_selling_value',
        'profit',
        'payment_option_id',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function productListItem()
    {
        return $this->hasOne(ProductListItem::class, 'pending_sale_id');
    }
}
