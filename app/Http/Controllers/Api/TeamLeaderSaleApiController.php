<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCredit;
use App\Models\AgentCreditPayment;
use App\Models\AgentSale;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\TeamLeaderProductListAssignment;
use App\Models\User;
use App\Services\DistributionSaleService;
use App\Services\TeamLeaderProductTransferService;
use App\Support\PdfDownload;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeamLeaderSaleApiController extends Controller
{
    public function available(): JsonResponse
    {
        $teamLeaderId = (int) Auth::id();
        $assignedIds = TeamLeaderProductListAssignment::query()
            ->where('team_leader_id', $teamLeaderId)
            ->pluck('product_list_id');

        $items = ProductListItem::with(['category', 'product', 'stock', 'purchase'])
            ->whereIn('id', $assignedIds)
            ->whereNull('sold_at')
            ->orderBy('model')
            ->orderBy('imei_number')
            ->get();

        $data = $items->map(fn (ProductListItem $item) => $this->serializeAvailableItem($item))->values()->all();

        return response()->json(['data' => $data]);
    }

    public function showByImei(string $imei): JsonResponse
    {
        $teamLeaderId = (int) Auth::id();

        $item = ProductListItem::with(['category', 'product', 'stock', 'purchase'])
            ->where('imei_number', $imei)
            ->whereNull('sold_at')
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'This device is not in stock or has already been sold.',
            ], 404);
        }

        if (! $this->teamLeaderOwnsItem($teamLeaderId, (int) $item->id)) {
            return response()->json([
                'message' => 'This device is not assigned to you.',
            ], 404);
        }

        return response()->json(['data' => $this->serializeAvailableItem($item)]);
    }

    public function sellCredit(Request $request): JsonResponse
    {
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
        if (Schema::hasColumn('agent_credits', 'installment_interval_days')) {
            $rules['installment_interval_days'] = 'nullable|integer|min:1|max:3650';
        }
        if (Schema::hasTable('payment_options')) {
            $rules['payment_option_id'] = 'nullable|exists:payment_options,id';
        }

        $validated = $request->validate($rules);

        $item = ProductListItem::with(['category', 'product'])->findOrFail($validated['product_list_id']);

        if ($item->isSold()) {
            return response()->json(['message' => 'This device is not in stock or has already been sold.'], 422);
        }

        $teamLeader = Auth::user();

        if (! $this->teamLeaderOwnsItem((int) $teamLeader->id, (int) $item->id)) {
            return response()->json(['message' => 'This device is not assigned to you.'], 403);
        }

        if (app(TeamLeaderProductTransferService::class)->isProductListInAnyPendingTransfer((int) $item->id)) {
            return response()->json(['message' => 'This device is in a pending transfer and cannot be sold on credit.'], 422);
        }

        $minimumSellPrice = $this->resolveMinimumAllowedSellPrice($item);
        $requestedSellingPrice = (float) $validated['selling_price'];
        if ($requestedSellingPrice + 0.0001 < $minimumSellPrice) {
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

        $totalCredit = $requestedSellingPrice;
        $down = (float) ($validated['down_payment'] ?? 0);
        if ($down > $totalCredit + 0.0001) {
            return response()->json(['message' => 'Down payment cannot exceed total credit amount.'], 422);
        }

        $eps = 0.0001;
        $paymentOptionId = isset($validated['payment_option_id']) ? (int) $validated['payment_option_id'] : null;

        if ($paymentOptionId === null) {
            $watuDefaultRaw = Setting::query()->where('key', 'default_watu_channel_id')->value('value');
            if (is_numeric($watuDefaultRaw)) {
                $candidate = PaymentOption::visible()->find((int) $watuDefaultRaw);
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
            if (! $opt || $opt->balance + $eps < $down) {
                return response()->json(['message' => 'Insufficient balance in selected payment channel for down payment.'], 422);
            }
        }

        $paymentStatus = $down >= $totalCredit - $eps ? 'paid' : ($down > $eps ? 'partial' : 'pending');
        $notes = $validated['description'] ?? $validated['installment_notes'] ?? null;
        $buyPrice = app(DistributionSaleService::class)->getBuyPriceForProduct($product->id);

        $credit = DB::transaction(function () use ($item, $product, $validated, $totalCredit, $down, $paymentStatus, $paymentOptionId, $teamLeader, $notes, $eps, $buyPrice) {
            $creditAttrs = [
                'agent_id' => null,
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

            if (Schema::hasColumn('agent_credits', 'team_leader_id')) {
                $creditAttrs['team_leader_id'] = $teamLeader->id;
            }
            if (Schema::hasColumn('agent_credits', 'customer_phone')) {
                $phone = isset($validated['customer_phone']) ? trim((string) $validated['customer_phone']) : '';
                $creditAttrs['customer_phone'] = $phone !== '' ? $phone : null;
            }
            if (Schema::hasColumn('agent_credits', 'kin_name')) {
                $kinName = isset($validated['kin_name']) ? trim((string) $validated['kin_name']) : '';
                $creditAttrs['kin_name'] = $kinName !== '' ? $kinName : null;
            }
            if (Schema::hasColumn('agent_credits', 'kin_phone')) {
                $kinPhone = isset($validated['kin_phone']) ? trim((string) $validated['kin_phone']) : '';
                $creditAttrs['kin_phone'] = $kinPhone !== '' ? $kinPhone : null;
            }
            if (Schema::hasColumn('agent_credits', 'installment_interval_days')) {
                $creditAttrs['installment_interval_days'] = isset($validated['installment_interval_days'])
                    ? (int) $validated['installment_interval_days']
                    : null;
            }
            if (Schema::hasColumn('agent_credits', 'purchase_price')) {
                $creditAttrs['purchase_price'] = $buyPrice;
                $creditAttrs['selling_price'] = $totalCredit;
                $creditAttrs['profit'] = $totalCredit - $buyPrice;
            }

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
            TeamLeaderProductListAssignment::where('product_list_id', $item->id)->delete();

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

    public function credits(): JsonResponse
    {
        $teamLeaderId = (int) Auth::id();

        $query = AgentCredit::query()
            ->where('team_leader_id', $teamLeaderId)
            ->with(['product.category', 'productListItem'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        $credits = $query->get()->map(function (AgentCredit $credit) {
            $total = (float) $credit->total_amount;
            $paid = (float) ($credit->paid_amount ?? 0);
            $product = $credit->product;
            $label = $product
                ? (($product->category?->name ?? '—') . ' – ' . $product->name)
                : '—';

            return [
                'id' => $credit->id,
                'customer_name' => $credit->customer_name,
                'customer_phone' => Schema::hasColumn('agent_credits', 'customer_phone') ? $credit->customer_phone : null,
                'kin_name' => Schema::hasColumn('agent_credits', 'kin_name') ? $credit->kin_name : null,
                'kin_phone' => Schema::hasColumn('agent_credits', 'kin_phone') ? $credit->kin_phone : null,
                'description' => $credit->installment_notes,
                'date' => $credit->date instanceof Carbon ? $credit->date->format('Y-m-d') : (string) $credit->date,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining' => max(0, $total - $paid),
                'payment_status' => $credit->payment_status,
                'product_label' => $label,
                'imei_number' => $credit->productListItem?->imei_number,
                'installment_count' => $credit->installment_count,
                'installment_amount' => $credit->installment_amount !== null ? (float) $credit->installment_amount : null,
                'first_due_date' => $credit->first_due_date instanceof Carbon
                    ? $credit->first_due_date->format('Y-m-d')
                    : ($credit->first_due_date ? (string) $credit->first_due_date : null),
                'invoice_available' => true,
                'invoice_endpoint' => '/team-leader/credits/' . $credit->id . '/invoice',
            ];
        });

        return response()->json(['data' => $credits]);
    }

    public function creditDetail(int $id): JsonResponse
    {
        $credit = AgentCredit::query()
            ->where('team_leader_id', Auth::id())
            ->with(['product.category', 'productListItem', 'paymentOption'])
            ->findOrFail($id);

        $total = (float) $credit->total_amount;
        $paid = (float) ($credit->paid_amount ?? 0);

        return response()->json([
            'data' => [
                'id' => $credit->id,
                'customer_name' => $credit->customer_name,
                'customer_phone' => $credit->customer_phone,
                'kin_name' => $credit->kin_name,
                'kin_phone' => $credit->kin_phone,
                'description' => $credit->installment_notes,
                'product_label' => $credit->product
                    ? (($credit->product->category?->name ?? '—') . ' – ' . $credit->product->name)
                    : '—',
                'imei_number' => $credit->productListItem?->imei_number,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining' => max(0, $total - $paid),
                'payment_status' => $credit->payment_status,
                'payment_option' => $credit->paymentOption?->name,
                'date' => $credit->date instanceof Carbon ? $credit->date->format('Y-m-d') : (string) $credit->date,
                'invoice_endpoint' => '/team-leader/credits/' . $credit->id . '/invoice',
            ],
        ]);
    }

    public function downloadInvoice(int $id)
    {
        $credit = AgentCredit::query()
            ->where('team_leader_id', Auth::id())
            ->with(['product.category', 'productListItem'])
            ->findOrFail($id);

        $invoiceNo = 'AC-' . str_pad((string) $credit->id, 6, '0', STR_PAD_LEFT);
        $invoiceDate = $credit->paid_date ?? $credit->date ?? now();
        $filename = 'agent-credit-invoice-' . strtolower($invoiceNo) . '-' . $invoiceDate->format('Ymd') . '.pdf';

        return PdfDownload::fromView('admin.stock.receipt-invoice', [
            'credit' => $credit,
            'invoiceNo' => $invoiceNo,
            'invoiceDate' => $invoiceDate,
            'title' => 'RECEIPT',
        ], $filename);
    }

    public function sales(): JsonResponse
    {
        $teamLeaderId = (int) Auth::id();

        if (! Schema::hasColumn('agent_sales', 'team_leader_id')) {
            return response()->json(['data' => []]);
        }

        $sales = AgentSale::query()
            ->where('team_leader_id', $teamLeaderId)
            ->with(['product.category', 'paymentOption', 'productListItem'])
            ->latest('date')
            ->latest('id')
            ->take(100)
            ->get()
            ->map(fn (AgentSale $sale) => [
                'record_type' => 'agent_sale',
                'id' => $sale->id,
                'customer_name' => $sale->customer_name ?? '–',
                'product_name' => $sale->product?->name ?? '–',
                'category_name' => $sale->product?->category?->name ?? '–',
                'imei_number' => $sale->productListItem?->imei_number,
                'quantity_sold' => (int) ($sale->quantity_sold ?? 0),
                'selling_price' => (float) ($sale->selling_price ?? 0),
                'total_selling_value' => (float) ($sale->total_selling_value ?? 0),
                'profit' => (float) ($sale->profit ?? 0),
                'payment_option' => $sale->paymentOption?->name,
                'date' => $sale->date ? (is_string($sale->date) ? Carbon::parse($sale->date)->toISOString() : $sale->date->toISOString()) : null,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $sales]);
    }

    private function teamLeaderOwnsItem(int $teamLeaderId, int $productListId): bool
    {
        return TeamLeaderProductListAssignment::query()
            ->where('team_leader_id', $teamLeaderId)
            ->where('product_list_id', $productListId)
            ->exists();
    }

    private function serializeAvailableItem(ProductListItem $item): array
    {
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
    }

    private function resolveMinimumAllowedSellPrice(ProductListItem $item): float
    {
        $sellPrice = null;

        if ($item->purchase_id) {
            $purchase = $item->relationLoaded('purchase') ? $item->purchase : Purchase::find($item->purchase_id);
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
            $product = $item->relationLoaded('product') ? $item->product : Product::find($item->product_id);
            if ($product && (float) $product->price > 0) {
                $sellPrice = (float) $product->price;
            }
        }

        return max(0, (float) ($sellPrice ?? 0));
    }
}
