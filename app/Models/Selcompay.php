<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Selcompay extends Model
{
    public const PURPOSE_ORDER_PAYMENT = 'order_payment';

    /** Checkout (create-order-minimal + wallet-payment) for agent commission lines. */
    public const PURPOSE_AGENT_COMMISSION_CHECKOUT = 'agent_commission_checkout';

    /** Selcom Business disbursement (transaction/process) that sends commission TO the agent. */
    public const PURPOSE_AGENT_COMMISSION_DISBURSE = 'agent_commission_disburse';

    /** Vendor package subscription during public signup. */
    public const PURPOSE_VENDOR_SUBSCRIPTION = 'vendor_subscription';

    protected $fillable = [
        'transid',
        'order_id',
        'phone_number',
        'amount',
        'payment_status',
        'local_order_id',
        'purpose',
        'payout_source_type',
        'payout_source_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'local_order_id');
    }
}
