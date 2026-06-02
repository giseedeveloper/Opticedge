<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = ['name', 'stock_limit', 'default_category_id', 'default_model', 'default_quantity', 'tenant_id'];

    public function defaultCategory()
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function productListItems()
    {
        return $this->hasMany(ProductListItem::class, 'stock_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /** Count of items not yet sold (available quantity) */
    public function getQuantityAttribute(): int
    {
        return $this->productListItems()->whereNull('sold_at')->count();
    }

    /** Whether this stock can accept more items (under limit) */
    public function getUnderLimitAttribute(): bool
    {
        return $this->quantity < $this->stock_limit;
    }
}
