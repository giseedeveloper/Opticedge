<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'user_id',
        'status',
        'total_price',
        'shipping_address',
        'payment_method',
        'address_id',
        'payment_status',
        'payment_option_id',
        'tenant_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }
}
