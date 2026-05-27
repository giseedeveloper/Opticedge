<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasPlatformCatalog;

    protected static ?string $resolvedTable = null;

    protected $fillable = [
        'category_id',
        'name',
        'brand',
        'price',
        'rating',
        'stock_quantity',
        'description',
        'images',
        'is_platform',
        'created_by_tenant_id',
    ];

    protected $casts = [
        'images' => 'array',
        'is_platform' => 'boolean',
    ];

    public function createdByTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'created_by_tenant_id');
    }

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        // Support both legacy schema (`products`) and renamed schema (`models`).
        static::$resolvedTable = Schema::hasTable('models') ? 'models' : 'products';

        return static::$resolvedTable;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function productListItems()
    {
        return $this->hasMany(ProductListItem::class, 'product_id');
    }
}
