<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDeviceReturnItem extends Model
{
    protected $fillable = [
        'agent_device_return_id',
        'product_list_id',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(AgentDeviceReturn::class, 'agent_device_return_id');
    }

    public function productListItem(): BelongsTo
    {
        return $this->belongsTo(ProductListItem::class, 'product_list_id');
    }
}
