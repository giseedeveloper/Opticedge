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

    /**
     * Canonical catalog of feature flags a package can grant.
     * Keys are stored in features_json; values are the display labels.
     */
    public const FEATURES = [
        'imei_tracking' => 'IMEI Tracking',
        'sales' => 'Sales',
        'stock_governance' => 'Stock Governance',
        'stock_aging' => 'Stock Aging',
        'automatic_receipting' => 'Automatic Receipting',
        'commissions' => 'Commissions (incl. Bulk)',
        'stock_transfers' => 'Stock Transfers',
        'distribution_module' => 'Distribution Module',
        'command_center' => 'Command center',
        'multi_branch' => 'Multi-branch',
    ];

    protected $fillable = [
        'name',
        'slug',
        'price',
        'interval',
        'profit',
        'features_json',
        'max_users',
        'max_agents',
        'max_admins',
        'trial_days',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'profit' => 'decimal:2',
            'features_json' => 'array',
            'max_users' => 'integer',
            'max_agents' => 'integer',
            'max_admins' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether this package grants the given feature flag.
     */
    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features_json[$key] ?? false);
    }

    /**
     * Human labels for every enabled feature flag on this package.
     *
     * @return array<int, string>
     */
    public function enabledFeatureLabels(): array
    {
        $labels = [];
        foreach ((array) $this->features_json as $key => $enabled) {
            if (! $enabled) {
                continue;
            }
            $labels[] = self::FEATURES[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
        }

        return $labels;
    }

    /**
     * Display a numeric limit, treating null as unlimited.
     */
    public function limitLabel(?int $value): string
    {
        return $value === null ? 'Unlimited' : number_format($value);
    }

    public function trialLabel(): string
    {
        return $this->trial_days ? $this->trial_days.'-day trial' : 'No trial';
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
