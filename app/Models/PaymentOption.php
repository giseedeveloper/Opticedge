<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'type',
        'name',
        'balance',
        'opening_balance',
        'is_hidden',
        'tenant_id',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_hidden' => 'boolean',
    ];

    /**
     * Scope to only visible (non-hidden) channels for dropdowns and selection.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to only bank-type channels (e.g. for dealer pending sales).
     */
    public function scopeBank($query)
    {
        return $query->where('type', self::TYPE_BANK);
    }

    public const TYPE_MOBILE = 'mobile';
    public const TYPE_BANK = 'bank';
    public const TYPE_CASH = 'cash';

    /**
     * Watu-named channels are recorded as agent_credits (installment / loan) in the app.
     * Other payment options from the same flow should become normal agent sales (pending → admin finalizes).
     */
    public function isWatuAgentCreditChannel(): bool
    {
        $name = mb_strtolower(trim((string) ($this->name ?? '')), 'UTF-8');

        return $name !== '' && str_contains($name, 'watu');
    }

    public static function types(): array
    {
        return [
            self::TYPE_MOBILE => 'Mobile',
            self::TYPE_BANK => 'Bank',
            self::TYPE_CASH => 'Cash',
        ];
    }

    public function pendingSales()
    {
        return $this->hasMany(PendingSale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
