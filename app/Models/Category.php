<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasPlatformCatalog;

    protected static ?string $resolvedTable = null;

    protected $fillable = ['name', 'image', 'is_platform', 'created_by_tenant_id'];

    protected function casts(): array
    {
        return [
            'is_platform' => 'boolean',
        ];
    }

    public function createdByTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'created_by_tenant_id');
    }

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        // Support both legacy schema (`categories`) and renamed schema (`brands`).
        static::$resolvedTable = Schema::hasTable('brands') ? 'brands' : 'categories';

        return static::$resolvedTable;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
