<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Support\ImeiListParser;
use Illuminate\Support\Facades\DB;

class PurchaseImeiRegistrationService
{
    public function register(Purchase $purchase, int $catalogProductId, string $imeiNumbersRaw, bool $oneImeiPerLine = false): PurchaseImeiRegistrationResult
    {
        $result = new PurchaseImeiRegistrationResult;

        $purchase->loadMissing(['product', 'stock', 'lines']);

        if ($purchase->isPassthrough() || $purchase->limit_status !== 'pending' || (int) $purchase->limit_remaining <= 0) {
            $result->errorField = 'purchase_id';
            $result->errorMessage = 'This purchase has no remaining device slots.';

            return $result;
        }

        $catalogProduct = Product::with('category')->find($catalogProductId);
        if (! $catalogProduct) {
            $result->errorField = 'catalog_product_id';
            $result->errorMessage = 'Invalid model.';

            return $result;
        }

        $categoryId = (int) $catalogProduct->category_id;
        $model = (string) $catalogProduct->name;
        $purchaseLine = null;

        if ($purchase->lines->isNotEmpty()) {
            $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
            if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                $result->errorField = 'catalog_product_id';
                $result->errorMessage = 'Pick a model from this purchase that still has open IMEI slots.';

                return $result;
            }
            $remainingForModel = (int) $purchaseLine->limit_remaining;
        } else {
            if ($purchase->product_id && (int) $purchase->product_id !== (int) $catalogProduct->id) {
                $result->errorField = 'catalog_product_id';
                $result->errorMessage = 'Selected model does not match this purchase.';

                return $result;
            }
            $remainingForModel = (int) $purchase->limit_remaining;
        }

        $imeis = $oneImeiPerLine
            ? ImeiListParser::parseOnePerLine($imeiNumbersRaw)
            : ImeiListParser::parse($imeiNumbersRaw);
        $result->parsedCount = count($imeis);

        if ($imeis === []) {
            $result->errorField = 'imei_numbers';
            $result->errorMessage = $oneImeiPerLine
                ? 'Enter at least one IMEI — one IMEI per line.'
                : 'Enter at least one IMEI. Use one per line, or separate with spaces, commas, or semicolons.';

            return $result;
        }

        $lenErrors = ImeiListParser::lengthErrors($imeis);
        if ($lenErrors !== []) {
            $result->errorField = 'imei_numbers';
            $result->errorMessage = implode(' ', $lenErrors);

            return $result;
        }

        if (count($imeis) > $remainingForModel) {
            $result->errorField = 'imei_numbers';
            $result->errorMessage = 'Not enough slots for this model. Remaining for this line: '.$remainingForModel.'.';

            return $result;
        }

        $stockIdForRow = $purchase->stock_id;

        DB::transaction(function () use (
            $purchase,
            $purchaseLine,
            $stockIdForRow,
            $categoryId,
            $model,
            $catalogProduct,
            $imeis,
            $result
        ) {
            $productPrice = $purchaseLine
                ? (float) ($purchaseLine->sell_price ?? $purchaseLine->unit_price)
                : (float) ($purchase->sell_price ?? $purchase->unit_price ?? 0);

            $product = Product::firstOrCreate(
                [
                    'category_id' => $categoryId,
                    'name' => $model,
                ],
                [
                    'price' => $productPrice,
                    'stock_quantity' => 0,
                    'rating' => 5.0,
                    'description' => 'From product list',
                    'images' => $catalogProduct->images ?? $purchase->product?->images ?? [],
                ]
            );

            $sellToApply = $purchaseLine ? $purchaseLine->sell_price : $purchase->sell_price;
            if ($sellToApply && (float) $product->price != (float) $sellToApply) {
                $product->update(['price' => (float) $sellToApply]);
            }

            foreach ($imeis as $imei) {
                if (ProductListItem::where('imei_number', $imei)->exists()) {
                    $result->failed[] = $imei.' (already in list)';
                    $result->failureReasons['duplicates'][] = $imei;

                    continue;
                }

                $purchase->refresh();
                if ($purchaseLine instanceof PurchaseLine) {
                    $purchaseLine->refresh();
                    if ((int) $purchaseLine->limit_remaining <= 0) {
                        $result->failed[] = $imei.' (purchase limit exhausted for this model)';
                        $result->failureReasons['limit_exhausted'][] = $imei;
                        break;
                    }
                } elseif ($purchase->limit_remaining <= 0) {
                    $result->failed[] = $imei.' (purchase limit exhausted)';
                    $result->failureReasons['limit_exhausted'][] = $imei;
                    break;
                }

                ProductListItem::create([
                    'stock_id' => $stockIdForRow,
                    'purchase_id' => $purchase->id,
                    'category_id' => $categoryId,
                    'model' => $model,
                    'imei_number' => $imei,
                    'product_id' => $product->id,
                ]);

                if ($purchaseLine) {
                    $purchaseLine->decrement('limit_remaining');
                    $purchase->syncAggregatesFromLines();
                } else {
                    $purchase->decrement('limit_remaining');
                    if ($purchase->fresh()->limit_remaining <= 0) {
                        $purchase->update(['limit_status' => 'complete']);
                    }
                }
                $result->created++;
            }
        });

        $purchase->refresh();
        $result->purchaseLimitRemaining = (int) $purchase->limit_remaining;

        if ($purchaseLine) {
            $purchaseLine->refresh();
            $result->modelLimitRemaining = (int) $purchaseLine->limit_remaining;
        } else {
            $result->modelLimitRemaining = $result->purchaseLimitRemaining;
        }

        if ($result->created === 0 && ! $result->hasValidationError()) {
            $result->errorField = 'imei_numbers';
            $result->errorMessage = self::buildDetailedErrorMessage($imeis, $result->failureReasons);
        }

        return $result;
    }

    /**
     * @param  array{duplicates?: list<string>, limit_exhausted?: list<string>}  $failureReasons
     */
    public static function buildDetailedErrorMessage(array $imeis, array $failureReasons): string
    {
        $duplicateCount = count($failureReasons['duplicates'] ?? []);
        $limitExhaustedCount = count($failureReasons['limit_exhausted'] ?? []);
        $totalParsed = count($imeis);

        $messages = [];
        $messages[] = "No devices added. Parsed $totalParsed IMEI(s), but all failed.";

        if ($duplicateCount > 0) {
            $samples = array_slice($failureReasons['duplicates'], 0, 3);
            $sampleList = implode(', ', $samples);
            $more = $duplicateCount > 3 ? ' (+ '.($duplicateCount - 3).' more)' : '';
            $messages[] = "All duplicates: $duplicateCount IMEI(s) already exist in the system. Examples: $sampleList$more";
        }

        if ($limitExhaustedCount > 0) {
            $samples = array_slice($failureReasons['limit_exhausted'], 0, 3);
            $sampleList = implode(', ', $samples);
            $more = $limitExhaustedCount > 3 ? ' (+ '.($limitExhaustedCount - 3).' more)' : '';
            $messages[] = "Purchase limit exhausted: $limitExhaustedCount IMEI(s) could not be added because the purchase limit has been reached. Examples: $sampleList$more";
        }

        if ($duplicateCount === 0 && $limitExhaustedCount === 0) {
            $messages[] = 'Verify you selected the correct purchase and model, and that all IMEIs are properly formatted.';
        }

        return implode("\n", $messages);
    }
}
