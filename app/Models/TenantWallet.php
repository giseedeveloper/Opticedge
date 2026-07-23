<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vendor's pre-funded disbursement wallet. Not tenant-scoped as a global model so
 * the superadmin can read every vendor's balance; callers must pass tenant_id
 * explicitly. Balance is mutated only through {@see \App\Services\WalletService}.
 */
class TenantWallet extends Model
{
    protected $fillable = [
        'tenant_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'tenant_id', 'tenant_id');
    }
}
