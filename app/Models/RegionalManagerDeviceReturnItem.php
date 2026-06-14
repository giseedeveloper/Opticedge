<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalManagerDeviceReturnItem extends Model
{
    protected $fillable = [
        'regional_manager_device_return_id',
        'product_list_id',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(RegionalManagerDeviceReturn::class, 'regional_manager_device_return_id');
    }

    public function productListItem(): BelongsTo
    {
        return $this->belongsTo(ProductListItem::class, 'product_list_id');
    }
}
