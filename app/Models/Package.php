<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    public const INTERVALS = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'one_time' => 'One-time',
    ];

    protected $fillable = [
        'name',
        'slug',
        'price',
        'interval',
        'profit',
        'features_json',
        'max_users',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'profit' => 'decimal:2',
            'features_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function intervalLabel(): string
    {
        return self::INTERVALS[$this->interval] ?? ucfirst(str_replace('_', ' ', (string) $this->interval));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function subscriptionEndsAtFrom(?\DateTimeInterface $from = null): \Illuminate\Support\Carbon
    {
        $start = $from ? \Illuminate\Support\Carbon::parse($from) : now();

        return match ($this->interval) {
            'yearly' => $start->copy()->addYear(),
            'quarterly' => $start->copy()->addMonths(3),
            'one_time' => $start->copy()->addYears(10),
            default => $start->copy()->addMonth(),
        };
    }

    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 0).' TZS';
    }

    public function formattedProfit(): string
    {
        return number_format((float) $this->profit, 0).' TZS';
    }

    public function profitMarginPercent(): ?float
    {
        $price = (float) $this->price;

        if ($price <= 0) {
            return null;
        }

        return round(((float) $this->profit / $price) * 100, 1);
    }

    public function monthlyMultiplier(): float
    {
        return match ($this->interval) {
            'yearly' => 1 / 12,
            'quarterly' => 1 / 3,
            'one_time' => 0,
            default => 1,
        };
    }

    public function estimatedMonthlyRevenue(): float
    {
        if (! $this->is_active) {
            return 0;
        }

        return (float) $this->price * $this->tenants_count * $this->monthlyMultiplier();
    }

    public function estimatedMonthlyProfit(): float
    {
        if (! $this->is_active) {
            return 0;
        }

        return (float) $this->profit * $this->tenants_count * $this->monthlyMultiplier();
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
