<?php

namespace App\Services;

use App\Models\AgentCredit;
use App\Models\AgentSale;
use App\Models\PaymentOption;
use App\Services\AgentCommissionExpenseService;
use App\Models\ProductListItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentSaleCreditMigrationService
{
    public function __construct(
        protected DistributionSaleService $distributionSaleService
    ) {}

    /**
     * Default Watu / agent-credit collection channel: Store setting first (any visible channel, e.g. “I&M”),
     * then optional fallback to a channel whose name contains “Watu”.
     */
    public function resolveDefaultWatuPaymentOption(): PaymentOption
    {
        $watuDefaultRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
        if (is_numeric($watuDefaultRaw)) {
            $candidate = PaymentOption::visible()->find((int) $watuDefaultRaw);
            if ($candidate) {
                return $candidate;
            }
        }

        $fallback = PaymentOption::visible()
            ->orderBy('name')
            ->get()
            ->first(fn (PaymentOption $opt) => $opt->isWatuAgentCreditChannel());

        if (! $fallback) {
            throw new \InvalidArgumentException(
                'No default Watu channel is available. In Store settings, set Default Watu channel to a visible payment option, or add a visible channel whose name contains “Watu”.'
            );
        }

        return $fallback;
    }

    /**
     * Convert a finalized agent sale into an agent credit: undo sale channel + commission,
     * move the same amount onto the default Watu channel, create credit (unpaid), relink IMEIs.
     */
    public function convertAgentSaleToAgentCredit(AgentSale $sale): AgentCredit
    {
        $eps = 0.0001;
        $sale->loadMissing(['agent', 'product']);

        if (! $sale->agent_id) {
            throw new \InvalidArgumentException('This sale has no agent; it cannot be converted to agent credit.');
        }
        if (! $sale->product_id) {
            throw new \InvalidArgumentException('This sale has no product.');
        }
        if (! $sale->payment_option_id) {
            throw new \InvalidArgumentException('Set a payment channel on this sale before converting to credit (or the channel balance cannot be adjusted).');
        }

        $total = (float) ($sale->total_selling_value ?? 0);
        if ($total <= $eps) {
            throw new \InvalidArgumentException('Sale total must be greater than zero.');
        }

        $linkedItems = ProductListItem::query()->where('agent_sale_id', $sale->id)->get();
        if ($linkedItems->count() > 1) {
            throw new \InvalidArgumentException('This sale is linked to more than one IMEI row. Split or correct links before converting.');
        }

        $watu = $this->resolveDefaultWatuPaymentOption();

        return DB::transaction(function () use ($sale, $total, $eps, $linkedItems, $watu) {
            $sale = AgentSale::lockForUpdate()->findOrFail($sale->id);

            $this->reverseAgentSalePaymentAndCommissionOnly($sale);

            $watuFresh = PaymentOption::lockForUpdate()->find($watu->id);
            if ($watuFresh && $total > $eps) {
                $watuFresh->increment('balance', $total);
            }

            $purchasePrice = (float) ($sale->purchase_price ?? 0);
            $sellingPrice = (float) ($sale->selling_price ?? $total);
            $profit = $sale->profit !== null ? (float) $sale->profit : ($sellingPrice - $purchasePrice);

            $creditAttrs = [
                'agent_id' => $sale->agent_id,
                'customer_name' => $sale->customer_name ?: 'Customer',
                'customer_phone' => null,
                'kin_name' => null,
                'kin_phone' => null,
                'product_list_id' => $linkedItems->first()?->id,
                'product_id' => (int) $sale->product_id,
                'total_amount' => $total,
                'paid_amount' => 0,
                'commission_paid' => (float) ($sale->commission_paid ?? 0),
                'payment_status' => 'pending',
                'payment_option_id' => $watu->id,
                'installment_count' => null,
                'installment_amount' => null,
                'installment_interval_days' => null,
                'first_due_date' => null,
                'installment_notes' => trim('[Converted from agent sale #'.$sale->id.']'
                    .($sale->seller_name ? ' Seller: '.$sale->seller_name : '')),
                'date' => $sale->date ? Carbon::parse($sale->date)->toDateString() : now()->toDateString(),
                'paid_date' => null,
            ];

            if (Schema::hasColumn('agent_credits', 'purchase_price')) {
                $creditAttrs['purchase_price'] = $purchasePrice;
                $creditAttrs['selling_price'] = $sellingPrice;
                $creditAttrs['profit'] = $profit;
            }

            $credit = AgentCredit::create($creditAttrs);

            foreach ($linkedItems as $item) {
                $item->update([
                    'agent_credit_id' => $credit->id,
                    'agent_sale_id' => null,
                ]);
            }

            DB::table('agent_sales')->where('id', $sale->id)->delete();

            return $credit->fresh();
        });
    }

    /**
     * Undo channel top-up and commission expense for a sale (does not touch product_list).
     */
    public function reverseAgentSalePaymentAndCommissionOnly(AgentSale $sale): void
    {
        if ($sale->payment_option_id) {
            $po = PaymentOption::find($sale->payment_option_id);
            $amount = (float) ($sale->total_selling_value ?? 0);
            if ($po && $amount > 0) {
                if ((float) $po->balance + 0.0001 < $amount) {
                    throw new \InvalidArgumentException('The sale’s payment channel balance is already lower than this sale amount.');
                }
                $po->decrement('balance', $amount);
            }
        }

        app(AgentCommissionExpenseService::class)->reverseForAgentSale($sale);
    }
}
