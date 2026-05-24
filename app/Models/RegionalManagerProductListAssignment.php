<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionalManagerProductListAssignment extends Model
{
    protected $fillable = [
        'regional_manager_id',
        'product_list_id',
    ];

    public function regionalManager()
    {
        return $this->belongsTo(User::class, 'regional_manager_id');
    }

    public function productListItem()
    {
        return $this->belongsTo(ProductListItem::class, 'product_list_id');
    }
}
