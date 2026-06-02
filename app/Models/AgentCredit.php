<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class AgentCredit extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'agent_id',
        'tenant_id',
        'customer_name',
        'customer_phone',
        'kin_name',
        'kin_phone',
        'product_list_id',
        'product_id',
        'total_amount',
        'purchase_price',
        'selling_price',
        'profit',
        'paid_amount',
        'commission_paid',
        'commission_expense_id',
        'payment_status',
        'payment_option_id',
        'installment_count',
        'installment_amount',
        'installment_interval_days',
        'first_due_date',
        'installment_notes',
        'date',
        'paid_date',
    ];

    protected $casts = [
        'date' => 'date',
        'paid_date' => 'date',
        'first_due_date' => 'date',
        'total_amount' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'commission_paid' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'installment_interval_days' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function productListItem()
    {
        return $this->belongsTo(ProductListItem::class, 'product_list_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function payments()
    {
        return $this->hasMany(AgentCreditPayment::class)
            ->orderByDesc('paid_date')
            ->orderByDesc('id');
    }

    public function commissionExpense()
    {
        return $this->belongsTo(Expense::class, 'commission_expense_id');
    }

    public function displayPurchasePrice(): float
    {
        if ($this->purchase_price !== null) {
            return (float) $this->purchase_price;
        }

        if (! $this->product_id) {
            return 0.0;
        }

        return app(\App\Services\DistributionSaleService::class)->getBuyPriceForProduct((int) $this->product_id);
    }

    public function displaySellingPrice(): float
    {
        if ($this->selling_price !== null) {
            return (float) $this->selling_price;
        }

        return (float) ($this->total_amount ?? 0);
    }

    public function displayProfit(): float
    {
        if ($this->profit !== null) {
            return (float) $this->profit;
        }

        return $this->displaySellingPrice() - $this->displayPurchasePrice();
    }
}
