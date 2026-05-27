<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasPlatformCatalog;

    protected $fillable = [
        'name',
        'is_platform',
        'created_by_tenant_id',
    ];

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
