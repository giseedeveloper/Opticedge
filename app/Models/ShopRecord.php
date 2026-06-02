<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class ShopRecord extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'product_id',
        'tenant_id',
        'opening_stock',
        'quantity_sold',
        'transfer_quantity',
        'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
