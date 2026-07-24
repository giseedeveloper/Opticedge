<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\AgentSale;
use App\Models\ProductListItem;
use App\Models\Product;
use App\Models\User;
use App\Models\AgentCredit;
use App\Models\AgentCreditPayment;
use App\Models\PendingSale;
use App\Models\PaymentOption;
use App\Models\Setting;
use App\Models\Purchase;
use App\Services\AgentProductTransferService;
use App\Services\DistributionSaleService;
use App\Support\ImeiListParser;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductListController extends Controller
{
    private function ensureTenantContext(): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        if ($user->isSuperadmin()) {
            TenantContext::bypass();

            return;
        }

        if ($user->tenant_id !== null) {
            TenantContext::set((int) $user->tenant_id);
        }
    }

    /**
     * Admin: Add a product to product_list.
     * Accepts either purchase_id + imei_number (category/model from purchase) or stock_id + category_id + model + imei_number.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'nullable|exists:purchases,id',
            'stock_id' => 'nullable|exists:stocks,id',
            'category_id' => 'nullable|exists:brands,id',
            'model' => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:models,id',
            'imei_number' => 'required|string|max:512|unique:product_list,imei_number',
        ]);

        $purchase = null;
        $stockId = null;
        $categoryId = null;
        $model = null;
        $purchaseLine = null;
        $catalogProduct = null;

        if (! empty($validated['purchase_id'])) {
            $purchase = Purchase::stockPurchases()->with(['product', 'lines'])->findOrFail($validated['purchase_id']);
            if ($purchase->isPassthrough() || $purchase->limit_status !== 'pending' || $purchase->limit_remaining <= 0) {
                return response()->json([
                    'message' => 'This purchase has no remaining limit.',
                ], 422);
            }

            if ($purchase->lines->isNotEmpty()) {
                if (empty($validated['product_id'])) {
                    return response()->json([
                        'message' => 'This purchase has multiple models. Send product_id for the line you are adding to.',
                    ], 422);
                }
                $catalogProduct = Product::findOrFail((int) $validated['product_id']);
                $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
                if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                    return response()->json([
                        'message' => 'No remaining IMEI slots for that model on this purchase.',
                    ], 422);
                }
                $stockId = $purchase->stock_id;
                $categoryId = (int) $catalogProduct->category_id;
                $model = (string) $catalogProduct->name;
            } else {
                if (! $purchase->product_id) {
                    return response()->json([
                        'message' => 'Purchase has no product linked.',
                    ], 422);
                }
                $stockId = $purchase->stock_id;
                $categoryId = $purchase->product->category_id;
                $model = $purchase->product->name;
                $catalogProduct = $purchase->product;
            }
        } else {
            if (empty($validated['stock_id'])) {
                return response()->json([
                    'message' => 'Provide either purchase_id or stock_id (with model fields or product_id).',
                ], 422);
            }
            $stock = \App\Models\Stock::findOrFail($validated['stock_id']);
            $purchase = Purchase::stockPurchases()->with(['product', 'lines'])
                ->where('stock_id', $stock->id)
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->latest('date')
                ->latest('id')
                ->first();
            if (! $purchase) {
                return response()->json([
                    'message' => 'No pending purchase limit for this stock.',
                ], 422);
            }
            $stockId = $validated['stock_id'];

            if ($purchase->lines->isNotEmpty()) {
                if (empty($validated['product_id'])) {
                    return response()->json([
                        'message' => 'This stock\'s purchase has multiple models. Send product_id for the catalog model.',
                    ], 422);
                }
                $catalogProduct = Product::findOrFail((int) $validated['product_id']);
                $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
                if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                    return response()->json([
                        'message' => 'No remaining IMEI slots for that model on the linked purchase.',
                    ], 422);
                }
                $categoryId = (int) $catalogProduct->category_id;
                $model = (string) $catalogProduct->name;
            } else {
                if (empty($validated['category_id']) || empty($validated['model'])) {
                    return response()->json([
                        'message' => 'Provide category_id and model, or product_id when the purchase has multiple models.',
                    ], 422);
                }
                $categoryId = (int) $validated['category_id'];
                $model = (string) $validated['model'];
            }
        }

        $productPrice = $purchaseLine
            ? (float) ($purchaseLine->sell_price ?? $purchaseLine->unit_price)
            : (float) ($purchase->sell_price ?? $purchase->unit_price ?? 0);

        $imageSource = $catalogProduct ?? $purchase->product;

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
                'images' => $imageSource?->images ?? [],
            ]
        );

        $sellToApply = $purchaseLine ? $purchaseLine->sell_price : $purchase->sell_price;
        if ($sellToApply && (float) $product->price != (float) $sellToApply) {
            $product->update(['price' => (float) $sellToApply]);
        }

        $item = ProductListItem::create([
            'stock_id' => $stockId,
            'purchase_id' => $purchase->id,
            'category_id' => $categoryId,
            'model' => $model,
            'imei_number' => $validated['imei_number'],
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

        return response()->json([
            'message' => 'Product added to list.',
            'data' => [
                'id' => $item->id,
                'stock_id' => $item->stock_id,
                'category_id' => $item->category_id,
                'model' => $item->model,
                'imei_number' => $item->imei_number,
            ],
        ], 201);
    }

    /**
     * Admin: Add multiple IMEIs to product_list for one purchase.
     */
    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'product_id' => 'nullable|exists:models,id',
            'imei_numbers' => 'required|array|min:1',
            'imei_numbers.*' => 'required|string|max:65535',
        ]);

        $purchase = Purchase::stockPurchases()->with(['product', 'lines'])->findOrFail($validated['purchase_id']);
        if ($purchase->isPassthrough() || $purchase->limit_status !== 'pending' || $purchase->limit_remaining <= 0) {
            return response()->json([
                'message' => 'This purchase has no remaining limit.',
            ], 422);
        }

        $purchaseLine = null;
        $catalogProduct = null;

        if ($purchase->lines->isNotEmpty()) {
            if (empty($validated['product_id'])) {
                return response()->json([
                    'message' => 'This purchase has multiple models. Send product_id for the line you are adding to.',
                ], 422);
            }
            $catalogProduct = Product::findOrFail((int) $validated['product_id']);
            $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
            if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                return response()->json([
                    'message' => 'No remaining IMEI slots for that model on this purchase.',
                ], 422);
            }
        } elseif (! $purchase->product_id) {
            return response()->json([
                'message' => 'Purchase has no product linked.',
            ], 422);
        }

        $imeis = [];
        foreach ($validated['imei_numbers'] as $entry) {
            $imeis = array_merge($imeis, ImeiListParser::parse((string) $entry));
        }
        $imeis = array_values(array_unique($imeis));

        if ($imeis === []) {
            return response()->json([
                'message' => 'No IMEIs after parsing. Use one code per line or separate with spaces, commas, or semicolons.',
            ], 422);
        }

        $lenErrors = ImeiListParser::lengthErrors($imeis);
        if ($lenErrors !== []) {
            return response()->json([
                'message' => implode(' ', $lenErrors),
            ], 422);
        }

        $remainingForModel = $purchaseLine
            ? (int) $purchaseLine->limit_remaining
            : (int) $purchase->limit_remaining;

        if (count($imeis) > $remainingForModel) {
            return response()->json([
                'message' => 'Not enough slots for this model. Remaining: '.$remainingForModel.'.',
            ], 422);
        }

        $created = [];
        $failed = [];

        DB::transaction(function () use ($purchase, $purchaseLine, $catalogProduct, $imeis, &$created, &$failed) {
            $purchase->refresh();
            $stockId = $purchase->stock_id;

            if ($purchaseLine) {
                $categoryId = (int) $catalogProduct->category_id;
                $model = (string) $catalogProduct->name;
                $productPrice = (float) ($purchaseLine->sell_price ?? $purchaseLine->unit_price);
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
                        'images' => $catalogProduct->images ?? [],
                    ]
                );

                if ($purchaseLine->sell_price && (float) $product->price != (float) $purchaseLine->sell_price) {
                    $product->update(['price' => (float) $purchaseLine->sell_price]);
                }

                foreach ($imeis as $imei) {
                    if (ProductListItem::where('imei_number', $imei)->exists()) {
                        $failed[] = ['imei_number' => $imei, 'message' => 'IMEI already in product list.'];

                        continue;
                    }

                    $purchaseLine->refresh();
                    if ((int) $purchaseLine->limit_remaining <= 0) {
                        $failed[] = ['imei_number' => $imei, 'message' => 'Purchase limit exhausted for this model.'];

                        break;
                    }

                    $item = ProductListItem::create([
                        'stock_id' => $stockId,
                        'purchase_id' => $purchase->id,
                        'category_id' => $categoryId,
                        'model' => $model,
                        'imei_number' => $imei,
                        'product_id' => $product->id,
                    ]);

                    $purchaseLine->decrement('limit_remaining');
                    $purchase->syncAggregatesFromLines();

                    $created[] = [
                        'id' => $item->id,
                        'imei_number' => $item->imei_number,
                        'model' => $item->model,
                    ];
                }

                return;
            }

            $categoryId = $purchase->product->category_id;
            $model = $purchase->product->name;
            $productPrice = $purchase->sell_price ?? $purchase->unit_price ?? 0;

            $product = Product::firstOrCreate(
                [
                    'category_id' => $categoryId,
                    'name' => $model,
                ],
                [
                    'price' => (float) $productPrice,
                    'stock_quantity' => 0,
                    'rating' => 5.0,
                    'description' => 'From product list',
                    'images' => $purchase->product?->images ?? [],
                ]
            );

            if ($purchase->sell_price && $product->price != $purchase->sell_price) {
                $product->update(['price' => (float) $purchase->sell_price]);
            }

            foreach ($imeis as $imei) {
                if (ProductListItem::where('imei_number', $imei)->exists()) {
                    $failed[] = ['imei_number' => $imei, 'message' => 'IMEI already in product list.'];

                    continue;
                }

                $purchase->refresh();
                if ($purchase->limit_remaining <= 0) {
                    $failed[] = ['imei_number' => $imei, 'message' => 'Purchase limit exhausted.'];

                    break;
                }

                $item = ProductListItem::create([
                    'stock_id' => $stockId,
                    'purchase_id' => $purchase->id,
                    'category_id' => $categoryId,
                    'model' => $model,
                    'imei_number' => $imei,
                    'product_id' => $product->id,
                ]);

                $purchase->decrement('limit_remaining');
                if ($purchase->fresh()->limit_remaining <= 0) {
                    $purchase->update(['limit_status' => 'complete']);
                }

                $created[] = [
                    'id' => $item->id,
                    'imei_number' => $item->imei_number,
                    'model' => $item->model,
                ];
            }
        });

        $status = count($created) > 0 ? 201 : 422;

        return response()->json([
            'message' => count($created) > 0
                ? 'Batch add completed.'
                : 'No items added (all duplicates, limit reached, or nothing valid after splitting IMEIs).',
            'data' => [
                'created' => $created,
                'failed' => $failed,
                'parsed_count' => count($imeis),
            ],
        ], $status);
    }

    /**
     * Agent: List product_list items that are available to sell (not yet sold).
     * Only returns items where sold_at is null.
     */
    public function available()
    {
        $this->ensureTenantContext();

        $agentId = Auth::id();
        $assignedIds = AgentProductListAssignment::where('agent_id', $agentId)->pluck('product_list_id');

        $items = ProductListItem::with(['category', 'product', 'stock', 'purchase'])
            ->whereIn('id', $assignedIds)
            ->whereNull('sold_at')
            ->orderBy('model')
            ->orderBy('imei_number')
            ->get();

        $data = $items->map(function ($item) {
            $sellPrice = null;
            if ($item->purchase_id && $item->purchase) {
                $sellPrice = $item->purchase->sell_price !== null ? (float) $item->purchase->sell_price : null;
            }
            if ($sellPrice === null && $item->stock_id && $item->product_id) {
                $purchase = Purchase::where('stock_id', $item->stock_id)
                    ->where('product_id', $item->product_id)
                    ->whereNotNull('sell_price')
                    ->latest('date')
                    ->first();
                $sellPrice = $purchase ? (float) $purchase->sell_price : null;
            }
            if ($sellPrice === null && $item->product) {
                $sellPrice = $item->product->price > 0 ? (float) $item->product->price : null;
            }
            $sellPrice = $sellPrice ?? 0.0;

            $purchasePrice = null;
            if ($item->purchase_id && $item->purchase) {
                $purchasePrice = (float) $item->purchase->unit_price;
            }
            if ($purchasePrice === null && $item->stock_id && $item->product_id) {
                $purchase = Purchase::where('stock_id', $item->stock_id)
                    ->where('product_id', $item->product_id)
                    ->latest('date')
                    ->first();
                $purchasePrice = $purchase ? (float) $purchase->unit_price : null;
            }
            if ($purchasePrice === null && $item->product) {
                $purchasePrice = (float) $item->product->price;
            }
            $purchasePrice = $purchasePrice ?? 0.0;

            return [
                'id' => $item->id,
                'imei_number' => $item->imei_number,
                'model' => $item->model,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name,
                'stock_id' => $item->stock_id,
                'stock_name' => $item->stock?->name,
                'sell_price' => $sellPrice,
                'purchase_price' => $purchasePrice,
                'product_id' => $item->product_id,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Agent: Get device info by IMEI (only if not sold).
     * Returns which stock the device is in, category and sell price from that stock's purchase.
     */
    public function showByImei(string $imei)
    {
        $this->ensureTenantContext();

        $agentId = Auth::id();

        $item = ProductListItem::with(['category', 'product', 'stock', 'purchase'])
            ->where('imei_number', $imei)
            ->whereNull('sold_at')
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'This device is not in stock or has already been sold. Only devices that are purchased and still in stock can be sold.',
            ], 404);
        }

        if (! AgentProductListAssignment::where('agent_id', $agentId)->where('product_list_id', $item->id)->exists()) {
            return response()->json([
                'message' => 'This device is not assigned to you. Only devices assigned by admin can be sold.',
            ], 404);
        }

        // Stock: which stock this barcode is in
        $stockName = $item->stock?->name;
        $stockId = $item->stock_id;

        // Category from item (linked to stock)
        $categoryName = $item->category?->name;
        $categoryId = $item->category_id;

        // Sell price from the purchase for this stock (recommended selling price)
        $sellPrice = null;
        if ($item->purchase_id && $item->purchase) {
            $sellPrice = $item->purchase->sell_price !== null ? (float) $item->purchase->sell_price : null;
        }
        if ($sellPrice === null && $item->stock_id && $item->product_id) {
            $purchase = Purchase::where('stock_id', $item->stock_id)
                ->where('product_id', $item->product_id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->first();
            $sellPrice = $purchase ? (float) $purchase->sell_price : null;
        }
        if ($sellPrice === null && $item->product) {
            $sellPrice = $item->product->price > 0 ? (float) $item->product->price : null;
        }
        $sellPrice = $sellPrice ?? 0.0;

        // Purchase (cost) price for reference
        $purchasePrice = null;
        if ($item->purchase_id && $item->purchase) {
            $purchasePrice = (float) $item->purchase->unit_price;
        }
        if ($purchasePrice === null && $item->stock_id && $item->product_id) {
            $purchase = Purchase::where('stock_id', $item->stock_id)
                ->where('product_id', $item->product_id)
                ->latest('date')
                ->first();
            $purchasePrice = $purchase ? (float) $purchase->unit_price : null;
        }
        if ($purchasePrice === null && $item->product) {
            $purchasePrice = (float) $item->product->price;
        }
        $purchasePrice = $purchasePrice ?? 0.0;

        return response()->json([
            'data' => [
                'id' => $item->id,
                'imei_number' => $item->imei_number,
                'model' => $item->model,
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'stock_id' => $stockId,
                'stock_name' => $stockName,
                'sell_price' => $sellPrice,
                'purchase_price' => $purchasePrice,
                'product_id' => $item->product_id,
            ],
        ]);
    }

    /**
     * Agent: Record sale for one device (by product_list id), enter customer info. Deducts from stock.
     *
     * If payment_option_id is supplied and is NOT a Watu channel, an AgentSale is created immediately
     * (visible to both admin and agent without any pending/admin step).
     * If no payment_option_id is supplied, a PendingSale is created for admin to assign a channel.
     */
    public function sell(Request $request)
    {
        $this->ensureTenantContext();

        $rules = [
            'product_list_id'   => 'required|exists:product_list,id',
            'customer_name'     => 'required|string|max:255',
            'selling_price'     => 'required|numeric|min:0',
            'payment_option_id' => 'nullable|exists:payment_options,id',
        ];

        $validated = $request->validate($rules);

        $item = ProductListItem::with(['category', 'product'])->findOrFail($validated['product_list_id']);

        if ($item->isSold()) {
            return response()->json([
                'message' => 'This device is not in stock or has already been sold. Only purchased devices still in stock can be sold.',
            ], 422);
        }

        $agent = Auth::user();

        if (! AgentProductListAssignment::where('agent_id', $agent->id)->where('product_list_id', $item->id)->exists()) {
            return response()->json([
                'message' => 'This device is not assigned to you. Only devices assigned by admin can be sold.',
            ], 403);
        }

        if (app(AgentProductTransferService::class)->isProductListLockedForSale((int) $item->id, (int) $agent->id)) {
            return response()->json([
                'message' => 'This device is in a pending transfer and cannot be sold.',
            ], 422);
        }

        $sellingPrice = (float) $validated['selling_price'];
        $minimumSellPrice = $this->resolveMinimumAllowedSellPrice($item);
        if ($sellingPrice + 0.0001 < $minimumSellPrice) {
            return response()->json([
                'message' => 'Selling price cannot be lower than ' . number_format($minimumSellPrice, 2) . '.',
            ], 422);
        }

        $product = $item->product;
        if (! $product) {
            $product = Product::firstOrCreate(
                ['category_id' => $item->category_id, 'name' => $item->model],
                ['price' => 0, 'stock_quantity' => 0, 'rating' => 5.0, 'description' => 'From product list', 'images' => []]
            );
            $item->update(['product_id' => $product->id]);
        }

        $paymentOptId = isset($validated['payment_option_id']) ? (int) $validated['payment_option_id'] : null;
        $paymentOpt = $paymentOptId ? PaymentOption::visible()->find($paymentOptId) : null;

        if ($paymentOptId && ! $paymentOpt) {
            return response()->json(['message' => 'Selected payment channel is invalid or not available.'], 422);
        }

        // Sale tab should always finalize into AgentSale.
        // If channel is missing, use default regular sale channel from settings, then fallback to first visible non-Watu channel.
        if (! $paymentOpt) {
            $defaultSaleChannelRaw = Setting::query()->where('key', 'default_agent_sale_channel_id')->value('value');
            $defaultSaleChannelId = is_numeric($defaultSaleChannelRaw) ? (int) $defaultSaleChannelRaw : null;

            $paymentOpt = $defaultSaleChannelId
                ? PaymentOption::visible()->find($defaultSaleChannelId)
                : null;

            if (! $paymentOpt || $paymentOpt->isWatuAgentCreditChannel()) {
                $paymentOpt = PaymentOption::visible()
                    ->orderBy('name')
                    ->get()
                    ->first(fn ($opt) => ! $opt->isWatuAgentCreditChannel());
            }
        }

        if (! $paymentOpt) {
            return response()->json([
                'message' => 'No regular sale payment channel is configured. Set default regular sale channel in Settings.',
            ], 422);
        }

        $sale = $this->createDirectAgentSale(
            $item, $product, $agent,
            $validated['customer_name'],
            $sellingPrice,
            $paymentOpt
        );

        return response()->json([
            'message' => 'Sale recorded successfully.',
            'data' => [
                'agent_sale_id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'selling_price' => $sale->selling_price,
            ],
        ], 201);
    }

    /**
     * Agent: Sell device on credit (loan to customer). Creates agent_credits row; optional down payment.
     */
    public function sellCredit(Request $request)
    {
        $this->ensureTenantContext();

        $rules = [
            'product_list_id' => 'required|exists:product_list,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:64',
            'kin_name' => 'nullable|string|max:255',
            'kin_phone' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:2000',
            'selling_price' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:0',
            'installment_amount' => 'nullable|numeric|min:0',
            'first_due_date' => 'nullable|date',
            'installment_notes' => 'nullable|string|max:2000',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('agent_credits', 'installment_interval_days')) {
            $rules['installment_interval_days'] = 'nullable|integer|min:1|max:3650';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('payment_options')) {
            $rules['payment_option_id'] = 'nullable|exists:payment_options,id';
        } else {
            $rules['payment_option_id'] = 'nullable';
        }

        $validated = $request->validate($rules);

        $item = ProductListItem::with(['category', 'product'])->findOrFail($validated['product_list_id']);

        if ($item->isSold()) {
            return response()->json([
                'message' => 'This device is not in stock or has already been sold.',
            ], 422);
        }

        $agent = Auth::user();

        if (! AgentProductListAssignment::where('agent_id', $agent->id)->where('product_list_id', $item->id)->exists()) {
            return response()->json([
                'message' => 'This device is not assigned to you. Only devices assigned by admin can be sold.',
            ], 403);
        }

        if (app(AgentProductTransferService::class)->isProductListLockedForSale((int) $item->id, (int) $agent->id)) {
            return response()->json([
                'message' => 'This device is in a pending transfer and cannot be sold on credit.',
            ], 422);
        }

        $minimumSellPrice = $this->resolveMinimumAllowedSellPrice($item);
        $requestedSellingPrice = (float) $validated['selling_price'];
        if ($requestedSellingPrice + 0.0001 < $minimumSellPrice) {
            return response()->json([
                'message' => 'Selling price cannot be lower than ' . number_format($minimumSellPrice, 2) . '.',
            ], 422);
        }

        $product = $item->product;
        if (!$product) {
            $product = Product::firstOrCreate(
                [
                    'category_id' => $item->category_id,
                    'name' => $item->model,
                ],
                [
                    'price' => 0,
                    'stock_quantity' => 0,
                    'rating' => 5.0,
                    'description' => 'From product list',
                    'images' => [],
                ]
            );
            $item->update(['product_id' => $product->id]);
        }

        $totalCredit = $requestedSellingPrice;
        $down = (float) ($validated['down_payment'] ?? 0);
        if ($down > $totalCredit + 0.0001) {
            return response()->json([
                'message' => 'Down payment cannot exceed total credit amount.',
            ], 422);
        }

        $eps = 0.0001;

        // Use the channel submitted from the app if provided
        $paymentOptionId = isset($validated['payment_option_id']) ? (int) $validated['payment_option_id'] : null;

        // If no channel submitted, fall back to default Watu channel
        if ($paymentOptionId === null) {
            $watuDefaultRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
            if (is_numeric($watuDefaultRaw)) {
                $candidateId = (int) $watuDefaultRaw;
                $candidate = PaymentOption::visible()->find($candidateId);
                if ($candidate) {
                    $paymentOptionId = $candidate->id;
                }
            }

            if ($paymentOptionId === null) {
                $fallbackWatu = PaymentOption::visible()
                    ->orderBy('name')
                    ->get()
                    ->first(fn ($opt) => $opt->isWatuAgentCreditChannel());
                if ($fallbackWatu) {
                    $paymentOptionId = $fallbackWatu->id;
                }
            }
        }

        if ($down > $eps && $paymentOptionId) {
            $opt = PaymentOption::find($paymentOptionId);
            if (!$opt || $opt->balance + $eps < $down) {
                return response()->json([
                    'message' => 'Insufficient balance in selected payment channel for down payment.',
                ], 422);
            }
        }

        $paymentStatus = $down >= $totalCredit - $eps ? 'paid' : ($down > $eps ? 'partial' : 'pending');

        $notes = $validated['description'] ?? $validated['installment_notes'] ?? null;

        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($product->id);

        $credit = DB::transaction(function () use ($item, $product, $validated, $totalCredit, $down, $paymentStatus, $paymentOptionId, $agent, $notes, $eps, $buyPrice) {
            $creditAttrs = [
                'agent_id' => $agent->id,
                'customer_name' => $validated['customer_name'],
                'product_list_id' => $item->id,
                'product_id' => $product->id,
                'total_amount' => $totalCredit,
                'paid_amount' => min($totalCredit, $down),
                'payment_status' => $paymentStatus,
                'payment_option_id' => $paymentOptionId,
                'installment_count' => $validated['installment_count'] ?? null,
                'installment_amount' => isset($validated['installment_amount']) ? (float) $validated['installment_amount'] : null,
                'first_due_date' => $validated['first_due_date'] ?? null,
                'installment_notes' => $notes,
                'date' => now()->toDateString(),
                'paid_date' => $down > $eps ? now()->toDateString() : null,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('agent_credits', 'customer_phone')) {
                $phone = isset($validated['customer_phone']) ? trim((string) $validated['customer_phone']) : '';
                $creditAttrs['customer_phone'] = $phone !== '' ? $phone : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('agent_credits', 'kin_name')) {
                $kinName = isset($validated['kin_name']) ? trim((string) $validated['kin_name']) : '';
                $creditAttrs['kin_name'] = $kinName !== '' ? $kinName : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('agent_credits', 'kin_phone')) {
                $kinPhone = isset($validated['kin_phone']) ? trim((string) $validated['kin_phone']) : '';
                $creditAttrs['kin_phone'] = $kinPhone !== '' ? $kinPhone : null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('agent_credits', 'installment_interval_days')) {
                $creditAttrs['installment_interval_days'] = isset($validated['installment_interval_days'])
                    ? (int) $validated['installment_interval_days']
                    : null;
            }
            if (Schema::hasColumn('agent_credits', 'purchase_price')) {
                $creditAttrs['purchase_price'] = $buyPrice;
                $creditAttrs['selling_price'] = $totalCredit;
                $creditAttrs['profit'] = $totalCredit - $buyPrice;
            }
            $creditAttrs = app(\App\Services\DefaultAgentCommissionService::class)
                ->applyToCreateAttrs($creditAttrs, 'agent_credits', 1);
            $credit = AgentCredit::create($creditAttrs);

            if ($down > $eps && $paymentOptionId) {
                $opt = PaymentOption::find($paymentOptionId);
                if ($opt) {
                    $opt->decrement('balance', min($down, $totalCredit));
                }
            }

            if ($down > $eps) {
                AgentCreditPayment::create([
                    'agent_credit_id' => $credit->id,
                    'payment_option_id' => $paymentOptionId,
                    'amount' => min($down, $totalCredit),
                    'paid_date' => now()->toDateString(),
                ]);
            }

            $item->update([
                'sold_at' => now(),
                'agent_credit_id' => $credit->id,
                'pending_sale_id' => null,
            ]);

            $product->decrement('stock_quantity');

            AgentProductListAssignment::where('product_list_id', $item->id)->delete();
            AgentAssignment::where('agent_id', $agent->id)
                ->where('product_id', $product->id)
                ->where('assignment_type', AgentAssignment::TYPE_IMEI)
                ->increment('quantity_sold');

            return $credit;
        });

        return response()->json([
            'message' => 'Credit sale recorded.',
            'data' => [
                'agent_credit_id' => $credit->id,
                'customer_name' => $credit->customer_name,
                'total_amount' => (float) $credit->total_amount,
                'paid_amount' => (float) $credit->paid_amount,
                'payment_status' => $credit->payment_status,
            ],
        ], 201);
    }

    /**
     * Agent: List "total" (quantity-only) assignments for the Given tab.
     * Returns one row per assigned product with the remaining quantity and a
     * suggested sell price taken from the product catalog.
     */
    public function totalAssignments()
    {
        $this->ensureTenantContext();

        $agentId = Auth::id();

        $rows = AgentAssignment::where('agent_id', $agentId)
            ->where('assignment_type', AgentAssignment::TYPE_TOTAL)
            ->with(['product.category', 'purchase'])
            ->get();

        $data = $rows->map(function (AgentAssignment $row) {
            $product = $row->product;
            $sellPrice = 0.0;
            if ($product) {
                if ($product->price !== null && (float) $product->price > 0) {
                    $sellPrice = (float) $product->price;
                } else {
                    $purchase = Purchase::where('product_id', $product->id)
                        ->whereNotNull('sell_price')
                        ->latest('date')
                        ->first();
                    if ($purchase && $purchase->sell_price !== null) {
                        $sellPrice = (float) $purchase->sell_price;
                    }
                }
            }

            $assigned = (int) $row->quantity_assigned;
            $sold = (int) $row->quantity_sold;
            $remaining = max(0, $assigned - $sold);

            return [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'purchase_id' => $row->purchase_id,
                'purchase_name' => $row->purchase?->name,
                'product_name' => $product?->name ?? '–',
                'category_id' => $product?->category_id,
                'category_name' => $product?->category?->name,
                'quantity_assigned' => $assigned,
                'quantity_sold' => $sold,
                'quantity_remaining' => $remaining,
                'sell_price' => $sellPrice,
            ];
        })
            ->filter(fn (array $r) => $r['quantity_remaining'] > 0)
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Agent: resolve scanned IMEI to a remaining total assignment.
     */
    public function totalAssignmentByImei(string $imei)
    {
        $this->ensureTenantContext();

        $agent = Auth::user();
        $imei = trim($imei);
        if ($imei === '') {
            return response()->json(['message' => 'IMEI is required.'], 422);
        }

        $item = ProductListItem::query()
            ->with(['product', 'purchase'])
            ->where('imei_number', $imei)
            ->first();

        if (! $item || ! $item->product_id) {
            return response()->json([
                'message' => 'Scanned IMEI is not linked to an assigned product.',
            ], 422);
        }

        $query = AgentAssignment::query()
            ->where('agent_id', $agent->id)
            ->where('assignment_type', AgentAssignment::TYPE_TOTAL)
            ->where('product_id', (int) $item->product_id)
            ->whereRaw('(quantity_assigned - quantity_sold) > 0')
            ->with(['product', 'purchase']);

        if ($item->purchase_id) {
            $query->orderByRaw('CASE WHEN purchase_id = ? THEN 0 ELSE 1 END', [(int) $item->purchase_id]);
        }

        $assignment = $query
            ->orderBy('id')
            ->first();

        if (! $assignment) {
            return response()->json([
                'message' => 'No remaining total assignment matches this IMEI.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'imei' => $imei,
                'assignment_id' => $assignment->id,
                'product_id' => $assignment->product_id,
                'purchase_assigned' => $assignment->purchase?->name ?? ($item->purchase?->name ?? 'Unspecified purchase'),
                'model' => $assignment->product?->name ?? ($item->model ?? '—'),
                'remaining_total' => max(0, (int) $assignment->quantity_assigned - (int) $assignment->quantity_sold),
            ],
        ]);
    }

    /**
     * Agent: Record a "Given" sale (from a quantity-only assignment).
     *
     * Flow:
     *  - Validate the agent has a TYPE_TOTAL assignment for $product_id with quantity_remaining >= 1.
     *  - Look up product_list by imei. If found and unsold, reuse it. If found and sold, fail.
     *    If not found, create a new product_list row tied to the catalog product so the IMEI is trackable.
     *  - Create the AgentSale, mark the product_list row as sold, increment the TYPE_TOTAL counter,
     *    and credit the chosen payment channel.
     */
    public function sellGiven(Request $request)
    {
        $this->ensureTenantContext();

        $validated = $request->validate([
            'imei' => 'required|string|max:512',
        ]);

        $agent = Auth::user();
        $imei = trim($validated['imei']);

        if ($imei === '') {
            return response()->json(['message' => 'IMEI is required.'], 422);
        }

        $existing = ProductListItem::where('imei_number', $imei)->first();
        if (! $existing || ! $existing->product_id) {
            return response()->json([
                'message' => 'This IMEI is not linked to a product in stock.',
            ], 422);
        }

        $productId = (int) $existing->product_id;

        $assignmentQuery = AgentAssignment::where('agent_id', $agent->id)
            ->where('assignment_type', AgentAssignment::TYPE_TOTAL)
            ->where('product_id', $productId)
            ->whereRaw('(quantity_assigned - quantity_sold) > 0');

        if ($existing->purchase_id) {
            $assignmentQuery->orderByRaw('CASE WHEN purchase_id = ? THEN 0 ELSE 1 END', [(int) $existing->purchase_id]);
        }

        $assignment = $assignmentQuery->orderBy('id')->first();

        if (! $assignment) {
            return response()->json([
                'message' => 'You do not have a total assignment for this product. Ask admin to assign by total.',
            ], 422);
        }

        if ($assignment->quantity_remaining < 1) {
            return response()->json([
                'message' => 'You have already sold all assigned units of this product.',
            ], 422);
        }

        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['message' => 'Selected product no longer exists.'], 422);
        }

        if ($existing->isSold()) {
            return response()->json([
                'message' => 'This IMEI has already been sold.',
            ], 422);
        }

        $otherAssignment = AgentProductListAssignment::where('product_list_id', $existing->id)
            ->where('agent_id', '!=', $agent->id)
            ->exists();
        if ($otherAssignment) {
            return response()->json([
                'message' => 'This IMEI is currently assigned to another agent.',
            ], 422);
        }

        $paymentOpt = PaymentOption::visible()
            ->orderBy('name')
            ->get()
            ->first(fn ($opt) => ! $opt->isWatuAgentCreditChannel());
        if (! $paymentOpt) {
            return response()->json([
                'message' => 'No regular payment channel is configured.',
            ], 422);
        }

        $sellingPrice = null;
        if ($assignment->purchase_id) {
            $assignedPurchase = Purchase::find($assignment->purchase_id);
            if ($assignedPurchase && $assignedPurchase->sell_price !== null) {
                $sellingPrice = (float) $assignedPurchase->sell_price;
            }
        }
        if ($sellingPrice === null && $existing->purchase_id && $existing->purchase && $existing->purchase->sell_price !== null) {
            $sellingPrice = (float) $existing->purchase->sell_price;
        }
        if ($sellingPrice === null) {
            $sellingPrice = (float) ($product->price ?? 0);
        }

        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($product->id);
        $profit = $sellingPrice - $buyPrice;

        try {
            $sale = DB::transaction(function () use ($agent, $product, $assignment, $existing, $validated, $sellingPrice, $buyPrice, $profit, $paymentOpt) {
                $item = $existing;

                $attrs = [
                    'customer_name' => 'Assigned agent sale',
                    'seller_name' => $agent->name,
                    'product_id' => $product->id,
                    'quantity_sold' => 1,
                    'purchase_price' => $buyPrice,
                    'selling_price' => $sellingPrice,
                    'total_purchase_value' => $buyPrice,
                    'total_selling_value' => $sellingPrice,
                    'profit' => $profit,
                    'balance' => 0,
                    'date' => now()->toDateString(),
                ];
                if (Schema::hasColumn('agent_sales', 'agent_id')) {
                    $attrs['agent_id'] = $agent->id;
                }
                if (Schema::hasColumn('agent_sales', 'payment_option_id')) {
                    $attrs['payment_option_id'] = $paymentOpt->id;
                }

                $attrs = app(\App\Services\DefaultAgentCommissionService::class)
                    ->applyToCreateAttrs($attrs, 'agent_sales', 1);
                $sale = AgentSale::create($attrs);

                $paymentOpt->increment('balance', $sellingPrice);

                $item->update([
                    'sold_at' => now(),
                    'agent_sale_id' => $sale->id,
                    'pending_sale_id' => null,
                    'agent_credit_id' => null,
                ]);

                AgentProductListAssignment::where('product_list_id', $item->id)->delete();

                $product->decrement('stock_quantity');

                $assignment->increment('quantity_sold');

                return $sale;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not record sale: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Given sale recorded successfully.',
            'data' => [
                'agent_sale_id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'selling_price' => (float) $sale->selling_price,
                'imei_number' => $imei,
            ],
        ], 201);
    }

    /**
     * Routing decision for sell-credit:
     *  - Watu channel                           → AgentCredit (loan with Watu)
     *  - No channel + installments or partial   → AgentCredit (unfinanced loan)
     *  - Non-Watu channel                       → AgentSale directly (handled by caller)
     *  - No channel + fully paid                → PendingSale (admin assigns channel)
     */
    private function shouldCreateAgentCredit(?PaymentOption $opt, float $totalCredit, float $down, array $validated): bool
    {
        if ($opt !== null && $opt->isWatuAgentCreditChannel()) {
            return true;
        }

        $eps          = 0.0001;
        $installments = (int) ($validated['installment_count'] ?? 0);

        if ($opt === null && ($installments > 0 || $down + $eps < $totalCredit)) {
            return true;
        }

        return false;
    }

    /**
     * Non-Watu channel: create an AgentSale record immediately.
     * The payment option balance is incremented (income from sale).
     * The product_list item gets agent_sale_id so the inventory endpoint classifies it as a sale.
     */
    private function createDirectAgentSale(
        ProductListItem $item,
        Product         $product,
        User            $agent,
        string          $customerName,
        float           $sellingPrice,
        PaymentOption   $paymentOpt
    ): AgentSale {
        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($product->id);
        $profit   = $sellingPrice - $buyPrice;

        return DB::transaction(function () use ($item, $product, $agent, $customerName, $sellingPrice, $buyPrice, $profit, $paymentOpt) {
            $attrs = [
                'customer_name'        => $customerName,
                'seller_name'          => $agent->name,
                'product_id'           => $product->id,
                'quantity_sold'        => 1,
                'purchase_price'       => $buyPrice,
                'selling_price'        => $sellingPrice,
                'total_purchase_value' => $buyPrice,
                'total_selling_value'  => $sellingPrice,
                'profit'               => $profit,
                'balance'              => 0,
                'date'                 => now()->toDateString(),
            ];

            if (Schema::hasColumn('agent_sales', 'agent_id')) {
                $attrs['agent_id'] = $agent->id;
            }
            if (Schema::hasColumn('agent_sales', 'payment_option_id')) {
                $attrs['payment_option_id'] = $paymentOpt->id;
            }

            $attrs = app(\App\Services\DefaultAgentCommissionService::class)
                ->applyToCreateAttrs($attrs, 'agent_sales', 1);
            $sale = AgentSale::create($attrs);

            // Record the incoming cash/channel amount
            $paymentOpt->increment('balance', $sellingPrice);

            $item->update([
                'sold_at'         => now(),
                'agent_sale_id'   => $sale->id,
                'pending_sale_id' => null,
                'agent_credit_id' => null,
            ]);

            $product->decrement('stock_quantity');

            AgentProductListAssignment::where('product_list_id', $item->id)->delete();
            AgentAssignment::where('agent_id', $agent->id)
                ->where('product_id', $product->id)
                ->where('assignment_type', AgentAssignment::TYPE_IMEI)
                ->increment('quantity_sold');

            return $sale;
        });
    }

    /**
     * No channel selected: create a pending_sales row (admin assigns channel → moves to agent_sales).
     */
    private function createPendingAgentSaleForDevice(
        ProductListItem $item,
        Product $product,
        User $agent,
        string $customerName,
        float $sellingPrice
    ): PendingSale {
        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($product->id);
        $totalSell = $sellingPrice;
        $totalBuy = $buyPrice * 1;
        $profit = $totalSell - $totalBuy;

        return DB::transaction(function () use ($item, $product, $agent, $customerName, $sellingPrice, $buyPrice, $totalSell, $totalBuy, $profit) {
            $pendingAttrs = [
                'customer_name' => $customerName,
                'seller_name' => $agent->name,
                'product_id' => $product->id,
                'quantity_sold' => 1,
                'purchase_price' => $buyPrice,
                'selling_price' => $sellingPrice,
                'total_purchase_value' => $totalBuy,
                'total_selling_value' => $totalSell,
                'profit' => $profit,
                'date' => now()->toDateString(),
            ];
            if (Schema::hasColumn('pending_sales', 'seller_id')) {
                $pendingAttrs['seller_id'] = $agent->id;
            }
            $sale = PendingSale::create($pendingAttrs);

            $item->update([
                'sold_at' => now(),
                'pending_sale_id' => $sale->id,
                'agent_credit_id' => null,
            ]);

            $product->decrement('stock_quantity');

            AgentProductListAssignment::where('product_list_id', $item->id)->delete();
            AgentAssignment::where('agent_id', $agent->id)
                ->where('product_id', $product->id)
                ->where('assignment_type', AgentAssignment::TYPE_IMEI)
                ->increment('quantity_sold');

            return $sale;
        });
    }

    /**
     * Minimum sell price allowed for an item:
     * purchase sell_price -> latest stock/product sell_price -> product price.
     */
    private function resolveMinimumAllowedSellPrice(ProductListItem $item): float
    {
        $sellPrice = null;

        if ($item->purchase_id) {
            $purchase = $item->relationLoaded('purchase')
                ? $item->purchase
                : Purchase::find($item->purchase_id);
            if ($purchase && $purchase->sell_price !== null) {
                $sellPrice = (float) $purchase->sell_price;
            }
        }

        if ($sellPrice === null && $item->stock_id && $item->product_id) {
            $purchase = Purchase::where('stock_id', $item->stock_id)
                ->where('product_id', $item->product_id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->first();
            if ($purchase) {
                $sellPrice = (float) $purchase->sell_price;
            }
        }

        if ($sellPrice === null && $item->product_id) {
            $product = $item->relationLoaded('product')
                ? $item->product
                : Product::find($item->product_id);
            if ($product && (float) $product->price > 0) {
                $sellPrice = (float) $product->price;
            }
        }

        return max(0, (float) ($sellPrice ?? 0));
    }
}
