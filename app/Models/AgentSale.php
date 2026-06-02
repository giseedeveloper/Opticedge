<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgentSale extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'agent_id',
        'tenant_id',
        'customer_name',
        'seller_name',
        'product_id',
        'quantity_sold',
        'purchase_price',
        'selling_price',
        'total_purchase_value',
        'total_selling_value',
        'profit',
        'commission_paid',
        'commission_expense_id',
        'date_of_collection',
        'balance',
        'stock_remaining',
        'payment_option_id',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
        'date_of_collection' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function productListItem()
    {
        return $this->hasOne(ProductListItem::class, 'agent_sale_id');
    }
}
