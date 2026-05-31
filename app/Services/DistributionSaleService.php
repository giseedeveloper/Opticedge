<?php

namespace App\Services;

use App\Models\DistributionSale;
use App\Models\Order;
use App\Models\Purchase;

class DistributionSaleService
{
    public const REFERRER_COMMISSION = 500000;

    /**
     * Create distribution sale records from a dealer order.
     * Buy price from purchase data; sell price from order item; commission 500k on first purchase if dealer has referrer.
     */
    public function createFromOrder(Order $order, string $status = 'pending'): void
    {
        $user = $order->user;
        if ($user->role !== 'dealer') {
            return;
        }

        $order->load(['items.product.category']);
        $dealerName = $user->business_name ?? $user->name;
        $sellerName = $user->referrer?->name;

        $isFirstPurchase = !DistributionSale::where('dealer_id', $user->id)->exists();
        $hasReferrer = (bool) $user->referred_by;
        $giveCommission = $isFirstPurchase && $hasReferrer;

        $first = true;
        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $buyPrice = $this->getBuyPriceForProduct($product->id);
            $sellPrice = (float) $item->price;
            $qty = (int) $item->quantity;

            $totalBuy = $buyPrice * $qty;
            $totalSell = $sellPrice * $qty;
            $commission = ($giveCommission && $first) ? self::REFERRER_COMMISSION : 0;
            $profit = $totalSell - $totalBuy - $commission;

            DistributionSale::create([
                'dealer_id' => $user->id,
                'order_id' => $order->id,
                'dealer_name' => $dealerName,
                'seller_name' => $sellerName,
                'product_id' => $product->id,
                'quantity_sold' => $qty,
                'purchase_price' => $buyPrice,
                'selling_price' => $sellPrice,
                'total_purchase_value' => $totalBuy,
                'total_selling_value' => $totalSell,
                'profit' => $profit,
                'commission' => $commission,
                'status' => $status,
                'to_be_paid' => $totalSell,
                'paid_amount' => 0,
                'balance' => $totalSell,
                'date' => $order->created_at->toDateString(),
            ]);

            $first = false;
        }
    }

    /**
     * Get buy price (unit) for a product from purchase data.
     * When $purchaseId is set, uses that purchase; otherwise the latest purchase for the product.
     */
    public function getBuyPriceForProduct(int $productId, ?int $purchaseId = null): float
    {
        $purchase = $this->resolvePurchaseForProduct($productId, $purchaseId);

        if (! $purchase) {
            return 0;
        }

        return $this->getPricesForProductOnPurchase($productId, $purchase)['buy'];
    }

    /**
     * Get sell price (unit) for a product from purchase data.
     * When $purchaseId is set, uses that purchase; otherwise the latest purchase for the product.
     */
    public function getSellPriceForProduct(int $productId, ?int $purchaseId = null): float
    {
        $purchase = $this->resolvePurchaseForProduct($productId, $purchaseId);

        if (! $purchase) {
            return 0;
        }

        return $this->getPricesForProductOnPurchase($productId, $purchase)['sell'];
    }

    /**
     * @return array{buy: float, sell: float}
     */
    public function getPricesForProductOnPurchase(int $productId, Purchase $purchase): array
    {
        $purchase->loadMissing('lines');

        if ($purchase->lines->isNotEmpty()) {
            $line = $purchase->lines->firstWhere('product_id', $productId);
            if ($line) {
                $buy = (float) ($line->unit_price ?? 0);
                $sell = $line->sell_price !== null
                    ? (float) $line->sell_price
                    : $buy;

                return ['buy' => $buy, 'sell' => $sell];
            }

            return ['buy' => 0.0, 'sell' => 0.0];
        }

        if ($purchase->product_id && (int) $purchase->product_id !== $productId) {
            return ['buy' => 0.0, 'sell' => 0.0];
        }

        $buy = (float) ($purchase->unit_price ?? 0);
        $sell = $purchase->sell_price !== null
            ? (float) $purchase->sell_price
            : $buy;

        return ['buy' => $buy, 'sell' => $sell];
    }

    private function resolvePurchaseForProduct(int $productId, ?int $purchaseId): ?Purchase
    {
        if ($purchaseId !== null) {
            return Purchase::with('lines')->find($purchaseId);
        }

        return Purchase::where('product_id', $productId)
            ->latest('date')
            ->first();
    }
}
