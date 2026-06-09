<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class DistributionSale extends Model
{
    use BelongsToTenantStrict;

    protected $casts = [
        'date' => 'date',
        'collection_date' => 'date',
    ];

    protected $fillable = [
        'dealer_id',
        'tenant_id',
        'order_id',
        'dealer_name',
        'seller_name',
        'product_id',
        'quantity_sold',
        'purchase_price',
        'selling_price',
        'total_purchase_value',
        'total_selling_value',
        'profit',
        'commission',
        'status',
        'to_be_paid',
        'paid_amount',
        'collection_date',
        'collected_amount',
        'balance',
        'payment_option_id',
        'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function dealer()
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function payments()
    {
        return $this->hasMany(DistributionSalePayment::class)->orderByDesc('paid_date')->orderByDesc('id');
    }

    public function productListItems()
    {
        return $this->hasMany(ProductListItem::class, 'distribution_sale_id');
    }
}
