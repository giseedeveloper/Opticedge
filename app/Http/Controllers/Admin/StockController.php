<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePayment;
use App\Models\AgentSale;
use App\Models\DistributionSale;
use App\Models\DistributionSalePayment;
use App\Models\Expense;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\ProductListItem;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\Setting;
use App\Models\User;
use App\Services\BarcodeImageDecoder;
use App\Support\ImeiListParser;
use App\Support\PdfDownload;
use App\Support\DocumentNumberGenerator;
use App\Support\PurchaseInvoiceNumber;
use App\Services\AgentCommissionExpenseService;
use App\Services\AgentSaleCreditMigrationService;
use App\Services\DistributionSaleService;
use App\Services\PurchaseImeiRegistrationService;
use App\Services\StockSummaryInsightsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StockController extends Controller
{
    /**
     * Stocks page: list all stocks with stock quantity, added (from purchases), and status.
     */
    public function stocks()
    {
        $usingPurchases = false;

        try {
            // Get all stocks
            $stocks = Stock::orderBy('name')->get();
            
            $stocksData = $stocks->map(function ($stock) {
                try {
                    // Added quantity must reflect real devices already entered (IMEI rows),
                    // not purchase target quantity.
                    $added = (int) ProductListItem::where('stock_id', $stock->id)->count();
                } catch (\Exception $e) {
                    Log::warning('Error calculating added quantity for stock ' . $stock->id . ': ' . $e->getMessage());
                    $added = 0;
                }

                $stockQuantity = (int) ($stock->stock_limit ?? 0);
                $status = ($stockQuantity > 0 && $stockQuantity == $added) ? 'complete' : 'pending';

                $imeiCount = $added;
                $unsoldCount = (int) ProductListItem::where('stock_id', $stock->id)->whereNull('sold_at')->count();

                return (object) [
                    'id' => $stock->id,
                    'name' => $stock->name ?? 'Unnamed Stock',
                    'stock_quantity' => $stockQuantity,
                    'added' => (int) $added,
                    'status' => $status,
                    'stock_status' => $imeiCount === 0
                        ? 'pending'
                        : ($unsoldCount > 0 ? 'in_stock' : 'sold_out'),
                    'imei_count' => $imeiCount,
                ];
            });
            
            // If no stocks exist but purchases exist, build rows from purchases instead
            if ($stocksData->isEmpty()) {
                $purchases = Purchase::stockPurchases()->withCount([
                    'productListItems',
                    'productListItems as unsold_items_count' => function ($q) {
                        $q->whereNull('sold_at');
                    },
                ])->orderBy('date', 'desc')->get();

                if ($purchases->isNotEmpty()) {
                    $usingPurchases = true;

                    $stocksData = $purchases->map(function ($purchase) {
                        $limit = (int) ($purchase->quantity ?? 0);
                        $added = (int) ($purchase->product_list_items_count ?? 0);
                        $status = ($limit > 0 && $added === $limit) ? 'complete' : 'pending';
                        $unsoldCount = (int) ($purchase->unsold_items_count ?? 0);

                        return (object) [
                            'id' => $purchase->id,
                            'name' => $purchase->name ?? 'Unnamed Purchase',
                            'stock_quantity' => $limit,
                            'added' => $added,
                            'status' => $status,
                            'stock_status' => $added === 0
                                ? 'pending'
                                : ($unsoldCount > 0 ? 'in_stock' : 'sold_out'),
                            'imei_count' => $added,
                        ];
                    });
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading stocks: ' . $e->getMessage());
            $stocksData = collect([]);
        }

        $stockDashboard = [
            'rows' => $stocksData->count(),
            'total_limit' => (int) $stocksData->sum('stock_quantity'),
            'total_added' => (int) $stocksData->sum('added'),
            'complete' => $stocksData->where('status', 'complete')->count(),
            'pending' => $stocksData->where('status', 'pending')->count(),
        ];

        $stockInsights = app(StockSummaryInsightsService::class)->summaryCounts();

        return view('admin.stock.stocks', [
            'stocks' => $stocksData,
            'hasPurchases' => $usingPurchases,
            'stockDashboard' => $stockDashboard,
            'stockInsights' => $stockInsights,
        ]);
    }

    /**
     * Agents matching aging (no sales in 7/14 days) or low stock (<=2 unsold) filters.
     */
    public function agentStockAlerts(Request $request, StockSummaryInsightsService $insights)
    {
        $filter = (string) $request->query('filter', 'low');
        if (! in_array($filter, ['aging7', 'aging14', 'low'], true)) {
            $filter = 'low';
        }

        $titles = [
            'aging7' => 'Aging stock — no sales in 7 days',
            'aging14' => 'Aging stock — no sales in 14 days',
            'low' => 'Low stock — agents with ≤ 2 devices',
        ];

        $agents = $insights->agentsForFilter($filter);

        return view('admin.stock.agent-stock-alerts', [
            'filter' => $filter,
            'title' => $titles[$filter],
            'agents' => $agents,
            'threshold' => 2,
        ]);
    }

    /**
     * Show items for one purchase: model, category, IMEI (product_list rows for this purchase).
     */
    public function showPurchase($id)
    {
        $purchase = Purchase::with(['lines.product.category', 'product.category'])->findOrFail($id);
        $items = $purchase->productListItems()
            ->with([
                'category:id,name',
                'product:id,name,category_id',
                'stock:id,name',
                'regionalManagerProductListAssignment.regionalManager:id,name,email',
                'teamLeaderProductListAssignment.teamLeader:id,name,email',
                'agentProductListAssignment.agent:id,name,email',
                'agentCredit.agent:id,name,email',
                'agentCredit.paymentOption:id,name',
                'pendingSale',
                'agentSale.agent:id,name,email',
                'distributionSale',
            ])
            ->orderBy('model')
            ->orderBy('imei_number')
            ->paginate(50)
            ->withQueryString();

        return view('admin.stock.purchase-show', [
            'purchase' => $purchase,
            'items' => $items,
        ]);
    }

    /**
     * Delete one IMEI row from a purchase details page.
     */
    public function destroyPurchaseItem(Purchase $purchase, ProductListItem $productListItem)
    {
        if ($purchase->isPassthrough()) {
            abort(404);
        }

        if ((int) $productListItem->purchase_id !== (int) $purchase->id) {
            return redirect()
                ->route('admin.stock.purchase.show', $purchase->id)
                ->withErrors(['error' => 'This IMEI does not belong to the selected purchase.']);
        }

        if ($productListItem->sold_at || $productListItem->agent_sale_id || $productListItem->agent_credit_id || $productListItem->pending_sale_id) {
            return redirect()
                ->route('admin.stock.purchase.show', $purchase->id)
                ->withErrors(['error' => 'Cannot delete IMEI that is already linked to a sale or credit.']);
        }

        if ($productListItem->agentProductListAssignment()->exists()) {
            return redirect()
                ->route('admin.stock.purchase.show', $purchase->id)
                ->withErrors(['error' => 'Cannot delete IMEI that is assigned to an agent.']);
        }

        DB::transaction(function () use ($purchase, $productListItem) {
            DB::table('product_list')->where('id', $productListItem->id)->delete();

            if (Schema::hasColumn('purchases', 'limit_remaining')) {
                if ($purchase->lines()->exists()) {
                    $line = $purchase->lines()->where('product_id', $productListItem->product_id)->first();
                    if ($line) {
                        $next = min((int) $line->quantity, (int) $line->limit_remaining + 1);
                        $line->update(['limit_remaining' => $next]);
                    }
                    $purchase->syncAggregatesFromLines();
                } else {
                    $currentRemaining = (int) ($purchase->limit_remaining ?? 0);
                    $maxLimit = (int) ($purchase->quantity ?? 0);
                    $nextRemaining = $maxLimit > 0
                        ? min($maxLimit, $currentRemaining + 1)
                        : ($currentRemaining + 1);
                    $update = ['limit_remaining' => $nextRemaining];
                    if (Schema::hasColumn('purchases', 'limit_status')) {
                        $update['limit_status'] = $nextRemaining > 0 ? 'pending' : 'complete';
                    }
                    $purchase->update($update);
                }
            }
        });

        return redirect()
            ->route('admin.stock.purchase.show', $purchase->id)
            ->with('success', 'IMEI deleted successfully.');
    }

    /**
     * Show devices (product list items) for a stock: model and IMEI.
     */
    public function showStock(Stock $stock)
    {
        $itemsQuery = ProductListItem::query()
            ->where('stock_id', $stock->id)
            ->with([
                'category',
                'product',
                'purchase',
                'stock:id,name',
                'regionalManagerProductListAssignment.regionalManager:id,name,email',
                'teamLeaderProductListAssignment.teamLeader:id,name,email',
                'agentProductListAssignment.agent:id,name,email',
                'agentCredit.agent:id,name,email',
                'agentCredit.paymentOption:id,name',
                'pendingSale',
                'agentSale.agent:id,name,email',
                'distributionSale',
            ])
            ->orderBy('model')
            ->orderBy('imei_number');

        $available = (clone $itemsQuery)->whereNull('sold_at')->count();
        $atLimit = $available >= $stock->stock_limit;
        $items = $itemsQuery->paginate(50)->withQueryString();

        return view('admin.stock.stock-show', compact('stock', 'atLimit', 'items', 'available'));
    }

    public function purchases(Request $request)
    {
        return $this->purchaseListForType($request, passthrough: false);
    }

    public function exportPurchasesCsv(Request $request)
    {
        $params = $this->resolvePurchaseListParams($request, passthrough: false);
        $purchases = $this->buildPurchaseListQuery($params)->latest('date')->get();
        $filename = 'purchases-' . now()->format('Ymd-His') . '.csv';

        return $this->streamPurchaseListCsv($purchases, $filename);
    }

    /**
     * View all payment receipts for all purchases.
     */
    public function viewAllReceipts()
    {
        $purchases = Purchase::stockPurchases()->with(['product', 'stock'])
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->get();
        
        return view('admin.stock.all-receipts', compact('purchases'));
    }

    /**
     * View payment receipts for a specific stock.
     */
    public function viewStockReceipts(Stock $stock)
    {
        $purchases = Purchase::with(['product'])
            ->where('stock_id', $stock->id)
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->get();
        
        return view('admin.stock.stock-receipts', compact('stock', 'purchases'));
    }

    public function distribution(Request $request)
    {
        $query = DistributionSale::with(['product.category', 'dealer']);
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $stats = (clone $query)->selectRaw('
            COUNT(*) as aggregate_count,
            COALESCE(SUM(total_selling_value), 0) as aggregate_sell,
            COALESCE(SUM(profit), 0) as aggregate_profit
        ')->first();

        $pendingCount = (clone $query)
            ->whereRaw('COALESCE(paid_amount, 0) < COALESCE(total_selling_value, 0) - 0.0001')
            ->count();

        $distributionSales = $query->latest('date')->paginate(50)->withQueryString();

        $distributionDashboard = [
            'count' => (int) ($stats->aggregate_count ?? 0),
            'total_sell' => (float) ($stats->aggregate_sell ?? 0),
            'total_profit' => (float) ($stats->aggregate_profit ?? 0),
            'pending' => $pendingCount,
        ];

        $consolidatedDealers = User::query()
            ->where('role', 'dealer')
            ->orderBy('name')
            ->get(['id', 'name', 'business_name']);

        return view('admin.stock.distribution', compact('distributionSales', 'distributionDashboard', 'consolidatedDealers'));
    }

    public function exportDistributionCsv(Request $request)
    {
        $query = DistributionSale::with(['product.category', 'dealer']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $sales = $query->latest('date')->get();
        $filename = 'distribution-sales-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date',
                'Dealer',
                'Business name',
                'Seller',
                'Product',
                'Quantity',
                'Buy Price',
                'Sell Price',
                'Total Buy',
                'Total Sell',
                'Paid Amount',
                'Pending Amount',
                'Commission',
                'Profit',
                'Status',
            ]);

            foreach ($sales as $sale) {
                $totalSell = (float) ($sale->total_selling_value ?? 0);
                $paid = (float) ($sale->paid_amount ?? 0);

                fputcsv($handle, [
                    $sale->date ?? '',
                    $sale->dealer_name ?? $sale->dealer?->name ?? '',
                    $sale->dealer?->business_name ?? '',
                    $sale->seller_name ?? '',
                    trim(($sale->product?->category?->name ? $sale->product->category->name . ' - ' : '') . ($sale->product?->name ?? '')),
                    (int) ($sale->quantity_sold ?? 0),
                    number_format((float) ($sale->purchase_price ?? 0), 2, '.', ''),
                    number_format((float) ($sale->selling_price ?? 0), 2, '.', ''),
                    number_format((float) ($sale->total_purchase_value ?? 0), 2, '.', ''),
                    number_format($totalSell, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format(max(0, $totalSell - $paid), 2, '.', ''),
                    number_format((float) ($sale->commission ?? 0), 2, '.', ''),
                    number_format((float) ($sale->profit ?? 0), 2, '.', ''),
                    $sale->status ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Legacy route: channel and installments are handled on the edit distribution page (like purchases).
     */
    public function saveDistributionChannel(Request $request, $id)
    {
        return redirect()->route('admin.stock.distribution')
            ->with('info', 'Use Edit on the sale to record payments, payment channel, and remaining balance.');
    }

    public function updateDistributionStatus($id)
    {
        $sale = DistributionSale::findOrFail($id);
        $sale->update(['status' => 'complete']);
        return redirect()->route('admin.stock.distribution')->with('success', 'Distribution sale marked as complete.');
    }

    public function agentSales(Request $request)
    {
        $query = AgentSale::with(['product.category', 'agent', 'teamLeader', 'paymentOption']);
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $stats = (clone $query)->selectRaw('
            COUNT(*) as aggregate_count,
            COALESCE(SUM(total_selling_value), 0) as aggregate_sell,
            COALESCE(SUM(profit), 0) as aggregate_profit
        ')->first();

        $agentSales = $query->latest('date')->paginate(50)->withQueryString();
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();

        $agentSalesDashboard = [
            'count' => (int) ($stats->aggregate_count ?? 0),
            'total_sell' => (float) ($stats->aggregate_sell ?? 0),
            'total_profit' => (float) ($stats->aggregate_profit ?? 0),
        ];

        return view('admin.stock.agent-sales', compact('agentSales', 'paymentOptions', 'agentSalesDashboard'));
    }

    public function exportAgentSalesCsv(Request $request)
    {
        $query = AgentSale::with(['product.category', 'agent', 'teamLeader', 'paymentOption']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $sales = $query->latest('date')->get();
        $filename = 'agent-sales-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date',
                'Customer',
                'Seller',
                'Product',
                'Quantity',
                'Buy Price',
                'Sell Price',
                'Total Buy',
                'Total Sell',
                'Profit',
                'Commission Paid',
                'Payment Channel',
            ]);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->date ?? '',
                    $sale->customer_name ?? '',
                    $sale->seller_name ?? $sale->agent?->name ?? '',
                    trim(($sale->product?->category?->name ? $sale->product->category->name . ' - ' : '') . ($sale->product?->name ?? '')),
                    (int) ($sale->quantity_sold ?? 0),
                    number_format((float) ($sale->purchase_price ?? 0), 2, '.', ''),
                    number_format((float) ($sale->selling_price ?? 0), 2, '.', ''),
                    number_format((float) ($sale->total_purchase_value ?? 0), 2, '.', ''),
                    number_format((float) ($sale->total_selling_value ?? 0), 2, '.', ''),
                    number_format((float) ($sale->profit ?? 0), 2, '.', ''),
                    number_format((float) ($sale->commission_paid ?? 0), 2, '.', ''),
                    $sale->paymentOption?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function saveAgentSaleChannel(Request $request, $id)
    {
        $sale = AgentSale::findOrFail($id);

        if ($sale->payment_option_id) {
            return redirect()->route('admin.stock.agent-sales')->with('info', 'Payment channel is already set for this sale.');
        }

        $validated = $request->validate([
            'payment_option_id' => 'required|exists:payment_options,id',
        ]);

        $option = PaymentOption::findOrFail($validated['payment_option_id']);
        $sale->update(['payment_option_id' => $option->id]);
        
        $amount = (float) ($sale->total_selling_value ?? 0);
        if ($amount > 0) {
            $option->increment('balance', $amount);
        }

        return redirect()->route('admin.stock.agent-sales')->with('success', 'Channel saved. Amount added to ' . $option->name . '.');
    }

    public function updateAgentSaleCommission(Request $request, $id)
    {
        $sale = AgentSale::findOrFail($id);
        $validated = $request->validate(['commission_paid' => 'required|numeric|min:0']);
        $newCommission = (float) $validated['commission_paid'];
        $eps = 0.0001;

        if (! Schema::hasColumn('agent_sales', 'commission_paid')) {
            return redirect()->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => 'The database is missing the commission column. Run php artisan migrate.']);
        }

        try {
            DB::transaction(function () use ($sale, $newCommission, $eps) {
                $sale->refresh();
                $commissionService = app(AgentCommissionExpenseService::class);

                if ($newCommission <= $eps) {
                    $commissionService->reverseForAgentSale($sale);
                    $sale->update(['commission_paid' => $newCommission]);

                    return;
                }

                $hasBookedExpense = Schema::hasColumn('agent_sales', 'commission_expense_id')
                    && $sale->commission_expense_id;
                $amountChanged = abs((float) ($sale->commission_paid ?? 0) - $newCommission) > $eps;

                if ($hasBookedExpense && $amountChanged) {
                    $commissionService->reverseForAgentSale($sale);
                    $sale->refresh();
                }

                $sale->update(['commission_paid' => $newCommission]);
            });
        } catch (\Throwable $e) {
            Log::error('Agent sale commission save failed: ' . $e->getMessage(), ['exception' => $e]);

            return redirect()->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => 'Could not save commission. Try again or check logs.']);
        }

        $msg = $newCommission > $eps
            ? 'Commission saved. It will appear in Pay out; expense is recorded after Selcom payment completes.'
            : 'Commission cleared and any linked expense was reversed.';

        return redirect()->route('admin.stock.agent-sales', $request->query())->with('success', $msg);
    }

    public function convertAgentSaleToCredit(Request $request, int $id)
    {
        $sale = AgentSale::findOrFail($id);

        try {
            $credit = app(AgentSaleCreditMigrationService::class)->convertAgentSaleToAgentCredit($sale);

            return redirect()
                ->route('admin.stock.agent-credits', $request->query())
                ->with('success', 'Agent sale #'.$sale->id.' was converted to agent credit #'.$credit->id.'. The sale amount was removed from the sale channel and added to '.$credit->paymentOption?->name.'.');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('convertAgentSaleToCredit: '.$e->getMessage(), ['exception' => $e]);

            return redirect()
                ->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => 'Conversion failed. Check logs or try again.']);
        }
    }

    /**
     * Reverse payment channel / commission expense and clear product_list links for an agent sale.
     * Caller is responsible for deleting $sale and restoring catalog stock_quantity.
     *
     * @throws \RuntimeException When channel balance cannot absorb the reversal
     */
    protected function applyAgentSaleRemovalEffects(AgentSale $sale): void
    {
        if ($sale->payment_option_id) {
            $option = PaymentOption::find($sale->payment_option_id);
            $amount = (float) ($sale->total_selling_value ?? 0);
            if ($option && $amount > 0) {
                if ((float) $option->balance >= $amount) {
                    $option->decrement('balance', $amount);
                } else {
                    throw new \RuntimeException('Cannot delete sale because the linked channel balance is already lower than this sale amount.');
                }
            }
        }

        app(AgentCommissionExpenseService::class)->reverseForAgentSale($sale);

        ProductListItem::where('agent_sale_id', $sale->id)->update([
            'agent_sale_id' => null,
            'sold_at' => null,
        ]);
    }

    public function destroyAgentSale($id)
    {
        $sale = AgentSale::findOrFail($id);
        $product = $sale->product;
        $qty = (int) ($sale->quantity_sold ?? 0);

        try {
            DB::transaction(function () use ($sale) {
                $this->applyAgentSaleRemovalEffects($sale);
                DB::table('agent_sales')->where('id', $sale->id)->delete();
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.stock.agent-sales')
                ->withErrors(['error' => $e->getMessage()]);
        }

        if ($product && $qty > 0) {
            $product->increment('stock_quantity', $qty);
        }

        return redirect()->route('admin.stock.agent-sales')->with('success', 'Agent sale deleted successfully.');
    }

    public function downloadAgentSaleInvoice($id)
    {
        $sale = AgentSale::with(['product.category', 'agent', 'productListItem'])->findOrFail($id);

        $invoiceNo = 'AS-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
        $invoiceDate = $sale->date ? Carbon::parse($sale->date) : now();
        $filename = 'agent-sale-invoice-' . strtolower($invoiceNo) . '-' . $invoiceDate->format('Ymd') . '.pdf';
        $title = 'RECEIPT';

        return PdfDownload::fromView('admin.stock.receipt-invoice', compact('sale', 'invoiceNo', 'invoiceDate', 'title'), $filename);
    }

    public function shopRecords()
    {
        $shopRecords = \App\Models\ShopRecord::with('product')->latest('date')->get();
        return view('admin.stock.shop-records', compact('shopRecords'));
    }

    public function payables()
    {
        $payables = \App\Models\Payable::latest('date')->get();
        return view('admin.stock.payables', compact('payables'));
    }

    /**
     * Form: scan IMEI, select stock (from pending purchases), select model from selected stock.
     * When no rows exist in `stocks`, the stocks list page falls back to purchases; this form
     * does the same so IMEIs can still be added against pending purchases (including purchases
     * with a null stock_id).
     */
    public function addProductForm()
    {
        $stocks = Stock::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $addProductTarget = 'stock';
        $purchasePickerRows = collect();

        if ($stocks->isEmpty()) {
            $addProductTarget = 'purchase';
            $purchasePickerRows = Purchase::stockPurchases()
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->get(['id', 'name']);
        }

        return view('admin.stock.add-product', compact('stocks', 'addProductTarget', 'purchasePickerRows'));
    }

    /**
     * JSON: validate IMEIs before registering on a purchase (duplicate check + slot context).
     */
    public function validateAddProductImeis(Request $request)
    {
        $validated = $request->validate([
            'catalog_product_id' => 'required|integer|exists:models,id',
            'purchase_id' => 'nullable|integer|exists:purchases,id',
            'stock_id' => 'nullable|integer|exists:stocks,id',
            'imeis' => 'nullable|array|max:500',
            'imeis.*' => 'string|max:512',
        ]);

        $catalogProductId = (int) $validated['catalog_product_id'];
        $purchase = $this->resolvePurchaseForAddProduct($request);

        if (! $purchase) {
            return response()->json([
                'ok' => false,
                'message' => 'Select a purchase or stock with open slots.',
            ], 422);
        }

        $purchase->loadMissing(['product', 'lines']);
        $purchase->recalculateLimitRemaining();
        $purchase->refresh()->loadMissing(['product', 'lines']);

        if ($purchase->isPassthrough() || $purchase->limit_status !== 'pending' || (int) $purchase->limit_remaining <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'This purchase has no remaining device slots.',
                'slots_remaining' => 0,
            ], 422);
        }

        $catalogProduct = Product::with('category')->find($catalogProductId);
        if (! $catalogProduct) {
            return response()->json(['ok' => false, 'message' => 'Invalid model.'], 422);
        }

        $remainingForModel = 0;
        if ($purchase->lines->isNotEmpty()) {
            $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
            if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Selected model has no open slots on this purchase.',
                    'slots_remaining' => 0,
                ], 422);
            }
            $remainingForModel = (int) $purchaseLine->limit_remaining;
        } else {
            if ($purchase->product_id && (int) $purchase->product_id !== $catalogProductId) {
                return response()->json(['ok' => false, 'message' => 'Selected model does not match this purchase.'], 422);
            }
            $remainingForModel = (int) $purchase->limit_remaining;
        }

        $normalized = [];
        foreach ($validated['imeis'] ?? [] as $raw) {
            $imei = ImeiListParser::normalizeImei($raw);
            if ($imei !== '') {
                $normalized[] = $imei;
            }
        }

        $unique = array_values(array_unique($normalized));
        $registered = $unique === []
            ? collect()
            : ProductListItem::query()
                ->whereIn('imei_number', $unique)
                ->pluck('imei_number');

        $catName = $catalogProduct->category?->name ?? '—';
        $invoiceNo = $purchase->name ?? ('Purchase #'.$purchase->id);

        return response()->json([
            'ok' => true,
            'slots_remaining' => $remainingForModel,
            'purchase_label' => 'Inv no. '.$invoiceNo,
            'model_label' => $catName.' — '.$catalogProduct->name,
            'registered' => $registered->values()->all(),
        ]);
    }

    /**
     * Resolve the purchase row used for add-product (direct purchase or pending stock purchase).
     */
    private function resolvePurchaseForAddProduct(Request $request): ?Purchase
    {
        if ($request->filled('purchase_id')) {
            return Purchase::stockPurchases()
                ->with(['product', 'stock', 'lines'])
                ->find((int) $request->input('purchase_id'));
        }

        if ($request->filled('stock_id')) {
            $stock = Stock::find((int) $request->input('stock_id'));
            if (! $stock) {
                return null;
            }

            return Purchase::stockPurchases()
                ->with(['product', 'lines'])
                ->where('stock_id', $stock->id)
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->latest('date')
                ->latest('id')
                ->first();
        }

        return null;
    }

    /**
     * JSON: model + category for one purchase (web Add product when picking a purchase directly).
     */
    public function modelsForPurchaseAddProduct(Purchase $purchase)
    {
        if ($purchase->isPassthrough()) {
            abort(404);
        }

        return response()->json([
            'data' => $this->resolvePurchaseRegistrationRows($purchase, persistLimits: true),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function purchaseModelRowForRegistration(?Product $product, int $limitRemaining, float $unitPrice, mixed $sellPrice): ?array
    {
        if (! $product) {
            return null;
        }

        $model = trim((string) ($product->name ?? ''));
        if ($model === '') {
            return null;
        }

        $categoryId = $product->category_id ?? null;
        $catName = $product->category?->name ?? '—';
        $sell = $sellPrice !== null ? (float) $sellPrice : null;

        return [
            'product_id' => (int) $product->id,
            'model' => $model,
            'category_id' => $categoryId ? (int) $categoryId : null,
            'category_name' => $catName,
            'unit_price' => $unitPrice,
            'sell_price' => $sell,
            'limit_remaining' => $limitRemaining,
            'can_register' => ! empty($categoryId),
            'label' => $catName.' — '.$model.' · slots '.$limitRemaining.' · cost '.number_format($unitPrice, 2).($sell !== null ? ' · sell '.number_format($sell, 2) : ''),
        ];
    }

    /**
     * JSON: distinct models (and category_id) for a stock (from product_list + purchases).
     */
    public function modelsForStock(Stock $stock)
    {
        $fromList = \App\Models\ProductListItem::query()
            ->where('stock_id', $stock->id)
            ->with('product:id,name,category_id')
            ->get()
            ->map(function ($r) {
                $model = trim((string) ($r->model ?: ($r->product->name ?? '')));
                $categoryId = $r->category_id ?: ($r->product->category_id ?? null);
                if ($model === '' || empty($categoryId)) {
                    return null;
                }
                $productId = $r->product_id ? (int) $r->product_id : null;
                if (! $productId) {
                    $productId = (int) (Product::query()
                        ->where('category_id', (int) $categoryId)
                        ->where('name', $model)
                        ->value('id') ?: 0);
                }
                if (! $productId) {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'model' => $model,
                    'category_id' => (int) $categoryId,
                    'label' => $model,
                ];
            })
            ->filter()
            ->unique(fn ($row) => $row['product_id'] ? 'p:'.$row['product_id'] : 'm:'.$row['model'].'|c:'.$row['category_id'])
            ->values();

        $fromPurchases = Purchase::where('stock_id', $stock->id)
            ->with(['lines.product.category', 'product.category'])
            ->get()
            ->flatMap(function (Purchase $p) {
                if ($p->lines->isNotEmpty()) {
                    return $p->lines->map(function ($line) {
                        $product = $line->product;
                        if (! $product) {
                            return null;
                        }
                        $model = trim((string) ($product->name ?? ''));
                        $categoryId = $product->category_id ?? null;
                        if ($model === '' || empty($categoryId)) {
                            return null;
                        }
                        $unit = (float) $line->unit_price;
                        $sell = $line->sell_price !== null ? (float) $line->sell_price : null;
                        $rem = (int) $line->limit_remaining;

                        return [
                            'product_id' => (int) $product->id,
                            'model' => $model,
                            'category_id' => (int) $categoryId,
                            'label' => ($product->category?->name ?? '—').' — '.$model.' · slots '.$rem.' · cost '.number_format($unit, 2).($sell !== null ? ' · sell '.number_format($sell, 2) : ''),
                        ];
                    })->filter();
                }

                $product = $p->product;
                if (! $product) {
                    return collect();
                }
                $model = trim((string) ($product->name ?? ''));
                $categoryId = $product->category_id ?? null;
                if ($model === '' || empty($categoryId)) {
                    return collect();
                }
                $unit = (float) ($p->unit_price ?? 0);
                $sell = $p->sell_price !== null ? (float) $p->sell_price : null;
                $rem = (int) ($p->limit_remaining ?? 0);

                return collect([[
                    'product_id' => (int) $product->id,
                    'model' => $model,
                    'category_id' => (int) $categoryId,
                    'label' => ($product->category?->name ?? '—').' — '.$model.' · slots '.$rem.' · cost '.number_format($unit, 2).($sell !== null ? ' · sell '.number_format($sell, 2) : ''),
                ]]);
            })
            ->unique(fn ($row) => $row['product_id'] ? 'p:'.$row['product_id'] : 'm:'.$row['model'].'|c:'.$row['category_id'])
            ->values();

        $combined = $fromList
            ->concat($fromPurchases)
            ->unique(fn ($row) => $row['product_id'] ? 'p:'.$row['product_id'] : 'm:'.$row['model'].'|c:'.$row['category_id'])
            ->sortBy('model', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($combined->isEmpty() && ! empty($stock->default_model) && ! empty($stock->default_category_id)) {
            $defaultPid = Product::query()
                ->where('category_id', (int) $stock->default_category_id)
                ->where('name', $stock->default_model)
                ->value('id');

            $combined = collect([[
                'product_id' => $defaultPid ? (int) $defaultPid : null,
                'model' => $stock->default_model,
                'category_id' => (int) $stock->default_category_id,
                'label' => $stock->default_model,
            ]]);
        }

        return response()->json(['data' => $combined->all()]);
    }

    /**
     * Decode QR codes from uploaded photos (server uses GD + ZXing; 1D barcodes work best from the mobile app).
     */
    public function decodeBarcodeImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:30',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if (! BarcodeImageDecoder::decodingAvailable()) {
            return response()->json([
                'message' => 'QR decode needs the PHP GD extension.',
                'codes' => [],
            ], 503);
        }

        $decoder = new BarcodeImageDecoder;
        $seen = [];
        $codes = [];

        foreach ($request->file('images', []) as $file) {
            $path = $file->getRealPath();
            if (! $path || ! is_readable($path)) {
                continue;
            }
            foreach ($decoder->decodeFile($path) as $row) {
                $c = trim((string) ($row['code'] ?? ''));
                if ($c !== '' && ! isset($seen[$c])) {
                    $seen[$c] = true;
                    $codes[] = $c;
                }
            }
        }

        return response()->json([
            'codes' => $codes,
            'message' => count($codes) ? null : 'No QR code found. Try clearer photos or type IMEIs manually. For linear barcodes, use the OpticApp admin Add Product (photo) flow.',
        ]);
    }

    /**
     * Save one or more IMEIs: stock_id or purchase_id, catalog_product_id, imei_numbers (newline / comma separated).
     */
    public function storeProductFromForm(Request $request, PurchaseImeiRegistrationService $registrationService)
    {
        $baseRules = [
            'catalog_product_id' => 'required|exists:models,id',
            'imei_numbers' => 'required|string|max:65535',
        ];

        if ($request->filled('purchase_id')) {
            $validated = $request->validate($baseRules + [
                'purchase_id' => 'required|exists:purchases,id',
            ]);
            $purchase = Purchase::stockPurchases()->with(['product', 'stock', 'lines'])->findOrFail($validated['purchase_id']);
        } elseif ($request->filled('stock_id')) {
            $validated = $request->validate($baseRules + [
                'stock_id' => 'required|exists:stocks,id',
            ]);
            $stock = Stock::findOrFail($validated['stock_id']);
            $purchase = Purchase::stockPurchases()->with(['product', 'lines'])
                ->where('stock_id', $stock->id)
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->latest('date')->latest('id')->first();

            if (! $purchase) {
                return redirect()->route('admin.stock.add-product')
                    ->withInput()
                    ->withErrors(['stock_id' => 'No pending purchase limit for this stock.']);
            }
        } else {
            $pickField = Stock::query()->exists() ? 'stock_id' : 'purchase_id';
            $pickMessage = $pickField === 'stock_id'
                ? 'Select stock first.'
                : 'Select a purchase first.';

            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors([$pickField => $pickMessage]);
        }

        $result = $registrationService->register(
            $purchase,
            (int) $validated['catalog_product_id'],
            (string) $validated['imei_numbers'],
            oneImeiPerLine: true
        );

        if ($result->hasValidationError()) {
            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors([$result->errorField => $result->errorMessage]);
        }

        if ($result->succeeded()) {
            $msg = 'Added '.$result->created.' device(s) ('.$result->parsedCount.' IMEI(s) parsed).';
            if ($result->failed !== []) {
                $msg .= ' Skipped: '.implode('; ', array_slice($result->failed, 0, 10)).(count($result->failed) > 10 ? '…' : '');
            }

            return redirect()->route('admin.stock.add-product')->with('success', $msg);
        }

        return redirect()->route('admin.stock.add-product')
            ->withInput()
            ->withErrors(['imei_numbers' => $result->errorMessage ?? 'Could not add devices.']);
    }

    public function createPurchase(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();

        $fromStock = null;
        if ($request->has('from_stock')) {
            $fromStock = Stock::with(['defaultCategory', 'productListItems' => fn ($q) => $q->with(['category', 'product'])->latest('id')->limit(1)])
                ->find($request->from_stock);

            if ($fromStock) {
                // Quantity = stock limit (total quantity for this purchase from stock)
                $fromStock->purchase_quantity = $fromStock->stock_limit;

                // Category and model: from product list items in this stock (as added in app), or fallback to stock defaults
                $firstItem = $fromStock->productListItems->first();
                if ($firstItem) {
                    $fromStock->purchase_category_id = $firstItem->category_id ?? $firstItem->product?->category_id;
                    $fromStock->purchase_category_name = $firstItem->category?->name ?? $firstItem->product?->category?->name ?? '–';
                    $fromStock->purchase_model = $firstItem->model ?? $firstItem->product?->name ?? '–';
                } else {
                    $fromStock->purchase_category_id = $fromStock->default_category_id;
                    $fromStock->purchase_category_name = $fromStock->defaultCategory?->name ?? '–';
                    $fromStock->purchase_model = $fromStock->default_model ?? '–';
                    if (!$fromStock->purchase_category_id || !$fromStock->purchase_model) {
                        return redirect()->route('admin.stock.stocks')
                            ->with('info', 'Add products to this stock in the app first. Then "Add via Purchases" will use that category and model.');
                    }
                }
            }
        }

        $branches = Branch::orderBy('name')->get();

        $productsForSelect = Product::with('category')
            ->get()
            ->sortBy(fn (Product $p) => ($p->category?->name ?? '') . $p->name)
            ->values();

        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
        $isPassthrough = false;

        return view('admin.stock.create-purchase', compact('vendors', 'fromStock', 'branches', 'productsForSelect', 'paymentOptions', 'isPassthrough'));
    }

    /**
     * When a payment channel is selected at purchase create, treat empty/zero paid amount as full total.
     */
    private function resolvePurchaseCreatePaidAmount(float $totalAmount, float $paidAmount, ?int $paymentOptionId): float
    {
        if ($paymentOptionId !== null && $paidAmount <= 0.0001 && $totalAmount > 0) {
            return $totalAmount;
        }

        return $paidAmount;
    }

    public function storePurchase(Request $request)
    {
        $passthrough = $request->boolean('_passthrough');
        $paymentOptionId = $request->filled('payment_option_id') ? $request->input('payment_option_id') : null;
        $hasNoteColumn = Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'note');
        $listRoute = $passthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
        $successMessage = $passthrough ? 'Passthrough recorded successfully.' : 'Purchase recorded successfully.';

        if ($passthrough && $request->filled('stock_id')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['stock_id' => 'Passthrough cannot be created from stock. Use the standard purchase flow for stock-linked entries.']);
        }

        if ($request->filled('stock_id')) {
            $request->validate([
                'product_id' => 'required|exists:models,id',
            ]);
            $selectedProduct = Product::findOrFail($request->product_id);
            $request->merge([
                'category_id' => $selectedProduct->category_id,
                'model' => $selectedProduct->name,
            ]);

            $validated = $request->validate([
                'stock_id' => 'nullable|exists:stocks,id',
                'branch_id' => 'required|exists:branches,id',
                'name' => 'nullable|string|max:255',
                'date' => 'required|date',
                'distributor_name' => 'nullable|string|max:255',
                'category_id' => 'required|exists:brands,id',
                'model' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
                'sell_price' => 'nullable|numeric|min:0',
                'paid_date' => 'nullable|date',
                'paid_amount' => 'nullable|numeric|min:0',
                'payment_option_id' => [
                    'nullable',
                    'exists:payment_options,id',
                    Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0.0001),
                ],
                'payment_receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'note' => 'nullable|string|max:10000',
            ]);

            $nameInput = trim((string) ($validated['name'] ?? ''));
            if ($nameInput === '') {
                $validated['name'] = PurchaseInvoiceNumber::unique(null, $validated['date'] ?? null);
            } else {
                $validated['name'] = $nameInput;
            }

            $productPrice = $validated['sell_price'] ?? $validated['unit_price'];
            $product = Product::firstOrCreate(
                [
                    'category_id' => $validated['category_id'],
                    'name' => $validated['model'],
                ],
                [
                    'price' => $productPrice,
                    'stock_quantity' => 0,
                    'rating' => 5.0,
                    'description' => 'Auto-created from purchase',
                    'images' => [],
                ]
            );

            $stockId = (int) $validated['stock_id'];
            $quantity = (int) $validated['quantity'];

            unset($validated['category_id'], $validated['model'], $validated['stock_id']);

            $validated['product_id'] = $product->id;
            $validated['stock_id'] = $stockId;

            $validated['total_amount'] = $quantity * (float) $validated['unit_price'];
            $totalAmount = (float) $validated['total_amount'];
            $paidAmount = $this->resolvePurchaseCreatePaidAmount(
                $totalAmount,
                (float) ($validated['paid_amount'] ?? 0),
                $paymentOptionId !== null ? (int) $paymentOptionId : null
            );
            $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');
            $validated['payment_status'] = $paymentStatus;
            $validated['paid_amount'] = $paidAmount;
            $validated['limit_status'] = $passthrough ? 'complete' : 'pending';
            $validated['limit_remaining'] = $passthrough ? 0 : $quantity;
            $validated['sell_price'] = $request->filled('sell_price') ? $request->input('sell_price') : null;
            $validated['is_passthrough'] = $passthrough;

            if ($hasNoteColumn) {
                $validated['note'] = $request->input('note');
            }

            try {
                $columns = Schema::getColumnListing('purchases');
                if (in_array('payment_option_id', $columns, true)) {
                    $validated['payment_option_id'] = $paymentOptionId;
                }
            } catch (\Exception $e) {
                Log::warning('payment_option_id column not found in purchases table. Migration may need to be run.');
            }

            if ($paidAmount > 0 && $paymentOptionId) {
                $paymentOption = PaymentOption::visible()->find($paymentOptionId);
                if (! $paymentOption) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['payment_option_id' => 'Selected channel is not available for payments. Open Channels and use Show, or pick another account.']);
                }
                if ((float) $paymentOption->balance >= $paidAmount) {
                    $paymentOption->decrement('balance', $paidAmount);
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel.']);
                }
            }

            $purchase = Purchase::create($validated);

            if ($request->hasFile('payment_receipt_image')) {
                $receiptImage = $request->file('payment_receipt_image');
                if ($receiptImage->isValid()) {
                    $receiptDir = 'receipts/purchase-' . $purchase->id;
                    $paymentReceiptPath = $receiptImage->store($receiptDir, 'public');
                    $purchase->update(['payment_receipt_image' => $paymentReceiptPath]);
                }
            }

            $product->increment('stock_quantity', $quantity);

            if ($paidAmount > 0 && $request->filled('payment_option_id')) {
                try {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'payment_option_id' => $request->input('payment_option_id'),
                        'amount' => $paidAmount,
                        'paid_date' => $validated['paid_date'] ?? now()->toDateString(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create purchase payment record: ' . $e->getMessage());
                }
            }

            $latestPurchase = Purchase::where('product_id', $product->id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->latest('id')
                ->first();

            if ($latestPurchase && $latestPurchase->sell_price) {
                $product->update(['price' => $latestPurchase->sell_price]);
            }

            return redirect()->route($listRoute)->with('success', $successMessage);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'distributor_name' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1|max:40',
            'lines.*.product_id' => 'required|exists:models,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0.01',
            'lines.*.sell_price' => 'nullable|numeric|min:0',
            'paid_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_option_id' => [
                'nullable',
                'exists:payment_options,id',
                Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0.0001),
            ],
            'payment_receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'note' => 'nullable|string|max:10000',
        ]);

        $linesInput = $validated['lines'];
        $productIds = array_map(fn ($row) => (int) ($row['product_id'] ?? 0), $linesInput);
        if (count($productIds) !== count(array_unique($productIds))) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['lines' => 'Each catalog model may only appear once on the same purchase.']);
        }

        $linePayload = [];
        $totalQty = 0;
        $totalAmount = 0.0;
        $firstProduct = null;

        foreach ($linesInput as $row) {
            $prod = Product::findOrFail((int) $row['product_id']);
            $qty = (int) $row['quantity'];
            $unit = (float) $row['unit_price'];
            $totalQty += $qty;
            $totalAmount += $qty * $unit;
            if ($firstProduct === null) {
                $firstProduct = $prod;
            }
            $sellRaw = $row['sell_price'] ?? null;
            $sell = ($sellRaw !== null && $sellRaw !== '') ? (float) $sellRaw : null;
            $linePayload[] = [
                'product' => $prod,
                'quantity' => $qty,
                'unit_price' => $unit,
                'sell_price' => $sell,
            ];
        }

        $nameInput = trim((string) ($validated['name'] ?? ''));
        if ($nameInput === '') {
            $purchaseName = PurchaseInvoiceNumber::unique(null, $validated['date'] ?? null);
        } else {
            $purchaseName = $nameInput;
        }

        $paidAmount = $this->resolvePurchaseCreatePaidAmount(
            $totalAmount,
            (float) ($validated['paid_amount'] ?? 0),
            $paymentOptionId !== null ? (int) $paymentOptionId : null
        );
        $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');

        $header = [
            'name' => $purchaseName,
            'branch_id' => (int) $validated['branch_id'],
            'date' => $validated['date'],
            'distributor_name' => $validated['distributor_name'] ?? null,
            'product_id' => $firstProduct->id,
            'quantity' => $totalQty,
            'unit_price' => $totalQty > 0 ? round($totalAmount / $totalQty, 4) : 0,
            'total_amount' => $totalAmount,
            'paid_date' => $validated['paid_date'] ?? null,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'limit_status' => $passthrough ? 'complete' : 'pending',
            'limit_remaining' => $passthrough ? 0 : $totalQty,
            'sell_price' => null,
            'is_passthrough' => $passthrough,
        ];

        if ($hasNoteColumn) {
            $header['note'] = $validated['note'] ?? null;
        }

        try {
            $columns = Schema::getColumnListing('purchases');
            if (in_array('payment_option_id', $columns, true)) {
                $header['payment_option_id'] = $paymentOptionId;
            }
        } catch (\Exception $e) {
            Log::warning('payment_option_id column not found in purchases table. Migration may need to be run.');
        }

        if ($paidAmount > 0 && $paymentOptionId) {
            $paymentOption = PaymentOption::visible()->find($paymentOptionId);
            if (! $paymentOption) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['payment_option_id' => 'Selected channel is not available for payments. Open Channels and use Show, or pick another account.']);
            }
            if ((float) $paymentOption->balance >= $paidAmount) {
                $paymentOption->decrement('balance', $paidAmount);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel.']);
            }
        }

        $purchase = null;

        DB::transaction(function () use ($header, $linePayload, $paidAmount, $request, $validated, $passthrough, &$purchase) {
            $purchase = Purchase::create($header);

            foreach ($linePayload as $row) {
                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'sell_price' => $row['sell_price'],
                    'limit_remaining' => $passthrough ? 0 : $row['quantity'],
                ]);

                $row['product']->increment('stock_quantity', $row['quantity']);
            }

            if (! $passthrough) {
                $purchase->update([
                    'limit_remaining' => (int) $purchase->lines()->sum('limit_remaining'),
                ]);
            }

            if ($request->hasFile('payment_receipt_image')) {
                $receiptImage = $request->file('payment_receipt_image');
                if ($receiptImage->isValid()) {
                    $receiptDir = 'receipts/purchase-' . $purchase->id;
                    $paymentReceiptPath = $receiptImage->store($receiptDir, 'public');
                    $purchase->update(['payment_receipt_image' => $paymentReceiptPath]);
                }
            }

            if ($paidAmount > 0 && $request->filled('payment_option_id')) {
                try {
                    PurchasePayment::create([
                        'purchase_id' => $purchase->id,
                        'payment_option_id' => $request->input('payment_option_id'),
                        'amount' => $paidAmount,
                        'paid_date' => $validated['paid_date'] ?? now()->toDateString(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create purchase payment record: ' . $e->getMessage());
                }
            }
        });

        foreach ($linePayload as $row) {
            if ($row['sell_price'] !== null) {
                $row['product']->update(['price' => (float) $row['sell_price']]);

                continue;
            }

            $latestPurchase = Purchase::where('product_id', $row['product']->id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->latest('id')
                ->first();

            if ($latestPurchase && $latestPurchase->sell_price) {
                $row['product']->update(['price' => (float) $latestPurchase->sell_price]);
            }
        }

        return redirect()->route($listRoute)->with('success', $successMessage);
    }

    public function storePassthrough(Request $request)
    {
        $request->merge(['_passthrough' => true]);

        return $this->storePurchase($request);
    }

    public function passthrough(Request $request)
    {
        return $this->purchaseListForType($request, passthrough: true);
    }

    public function exportPassthroughCsv(Request $request)
    {
        $params = $this->resolvePurchaseListParams($request, passthrough: true);
        $purchases = $this->buildPurchaseListQuery($params)->latest('date')->get();
        $filename = 'passthrough-' . now()->format('Ymd-His') . '.csv';

        return $this->streamPurchaseListCsv($purchases, $filename);
    }

    public function viewPassthroughReceipts()
    {
        $purchases = Purchase::passthrough()
            ->with(['product', 'stock'])
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->get();

        return view('admin.stock.passthrough-receipts', compact('purchases'));
    }

    public function createPassthrough(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $productsForSelect = Product::with('category')
            ->get()
            ->sortBy(fn (Product $p) => ($p->category?->name ?? '') . $p->name)
            ->values();
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
        $isPassthrough = true;
        $fromStock = null;

        return view('admin.stock.create-purchase', compact(
            'vendors',
            'fromStock',
            'branches',
            'productsForSelect',
            'paymentOptions',
            'isPassthrough'
        ));
    }

    public function showPassthrough($id)
    {
        $purchase = Purchase::passthrough()
            ->with(['lines.product.category', 'product.category', 'payments.paymentOption', 'branch'])
            ->findOrFail($id);

        return view('admin.stock.passthrough-show', compact('purchase'));
    }

    public function editPassthrough($id)
    {
        $purchase = Purchase::passthrough()->with(['product.category', 'payments.paymentOption'])->findOrFail($id);
        $categories = \App\Models\Category::orderBy('name')->get();
        $distributors = Purchase::passthrough()->select('distributor_name')
            ->whereNotNull('distributor_name')
            ->distinct()
            ->pluck('distributor_name');
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
        $isPassthrough = true;

        return view('admin.stock.edit-purchase', compact('purchase', 'categories', 'distributors', 'paymentOptions', 'isPassthrough'));
    }

    public function updatePassthrough(Request $request, $id)
    {
        Purchase::passthrough()->findOrFail($id);

        return $this->updatePurchase($request, $id, passthrough: true);
    }

    public function destroyPassthrough($id)
    {
        Purchase::passthrough()->findOrFail($id);

        return $this->destroyPurchase($id, passthrough: true);
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    private function purchaseListForType(Request $request, bool $passthrough)
    {
        $params = $this->resolvePurchaseListParams($request, $passthrough);

        if ($request->ajax()) {
            $query = $this->buildPurchaseListQuery($params);
            $purchases = (clone $query)->latest('date')->paginate(50)->withQueryString();
            $purchaseDashboard = $this->computePurchaseDashboard($query);

            return response()->json([
                'tbody' => view('admin.stock.partials.purchases-tbody', [
                    'purchases' => $purchases,
                    'isPassthrough' => $passthrough,
                ])->render(),
                'pagination' => view('admin.partials.table-pagination', [
                    'paginator' => $purchases,
                    'label' => $passthrough ? 'entries' : 'purchases',
                ])->render(),
                'dashboard' => view('admin.stock.partials.purchases-dashboard', [
                    'purchaseDashboard' => $purchaseDashboard,
                    'isPassthrough' => $passthrough,
                ])->render(),
            ]);
        }

        return view('admin.stock.purchases', [
            'purchases' => null,
            'dateFrom' => $params['dateFrom'],
            'dateTo' => $params['dateTo'],
            'preset' => $params['preset'],
            'search' => $params['search'],
            'purchaseDashboard' => [
                'count' => 0,
                'total_value' => 0.0,
                'pending_amount' => 0.0,
            ],
            'isPassthrough' => $passthrough,
            'ajaxLoad' => true,
        ]);
    }

    /**
     * @return array{passthrough: bool, search: string, dateFrom: ?string, dateTo: ?string, preset: ?string}
     */
    private function resolvePurchaseListParams(Request $request, bool $passthrough): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $preset = $request->input('preset');
        $search = $request->string('search')->trim()->toString();

        if ($request->filled('preset')) {
            $now = Carbon::now();
            switch ($preset) {
                case 'this_week':
                    $dateFrom = $now->copy()->startOfWeek()->toDateString();
                    $dateTo = $now->copy()->endOfWeek()->toDateString();
                    break;
                case 'last_week':
                    $dateFrom = $now->copy()->subWeek()->startOfWeek()->toDateString();
                    $dateTo = $now->copy()->subWeek()->endOfWeek()->toDateString();
                    break;
                case 'last_30_days':
                    $dateFrom = $now->copy()->subDays(30)->toDateString();
                    $dateTo = $now->toDateString();
                    break;
            }
        }

        return [
            'passthrough' => $passthrough,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'preset' => $preset,
        ];
    }

    /**
     * @param  array{passthrough: bool, search: string, dateFrom: ?string, dateTo: ?string}  $params
     */
    private function buildPurchaseListQuery(array $params)
    {
        $query = $params['passthrough'] ? Purchase::passthrough() : Purchase::stockPurchases();
        $query->with(['product', 'stock', 'branch', 'lines.product']);

        if ($params['dateFrom']) {
            $query->where('date', '>=', $params['dateFrom']);
        }
        if ($params['dateTo']) {
            $query->where('date', '<=', $params['dateTo']);
        }

        return $query->listSearch($params['search']);
    }

    /**
     * @return array{count: int, total_value: float, pending_amount: float}
     */
    private function computePurchaseDashboard($query): array
    {
        $valueExpr = 'COALESCE(total_amount, quantity * unit_price)';
        $stats = (clone $query)->selectRaw("
            COUNT(*) as aggregate_count,
            COALESCE(SUM({$valueExpr}), 0) as aggregate_total,
            COALESCE(SUM(GREATEST(0, {$valueExpr} - COALESCE(paid_amount, 0))), 0) as aggregate_pending
        ")->first();

        return [
            'count' => (int) ($stats->aggregate_count ?? 0),
            'total_value' => (float) ($stats->aggregate_total ?? 0),
            'pending_amount' => (float) ($stats->aggregate_pending ?? 0),
        ];
    }

    private function streamPurchaseListCsv($purchases, string $filename)
    {
        return response()->streamDownload(function () use ($purchases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice',
                'Date',
                'Branch',
                'Distributor',
                'Product',
                'Quantity',
                'Unit Price',
                'Total Amount',
                'Paid Date',
                'Paid Amount',
                'Pending Amount',
                'Sell Price',
                'Status',
            ]);

            foreach ($purchases as $purchase) {
                $total = (float) ($purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price));
                $paid = (float) ($purchase->paid_amount ?? 0);
                $pending = max(0, $total - $paid);

                $productCell = '';
                if ($purchase->lines->isNotEmpty()) {
                    $productCell = $purchase->lines
                        ->map(function ($line) {
                            $p = $line->product;
                            if (! $p) {
                                return '';
                            }

                            return trim(($p->category?->name ? $p->category->name.' - ' : '').$p->name);
                        })
                        ->filter()
                        ->unique()
                        ->implode('; ');
                } else {
                    $productCell = trim(($purchase->product?->category?->name ? $purchase->product->category->name.' - ' : '').($purchase->product?->name ?? ''));
                }

                $sellCell = $purchase->sell_price !== null
                    ? number_format((float) $purchase->sell_price, 2, '.', '')
                    : ($purchase->lines->isNotEmpty()
                        ? $purchase->lines->map(fn ($l) => $l->sell_price !== null ? number_format((float) $l->sell_price, 2, '.', '') : null)->filter()->unique()->implode('; ')
                        : '');

                fputcsv($handle, [
                    $purchase->name ?? '',
                    $purchase->date ?? '',
                    $purchase->branch?->name ?? '',
                    $purchase->distributor_name ?? '',
                    $productCell,
                    (int) ($purchase->quantity ?? 0),
                    number_format((float) ($purchase->unit_price ?? 0), 2, '.', ''),
                    number_format($total, 2, '.', ''),
                    $purchase->paid_date ?? '',
                    number_format($paid, 2, '.', ''),
                    number_format($pending, 2, '.', ''),
                    $sellCell,
                    $purchase->payment_status ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function editPurchase($id)
    {
        $purchase = Purchase::stockPurchases()->with(['product.category', 'payments.paymentOption'])->findOrFail($id);
        
        // Get all categories for the select dropdown
        $categories = \App\Models\Category::orderBy('name')->get();
            
        // Get unique distributors for the datalist
        $distributors = Purchase::select('distributor_name')
            ->whereNotNull('distributor_name')
            ->distinct()
            ->pluck('distributor_name');
        
        // Get payment options with balance for selection
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
            
        $isPassthrough = false;

        return view('admin.stock.edit-purchase', compact('purchase', 'categories', 'distributors', 'paymentOptions', 'isPassthrough'));
    }

    public function updatePurchase(Request $request, $id, bool $passthrough = false)
    {
        $purchase = ($passthrough ? Purchase::passthrough() : Purchase::stockPurchases())
            ->with('product')
            ->findOrFail($id);

        $rules = [
            'name' => 'nullable|string|max:255',
            'sell_price' => 'nullable|numeric|min:0',
            'paid_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_option_id' => [
                'nullable',
                'exists:payment_options,id',
                Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0.0001),
            ],
            'payment_receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
        $validated = $request->validate($rules);

        // Upload payment receipt image if provided (store in purchase-specific directory)
        $paymentReceiptPath = $purchase->payment_receipt_image;
        if ($request->hasFile('payment_receipt_image')) {
            $receiptImage = $request->file('payment_receipt_image');
            if ($receiptImage->isValid()) {
                // Delete old receipt image if exists
                if ($purchase->payment_receipt_image && Storage::disk('public')->exists($purchase->payment_receipt_image)) {
                    Storage::disk('public')->delete($purchase->payment_receipt_image);
                }
                // Store in purchase-specific directory: receipts/purchase-{id}/
                $receiptDir = 'receipts/purchase-' . $purchase->id;
                $paymentReceiptPath = $receiptImage->store($receiptDir, 'public');
            }
        }

        // Form field "paid_amount" is incremental ("Pay this time"); persist cumulative total.
        $totalAmount = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
        $oldPaidAmount = (float) ($purchase->paid_amount ?? 0);
        $increment = max(0, (float) ($validated['paid_amount'] ?? 0));
        $remaining = max(0, $totalAmount - $oldPaidAmount);
        $eps = 0.0001;

        if ($increment > $remaining + $eps) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['paid_amount' => 'Pay amount cannot exceed the remaining balance for this purchase.']);
        }

        $newPaidAmount = min($totalAmount, $oldPaidAmount + $increment);
        $paymentDifference = $newPaidAmount - $oldPaidAmount;

        $paymentStatus = $newPaidAmount >= $totalAmount - $eps ? 'paid' : ($newPaidAmount > $eps ? 'partial' : 'pending');

        $oldPaymentOption = $purchase->payment_option_id;
        $hasPaymentOptionInput = $request->has('payment_option_id');
        if ($hasPaymentOptionInput) {
            $newPaymentOptionId = $validated['payment_option_id'] ?? null;
            if ($newPaymentOptionId === '' || $newPaymentOptionId === false) {
                $newPaymentOptionId = null;
            } else {
                $newPaymentOptionId = (int) $newPaymentOptionId;
            }
        } else {
            $newPaymentOptionId = $oldPaymentOption !== null ? (int) $oldPaymentOption : null;
        }

        if ($increment > $eps && $newPaymentOptionId === null) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['payment_option_id' => 'Select which payment channel to pay from (e.g. your bank account).']);
        }

        if ($increment > $eps && $newPaymentOptionId !== null) {
            if (! PaymentOption::visible()->whereKey($newPaymentOptionId)->exists()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['payment_option_id' => 'Selected channel is not available for payments. Open Channels and use Show, or pick another account.']);
            }
        }
        $newPaidDate = $validated['paid_date'] ?? null;

        // Payment channel balances: delta on same channel; refund old + charge full cumulative on switch; refund when channel removed
        $oldOptId = $oldPaymentOption !== null ? (int) $oldPaymentOption : null;
        $newOptId = $newPaymentOptionId;

        if ($newOptId === null && $oldOptId !== null && $oldPaidAmount > $eps) {
            $oldOption = PaymentOption::find($oldOptId);
            if ($oldOption) {
                $oldOption->increment('balance', $oldPaidAmount);
            }
        } elseif ($oldOptId !== null && $newOptId !== null && $oldOptId !== $newOptId) {
            if ($oldPaidAmount > $eps) {
                $oldOption = PaymentOption::find($oldOptId);
                if ($oldOption) {
                    $oldOption->increment('balance', $oldPaidAmount);
                }
            }
            if ($newPaidAmount > $eps) {
                $paymentOption = PaymentOption::visible()->find($newOptId);
                if (! $paymentOption) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['payment_option_id' => 'Selected channel is not available for payments.']);
                }
                if ($paymentOption->balance + $eps >= $newPaidAmount) {
                    $paymentOption->decrement('balance', $newPaidAmount);
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['payment_option_id' => 'Insufficient balance in selected payment channel.']);
                }
            }
        } elseif ($newOptId !== null) {
            $paymentOption = PaymentOption::visible()->find($newOptId);
            if (! $paymentOption) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['payment_option_id' => 'Selected channel is not available for payments.']);
            }
            $deltaToApply = $paymentDifference;
            if ($oldOptId === null && $paymentDifference <= $eps && $oldPaidAmount > $eps) {
                $deltaToApply = $oldPaidAmount;
            }
            if ($deltaToApply > $eps) {
                if ($paymentOption->balance + $eps >= $deltaToApply) {
                    $paymentOption->decrement('balance', $deltaToApply);
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['paid_amount' => 'Insufficient balance in selected payment channel for this payment.']);
                }
            } elseif ($deltaToApply < -$eps) {
                $paymentOption->increment('balance', abs($deltaToApply));
            }
        }

        // Prepare update data
        $updateData = [
            'name' => $validated['name'] ?? $purchase->name,
            'sell_price' => $validated['sell_price'] ?? null,
            'paid_date' => $newPaidDate,
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'payment_receipt_image' => $paymentReceiptPath,
        ];
        
        // Only add payment_option_id if the column exists (migration has been run)
        try {
            $columns = Schema::getColumnListing('purchases');
            if (in_array('payment_option_id', $columns)) {
                $updateData['payment_option_id'] = $newPaymentOptionId;
            }
        } catch (\Exception $e) {
            Log::warning('payment_option_id column not found in purchases table. Migration may need to be run.');
        }
        
        $purchase->update($updateData);

        // Record one history row per incremental payment (amount = delta for this save)
        if ($paymentDifference > $eps) {
            try {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'payment_option_id' => $newOptId,
                    'amount' => $paymentDifference,
                    'paid_date' => $newPaidDate ?? now()->toDateString(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create purchase payment record: ' . $e->getMessage());
            }
        }

        // Update product price to use the latest purchase's sell_price (if available)
        // This ensures front page products show the correct sell_price instead of unit_price
        if ($purchase->product) {
            $latestPurchase = Purchase::where('product_id', $purchase->product_id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->latest('id')
                ->first();
            
            if ($latestPurchase && $latestPurchase->sell_price) {
                $purchase->product->update(['price' => $latestPurchase->sell_price]);
            }
        }

        $editRoute = $purchase->isPassthrough() ? 'admin.stock.edit-passthrough' : 'admin.stock.edit-purchase';
        $successLabel = $purchase->isPassthrough() ? 'Passthrough updated successfully.' : 'Purchase updated successfully.';

        return redirect()
            ->route($editRoute, $purchase->id)
            ->with('success', $successLabel);
    }

    public function destroyPurchasePayment(Request $request, $id, int $paymentId)
    {
        return $this->destroyPurchasePaymentRecord($request, $id, $paymentId, passthrough: false);
    }

    public function destroyPassthroughPayment(Request $request, $id, int $paymentId)
    {
        return $this->destroyPurchasePaymentRecord($request, $id, $paymentId, passthrough: true);
    }

    private function destroyPurchasePaymentRecord(Request $request, $id, int $paymentId, bool $passthrough = false)
    {
        $purchase = ($passthrough ? Purchase::passthrough() : Purchase::stockPurchases())
            ->findOrFail($id);

        $payment = PurchasePayment::query()
            ->where('purchase_id', $purchase->id)
            ->with('paymentOption')
            ->findOrFail($paymentId);

        $amount = (float) $payment->amount;
        $eps = 0.0001;
        $totalAmount = (float) ($purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price));

        DB::transaction(function () use ($payment, $purchase, $amount, $eps, $totalAmount) {
            if ($amount > $eps && $payment->paymentOption) {
                $payment->paymentOption->increment('balance', $amount);
            }

            $oldPaid = (float) ($purchase->paid_amount ?? 0);
            $newPaid = max(0, $oldPaid - $amount);
            $status = $newPaid >= $totalAmount - $eps ? 'paid' : ($newPaid > $eps ? 'partial' : 'pending');

            $latestRemaining = PurchasePayment::query()
                ->where('purchase_id', $purchase->id)
                ->where('id', '!=', $payment->id)
                ->orderByDesc('paid_date')
                ->orderByDesc('id')
                ->first();

            $updateData = [
                'paid_amount' => $newPaid,
                'payment_status' => $status,
                'paid_date' => $newPaid > $eps ? $latestRemaining?->paid_date : null,
            ];

            try {
                $columns = Schema::getColumnListing('purchases');
                if (in_array('payment_option_id', $columns, true)) {
                    $updateData['payment_option_id'] = $newPaid > $eps ? $latestRemaining?->payment_option_id : null;
                }
            } catch (\Exception $e) {
                Log::warning('payment_option_id column not found in purchases table. Migration may need to be run.');
            }

            $purchase->update($updateData);
            $payment->delete();
        });

        $editRoute = $passthrough ? 'admin.stock.edit-passthrough' : 'admin.stock.edit-purchase';
        $successLabel = $passthrough
            ? 'Payment deleted. Passthrough balance and channel updated.'
            : 'Payment deleted. Purchase balance and channel updated.';

        return redirect()
            ->route($editRoute, $purchase->id)
            ->with('success', $successLabel);
    }

    public function destroyPurchase($id, bool $passthrough = false)
    {
        $purchase = ($passthrough ? Purchase::passthrough() : Purchase::stockPurchases())
            ->with(['product', 'productListItems'])
            ->findOrFail($id);
        $purchaseQty = (int) ($purchase->quantity ?? 0);
        $productId = $purchase->product_id;

        try {
            DB::transaction(function () use ($purchase, $id, $purchaseQty, $productId) {
                if (! empty($purchase->payment_receipt_image) && Storage::disk('public')->exists($purchase->payment_receipt_image)) {
                    try {
                        Storage::disk('public')->delete($purchase->payment_receipt_image);
                    } catch (\Throwable $e) {
                        Log::warning('Purchase receipt delete failed: '.$e->getMessage());
                    }
                }

                $items = ProductListItem::where('purchase_id', $id)->get();

                $agentSaleIds = $items->pluck('agent_sale_id')->filter()->unique()->values();
                foreach ($agentSaleIds as $saleId) {
                    $sale = AgentSale::lockForUpdate()->find($saleId);
                    if (! $sale) {
                        continue;
                    }
                    $qty = (int) ($sale->quantity_sold ?? 0);
                    $saleProductId = $sale->product_id;
                    $this->applyAgentSaleRemovalEffects($sale);
                    DB::table('agent_sales')->where('id', $sale->id)->delete();
                    if ($saleProductId && $qty > 0) {
                        Product::whereKey($saleProductId)->increment('stock_quantity', $qty);
                    }
                }

                $pendingIds = $items->pluck('pending_sale_id')->filter()->unique()->values();
                foreach ($pendingIds as $pid) {
                    DB::table('pending_sales')->where('id', $pid)->delete();
                }

                $creditIds = $items->pluck('agent_credit_id')->filter()->unique()->values();
                foreach ($creditIds as $cid) {
                    DB::table('agent_credits')->where('id', $cid)->delete();
                }

                DB::table('product_list')->where('purchase_id', $id)->delete();

                DB::table('purchases')->where('id', $id)->delete();

                if ($productId && $purchaseQty > 0) {
                    $p = Product::lockForUpdate()->find($productId);
                    if ($p) {
                        $p->update([
                            'stock_quantity' => max(0, (int) $p->stock_quantity - $purchaseQty),
                        ]);
                    }
                }
            });
        } catch (\RuntimeException $e) {
            return redirect()->route($passthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases')
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Purchase delete failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route($passthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases')
                ->withErrors(['error' => 'Could not delete this record. It may be linked to data that block removal.']);
        }

        $listRoute = $passthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
        $successMessage = $passthrough
            ? 'Passthrough deleted successfully.'
            : 'Purchase deleted, including linked stock and agent sale / credit / pending data.';

        return redirect()->route($listRoute)->with('success', $successMessage);
    }

    // Distribution Sales
    public function createDistribution()
    {
        $dealers = User::where('role', 'dealer')->orderBy('name')->get();

        $purchases = Purchase::stockPurchases()
            ->with(['product.category', 'lines.product.category'])
            ->where(function ($q) {
                $q->whereNotNull('product_id')->orWhereHas('lines');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $purchaseRegisterMeta = $purchases->mapWithKeys(function (Purchase $purchase) {
            return [
                (int) $purchase->id => $this->resolvePurchaseRegistrationRows($purchase, persistLimits: false),
            ];
        })->all();

        return view('admin.stock.create-distribution', compact('dealers', 'purchases', 'purchaseRegisterMeta'));
    }

    /**
     * JSON: catalog models on this purchase (lines or header) with available IMEI counts for distribution.
     */
    public function distributionModelsForPurchase(Purchase $purchase)
    {
        if ($purchase->isPassthrough()) {
            abort(404);
        }

        $purchase->load(['lines.product.category', 'product.category']);
        $purchaseId = (int) $purchase->id;

        // Use already-loaded product relations to avoid a secondary whereIn query.
        $products = collect();
        if ($purchase->product) {
            $products->put((int) $purchase->product->id, $purchase->product);
        }
        foreach ($purchase->lines as $line) {
            if ($line->product && ! $products->has((int) $line->product->id)) {
                $products->put((int) $line->product->id, $line->product);
            }
        }

        $registeredProductIds = ProductListItem::onPurchaseStock($purchaseId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id);

        foreach ($registeredProductIds as $rpid) {
            if (! $products->has($rpid)) {
                $extra = Product::with('category')->find($rpid);
                if ($extra) {
                    $products->put($rpid, $extra);
                }
            }
        }

        if ($products->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $pricingService = app(DistributionSaleService::class);

        $data = $products->map(function ($product) use ($purchase, $purchaseId, $pricingService) {
            $pid = (int) $product->id;

            $available = ProductListItem::availableForDistribution($pid)
                ->onPurchaseStock($purchaseId)
                ->count();
            $totalRegistered = ProductListItem::onPurchaseStock($purchaseId)
                ->matchingCatalogProduct($pid)
                ->count();
            $categoryName = $product->category?->name ?? '—';
            $label = $categoryName.' — '.$product->name;
            $prices = $pricingService->getPricesForProductOnPurchase($pid, $purchase);

            $pickerSuffix = $available > 0
                ? $available.' IMEI'.($available === 1 ? '' : 's').' on this purchase'
                : ($totalRegistered > 0
                    ? $totalRegistered.' registered — none free to sell'
                    : '0 registered — add IMEIs in Register IMEIs tab');

            return [
                'product_id' => $pid,
                'label' => $label,
                'available_imeis' => $available,
                'total_registered' => $totalRegistered,
                'unit_price' => $prices['buy'],
                'sell_price' => $prices['sell'],
                'suggest' => $prices['sell'],
                'picker_label' => $label.' ('.$pickerSuffix.')',
            ];
        })
            ->sortBy('label')
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    /**
     * JSON: unsold IMEIs available to sell to a dealer for this catalog product on a purchase.
     */
    public function distributionAssignableImeis(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:models,id',
            'purchase_id' => 'required|integer|exists:purchases,id',
        ]);

        $productId = (int) $validated['product_id'];
        $purchaseId = (int) $validated['purchase_id'];

        $items = ProductListItem::onPurchaseStock($purchaseId)
            ->matchingCatalogProduct($productId)
            ->with([
                'distributionSale:id,dealer_name,date,status',
                'regionalManagerProductListAssignment.regionalManager:id,name',
                'teamLeaderProductListAssignment.teamLeader:id,name',
                'agentProductListAssignment.agent:id,name',
            ])
            ->orderBy('imei_number')
            ->get(['id', 'imei_number', 'model', 'sold_at', 'agent_sale_id', 'distribution_sale_id', 'pending_sale_id', 'agent_credit_id']);

        $rows = $items->map(function (ProductListItem $item) {
            $status = $item->custodyStatusForAdminAssign();

            return [
                'id' => $item->id,
                'imei_number' => $item->imei_number,
                'model' => $item->model,
                'text' => $item->imei_number.($item->model ? ' – '.$item->model : ''),
                'selectable' => $status['selectable'],
                'status' => $status['code'],
                'status_label' => $status['label'],
            ];
        })->values();

        $summary = [
            'total' => $rows->count(),
            'available' => $rows->where('selectable', true)->count(),
            'in_distribution' => $rows->where('status', 'distribution')->count(),
            'other' => $rows->where('selectable', false)->where('status', '!=', 'distribution')->count(),
        ];

        return response()->json([
            'data' => $rows->all(),
            'summary' => $summary,
        ]);
    }

    /**
     * JSON: register IMEIs on a purchase from distribution create (optional panel).
     */
    public function distributionRegisterImeis(Request $request, PurchaseImeiRegistrationService $registrationService)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|integer|exists:purchases,id',
            'catalog_product_id' => 'required|integer|exists:models,id',
            'imei_numbers' => 'required|string|max:65535',
        ]);

        $purchase = Purchase::stockPurchases()
            ->with(['product', 'stock', 'lines'])
            ->findOrFail((int) $validated['purchase_id']);

        $result = $registrationService->register(
            $purchase,
            (int) $validated['catalog_product_id'],
            (string) $validated['imei_numbers'],
            oneImeiPerLine: true
        );

        if ($result->hasValidationError()) {
            return response()->json([
                'ok' => false,
                'field' => $result->errorField,
                'message' => $result->errorMessage,
            ], 422);
        }

        if (! $result->succeeded()) {
            return response()->json([
                'ok' => false,
                'field' => 'imei_numbers',
                'message' => $result->errorMessage ?? 'No devices were added.',
            ], 422);
        }

        $purchase->refresh()->load('lines');

        $models = $this->purchaseModelsForRegistration($purchase);

        $pricing = app(DistributionSaleService::class)
            ->getPricesForProductOnPurchase((int) $validated['catalog_product_id'], $purchase);

        return response()->json([
            'ok' => true,
            'created' => $result->created,
            'parsed' => $result->parsedCount,
            'failed' => $result->failed,
            'items' => $result->createdItems,
            'catalog_product_id' => (int) $validated['catalog_product_id'],
            'unit_price' => $pricing['buy'],
            'sell_price' => $pricing['sell'],
            'purchase_limit_remaining' => (int) $purchase->limit_remaining,
            'model_limit_remaining' => $result->modelLimitRemaining,
            'models' => $models,
            'message' => 'Added '.$result->created.' device(s) to the purchase and this sale.',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function purchaseModelsForRegistration(Purchase $purchase): array
    {
        return collect($this->resolvePurchaseRegistrationRows($purchase, persistLimits: true))
            ->filter(fn (array $row) => (int) ($row['limit_remaining'] ?? 0) > 0)
            ->map(fn (array $row) => [
                'product_id' => $row['product_id'],
                'limit_remaining' => $row['limit_remaining'],
                'can_register' => $row['can_register'],
                'unit_price' => $row['unit_price'],
                'sell_price' => $row['sell_price'],
                'label' => ($row['category_name'] ?? '—').' — '.$row['model'].' · slots '.$row['limit_remaining'],
            ])
            ->values()
            ->all();
    }

    /**
     * Models on a purchase for IMEI registration (line items + header product fallback).
     *
     * @return list<array<string, mixed>>
     */
    private function resolvePurchaseRegistrationRows(Purchase $purchase, bool $persistLimits = true): array
    {
        if ($persistLimits) {
            $purchase->recalculateLimitRemaining();
        }

        $purchase->loadMissing([
            'lines.product.category:id,name',
            'product.category:id,name',
        ]);

        $rows = collect();
        $seenProductIds = [];

        foreach ($purchase->lines as $line) {
            $limitRemaining = $persistLimits
                ? (int) $line->limit_remaining
                : $purchase->openSlotsForLine($line);

            $row = $this->purchaseModelRowForRegistration(
                $line->product,
                $limitRemaining,
                (float) $line->unit_price,
                $line->sell_price
            );
            if (! $row) {
                continue;
            }

            $rows->push($row);
            $seenProductIds[(int) $row['product_id']] = true;
        }

        if ($purchase->product) {
            $headerProductId = (int) $purchase->product->id;
            if (! isset($seenProductIds[$headerProductId])) {
                $limitRemaining = $persistLimits
                    ? (int) ($purchase->limit_remaining ?? 0)
                    : $purchase->openSlotsForHeaderProduct();

                $row = $this->purchaseModelRowForRegistration(
                    $purchase->product,
                    $limitRemaining,
                    (float) ($purchase->unit_price ?? 0),
                    $purchase->sell_price
                );
                if ($row) {
                    $rows->push($row);
                }
            }
        }

        return $rows->values()->all();
    }

    public function storeDistribution(Request $request)
    {
        if ($request->filled('paid_amount') === false || trim((string) $request->input('paid_amount', '')) === '') {
            $request->merge(['paid_amount' => null]);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'dealer_id' => 'required|exists:users,id',
            'seller_name' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.purchase_id' => 'required|integer|exists:purchases,id',
            'lines.*.product_id' => 'required|integer|exists:models,id',
            'lines.*.product_list_ids' => 'required|array|min:1',
            'lines.*.product_list_ids.*' => 'integer|distinct|exists:product_list,id',
            'lines.*.sell_price' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $service = app(DistributionSaleService::class);
        $dealer = User::findOrFail((int) $validated['dealer_id']);
        $dealerName = $dealer->business_name ?? $dealer->name;

        $purchaseCache = [];
        $resolvePurchase = function (int $purchaseId) use (&$purchaseCache): Purchase {
            if (! isset($purchaseCache[$purchaseId])) {
                $purchaseCache[$purchaseId] = Purchase::stockPurchases()->with('lines')->findOrFail($purchaseId);
            }

            return $purchaseCache[$purchaseId];
        };

        $linePayloads = [];
        $grandSellingTotal = 0.0;
        $usedImeiIds = [];

        foreach ($validated['lines'] as $lineIndex => $line) {
            $purchaseId = (int) $line['purchase_id'];
            $purchase = $resolvePurchase($purchaseId);
            $pid = (int) $line['product_id'];
            $imeiIds = array_values(array_unique(array_map('intval', $line['product_list_ids'] ?? [])));
            $product = Product::findOrFail($pid);
            $prices = $service->getPricesForProductOnPurchase($pid, $purchase);
            $buyPrice = $prices['buy'];
            $formSellRaw = $line['sell_price'] ?? null;
            $sellUnit = ($formSellRaw !== null && $formSellRaw !== '')
                ? (float) $formSellRaw
                : (float) $prices['sell'];

            // Fall back: use buy price as sell when no sell price is set (prevents blocking
            // legitimate sales where only unit_price was entered without an explicit sell_price).
            if ($sellUnit <= 0 && $buyPrice > 0) {
                $sellUnit = $buyPrice;
            }

            if ($sellUnit <= 0) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'lines' => 'No sell price on the selected purchase for '.$product->name.'. Set a sell price on the purchase or its lines before creating this sale.',
                    ]);
            }

            foreach ($imeiIds as $imeiId) {
                if (isset($usedImeiIds[$imeiId])) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors([
                            'lines' => 'The same IMEI cannot appear on more than one line in this sale.',
                        ]);
                }
                $usedImeiIds[$imeiId] = true;
            }

            $items = ProductListItem::availableForDistribution($pid)
                ->onPurchaseStock($purchaseId)
                ->whereIn('id', $imeiIds)
                ->get();

            if ($items->count() !== count($imeiIds)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'lines' => "One or more IMEIs on line ".($lineIndex + 1)." are not available for {$product->name} on this purchase (sold, assigned, wrong product, or different purchase).",
                    ]);
            }

            foreach ($items as $item) {
                if (! $item->isCatalogProduct($pid)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors([
                            'lines' => "IMEI {$item->imei_number} does not belong to {$product->name}.",
                        ]);
                }
            }

            $qty = count($imeiIds);
            $stock = (int) ($product->stock_quantity ?? 0);
            if ($qty > $stock) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'lines' => "Insufficient stock for {$product->name}: selected {$qty} device(s), available {$stock}.",
                    ]);
            }

            $totalSell = $qty * $sellUnit;
            $totalBuy = $qty * $buyPrice;
            $grandSellingTotal += $totalSell;

            $linePayloads[] = [
                'product' => $product,
                'quantity_sold' => $qty,
                'product_list_ids' => $imeiIds,
                'purchase_price' => $buyPrice,
                'selling_price' => $sellUnit,
                'total_selling_value' => $totalSell,
                'total_purchase_value' => $totalBuy,
                'profit' => $totalSell - $totalBuy,
            ];
        }

        $paidTotal = (float) ($validated['paid_amount'] ?? 0);
        $eps = 0.0001;
        if ($paidTotal > $grandSellingTotal + $eps * max(1, count($linePayloads))) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['paid_amount' => 'Paid amount cannot exceed the total for all lines.']);
        }

        $lineTotals = array_map(fn ($p) => (float) $p['total_selling_value'], $linePayloads);
        $paidShares = $this->allocateDistributionPaidAcrossLines($paidTotal, $grandSellingTotal, $lineTotals);

        $hasDistributionSaleIdOnList = Schema::hasColumn('product_list', 'distribution_sale_id');
        $hasInvoiceNumberColumn = Schema::hasColumn('distribution_sales', 'invoice_number');

        DB::transaction(function () use ($validated, $dealerName, $request, $linePayloads, $paidShares, $eps, $hasDistributionSaleIdOnList, $hasInvoiceNumberColumn) {
            foreach ($linePayloads as $idx => $payload) {
                $paidLine = (float) ($paidShares[$idx] ?? 0);
                $totalSell = (float) $payload['total_selling_value'];
                $balance = max(0, $totalSell - $paidLine);
                $attrs = [
                    'date' => $validated['date'],
                    'dealer_id' => (int) $validated['dealer_id'],
                    'dealer_name' => $dealerName,
                    'seller_name' => $validated['seller_name'] ?? $request->user()?->name,
                    'product_id' => $payload['product']->id,
                    'quantity_sold' => $payload['quantity_sold'],
                    'purchase_price' => $payload['purchase_price'],
                    'selling_price' => $payload['selling_price'],
                    'total_purchase_value' => $payload['total_purchase_value'],
                    'total_selling_value' => $totalSell,
                    'to_be_paid' => $totalSell,
                    'commission' => 0,
                    'profit' => $payload['profit'],
                    'paid_amount' => $paidLine,
                    'balance' => $balance,
                    'status' => $paidLine >= $totalSell - $eps ? 'complete' : 'pending',
                ];

                if ($hasInvoiceNumberColumn) {
                    $attrs['invoice_number'] = DocumentNumberGenerator::nextDistributionSale($validated['date'] ?? null);
                }

                $sale = DistributionSale::create($attrs);

                $imeiUpdate = ['sold_at' => now()];
                if ($hasDistributionSaleIdOnList) {
                    $imeiUpdate['distribution_sale_id'] = $sale->id;
                }
                ProductListItem::whereIn('id', $payload['product_list_ids'])->update($imeiUpdate);

                Product::where('id', $payload['product']->id)->decrement('stock_quantity', $payload['quantity_sold']);
            }
        });

        $count = count($linePayloads);
        $msg = $count === 1
            ? 'Distribution sale recorded successfully.'
            : "Recorded {$count} distribution sale lines successfully.";

        return redirect()->route('admin.stock.distribution')->with('success', $msg);
    }

    /**
     * Split a single paid_amount across multiple distribution lines by each line’s share of billed total.
     *
     * @param  array<int, float>  $lineSellingTotals
     * @return array<int, float>
     */
    private function allocateDistributionPaidAcrossLines(float $paidTotal, float $grandTotal, array $lineSellingTotals): array
    {
        $paidTotal = max(0, min($paidTotal, $grandTotal));
        $n = count($lineSellingTotals);
        if ($n === 0) {
            return [];
        }
        if ($paidTotal <= 0.00001 || $grandTotal <= 0.00001) {
            return array_fill(0, $n, 0.0);
        }

        $out = [];
        $allocated = 0.0;
        foreach ($lineSellingTotals as $i => $lt) {
            if ($i === $n - 1) {
                $rest = round($paidTotal - $allocated, 2);
                $out[$i] = max(0, min($lt, $rest));

                continue;
            }
            $share = round($paidTotal * ($lt / $grandTotal), 2);
            $share = min($share, $lt, max(0, round($paidTotal - $allocated, 2)));
            $out[$i] = $share;
            $allocated += $share;
        }

        $sumAllocated = array_sum($out);
        if (abs($sumAllocated - $paidTotal) > 0.009 && $n > 0) {
            $drift = round($paidTotal - $sumAllocated, 2);
            $last = $n - 1;
            $out[$last] = max(0, min((float) $lineSellingTotals[$last], $out[$last] + $drift));
        }

        return $out;
    }

    public function editDistribution($id)
    {
        $sale = DistributionSale::with(['product.category', 'dealer', 'payments.paymentOption'])->findOrFail($id);
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();

        return view('admin.stock.edit-distribution', compact('sale', 'paymentOptions'));
    }

    public function downloadDistributionInvoice($id)
    {
        $sale = DistributionSale::with(['product.category', 'dealer', 'payments.paymentOption'])->findOrFail($id);

        $invoiceNo = $sale->displayInvoiceNumber();
        $safeDate = ($sale->date ? Carbon::parse($sale->date)->format('Ymd') : now()->format('Ymd'));
        $filename = "distribution-invoice-{$invoiceNo}-{$safeDate}.pdf";

        return PdfDownload::fromView('admin.stock.distribution-invoice', compact('sale', 'invoiceNo'), $filename);
    }

    /**
     * One PDF: all distribution rows for a dealer in a date range with outstanding balance &gt; 0.
     */
    public function downloadConsolidatedDistributionInvoice(Request $request)
    {
        $validated = $request->validate([
            'dealer_id' => 'required|integer|exists:users,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dealer = User::query()
            ->where('id', (int) $validated['dealer_id'])
            ->where('role', 'dealer')
            ->firstOrFail();

        $from = Carbon::parse($validated['date_from'])->startOfDay();
        $to = Carbon::parse($validated['date_to'])->endOfDay();

        $sales = DistributionSale::query()
            ->with(['product.category'])
            ->where('dealer_id', $dealer->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $eps = 0.0001;
        $outstandingFn = function (DistributionSale $s): float {
            if ($s->balance !== null) {
                return max(0, (float) $s->balance);
            }
            $t = (float) ($s->total_selling_value ?? 0);
            $p = (float) ($s->paid_amount ?? 0);

            return max(0, $t - $p);
        };

        $lines = $sales->filter(fn (DistributionSale $s) => $outstandingFn($s) > $eps)->values();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('admin.stock.distribution', $request->only(['date_from', 'date_to']))
                ->with('info', 'No outstanding distribution invoices for that dealer in the selected date range.');
        }

        $lineRows = $lines->map(function (DistributionSale $s) use ($outstandingFn) {
            $totalSell = (float) ($s->total_selling_value ?? 0);
            $paid = (float) ($s->paid_amount ?? 0);
            $productName = $s->product
                ? (($s->product->category?->name ?? 'N/A').' - '.$s->product->name)
                : 'N/A';

            return [
                'id' => $s->id,
                'invoice_number' => $s->displayInvoiceNumber(),
                'date' => $s->date ? Carbon::parse($s->date)->format('Y-m-d') : null,
                'product_name' => $productName,
                'quantity' => (int) ($s->quantity_sold ?? 0),
                'total_sell' => $totalSell,
                'paid' => $paid,
                'outstanding' => $outstandingFn($s),
            ];
        })->values()->all();

        $totalOutstanding = (float) collect($lineRows)->sum('outstanding');
        $dealerBusinessName = $dealer->business_name ?? $dealer->name;
        $invoiceNo = 'CONS-'.$dealer->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd');
        $filename = 'distribution-consolidated-'.strtolower($invoiceNo).'.pdf';
        $periodLabel = $from->format('d M Y').' – '.$to->format('d M Y');

        return PdfDownload::fromView('admin.stock.distribution-invoice-consolidated', compact(
            'dealer',
            'dealerBusinessName',
            'lineRows',
            'totalOutstanding',
            'invoiceNo',
            'periodLabel',
        ), $filename);
    }

    public function updateDistribution(Request $request, $id)
    {
        $sale = DistributionSale::findOrFail($id);

        $incrementPreview = max(0, (float) ($request->input('paid_amount') ?? 0));
        $eps = 0.0001;
        $paymentOptionRules = $incrementPreview > $eps
            ? 'required|exists:payment_options,id'
            : 'nullable|exists:payment_options,id';

        $validated = $request->validate([
            'paid_amount' => 'nullable|numeric|min:0',
            'collection_date' => 'nullable|date',
            'payment_option_id' => $paymentOptionRules,
        ]);

        // Form "paid_amount" is incremental ("Pay this time"); persist cumulative total (same as purchases).
        $totalSelling = (float) ($sale->total_selling_value ?? 0);
        $oldPaidAmount = (float) ($sale->paid_amount ?? 0);
        $increment = max(0, (float) ($validated['paid_amount'] ?? 0));
        $remaining = max(0, $totalSelling - $oldPaidAmount);

        if ($increment > $remaining + $eps) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['paid_amount' => 'Pay amount cannot exceed the remaining balance for this sale.']);
        }

        $newPaidAmount = min($totalSelling, $oldPaidAmount + $increment);
        $paymentDifference = $newPaidAmount - $oldPaidAmount;

        $newPaymentOptionId = $validated['payment_option_id'] ?? null;
        if ($newPaymentOptionId === '' || $newPaymentOptionId === false) {
            $newPaymentOptionId = null;
        } else {
            $newPaymentOptionId = (int) $newPaymentOptionId;
        }

        // Dealer payment received: credit the selected channel balance (purchases debit when paying out).
        if ($paymentDifference > $eps && $newPaymentOptionId !== null) {
            $paymentOption = PaymentOption::find($newPaymentOptionId);
            if ($paymentOption) {
                $paymentOption->increment('balance', $paymentDifference);
            }
        }

        $newStatus = $newPaidAmount >= $totalSelling - $eps ? 'complete' : 'pending';

        $update = [
            'paid_amount' => $newPaidAmount,
            'balance' => max(0, $totalSelling - $newPaidAmount),
            'collection_date' => $validated['collection_date'] ?? $sale->collection_date,
            'status' => $newStatus,
        ];

        if (Schema::hasColumn('distribution_sales', 'payment_option_id')) {
            $update['payment_option_id'] = $newPaymentOptionId;
        }

        $sale->update($update);

        if ($paymentDifference > $eps) {
            try {
                $paidDate = !empty($validated['collection_date'])
                    ? \Carbon\Carbon::parse($validated['collection_date'])->toDateString()
                    : now()->toDateString();
                DistributionSalePayment::create([
                    'distribution_sale_id' => $sale->id,
                    'payment_option_id' => $newPaymentOptionId,
                    'amount' => $paymentDifference,
                    'paid_date' => $paidDate,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create distribution sale payment record: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.stock.edit-distribution', $sale->id)
            ->with('success', 'Distribution sale updated successfully.');
    }

    public function destroyDistribution($id)
    {
        $sale = DistributionSale::findOrFail($id);
        $product = $sale->product;
        $quantitySold = (int) ($sale->quantity_sold ?? 0);

        DB::transaction(function () use ($sale, $product, $quantitySold) {
            if (Schema::hasColumn('product_list', 'distribution_sale_id')) {
                ProductListItem::where('distribution_sale_id', $sale->id)->update([
                    'sold_at' => null,
                    'distribution_sale_id' => null,
                ]);
            }

            DB::table('distribution_sales')->where('id', $sale->id)->delete();

            if ($product && $quantitySold > 0) {
                $product->increment('stock_quantity', $quantitySold);
            }
        });

        return redirect()->route('admin.stock.distribution')->with('success', 'Distribution sale deleted successfully.');
    }

    // Agent Sales
    public function createAgentSale()
    {
        $purchases = Purchase::stockPurchases()
            ->with(['product.category', 'lines.product.category'])
            ->where(function ($q) {
                $q->whereNotNull('product_id')->orWhereHas('lines');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('admin.stock.create-agent-sale', compact('purchases'));
    }

    /**
     * JSON: models on a purchase with warehouse units available for manual agent sale.
     */
    public function agentSaleModelsForPurchase(Purchase $purchase)
    {
        if ($purchase->isPassthrough()) {
            abort(404);
        }

        $purchase->load(['lines.product.category', 'product.category']);
        $purchaseId = (int) $purchase->id;

        $products = collect();
        if ($purchase->product) {
            $products->put((int) $purchase->product->id, $purchase->product);
        }
        foreach ($purchase->lines as $line) {
            if ($line->product && ! $products->has((int) $line->product->id)) {
                $products->put((int) $line->product->id, $line->product);
            }
        }

        $registeredProductIds = ProductListItem::onPurchaseStock($purchaseId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id);

        foreach ($registeredProductIds as $rpid) {
            if (! $products->has($rpid)) {
                $extra = Product::with('category')->find($rpid);
                if ($extra) {
                    $products->put($rpid, $extra);
                }
            }
        }

        if ($products->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $pricingService = app(DistributionSaleService::class);

        $data = $products->map(function ($product) use ($purchase, $purchaseId, $pricingService) {
            $pid = (int) $product->id;

            $available = ProductListItem::assignableFromAdminOnPurchase($purchaseId, $pid)->count();
            $totalRegistered = ProductListItem::onPurchaseStock($purchaseId)
                ->matchingCatalogProduct($pid)
                ->count();
            $categoryName = $product->category?->name ?? '—';
            $label = $categoryName.' — '.$product->name;
            $prices = $pricingService->getPricesForProductOnPurchase($pid, $purchase);
            $sell = $prices['sell'] > 0 ? $prices['sell'] : $prices['buy'];

            $pickerSuffix = $available > 0
                ? $available.' available on this purchase'
                : ($totalRegistered > 0
                    ? $totalRegistered.' registered — none free in warehouse'
                    : '0 registered — add IMEIs on the purchase first');

            return [
                'product_id' => $pid,
                'label' => $label,
                'available_units' => $available,
                'total_registered' => $totalRegistered,
                'unit_price' => $prices['buy'],
                'sell_price' => $sell,
                'suggest' => $sell,
                'picker_label' => $label.' ('.$pickerSuffix.')',
            ];
        })
            ->sortBy('label')
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    public function storeAgentSale(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'customer_name' => 'required|string|max:255',
            'seller_name' => 'nullable|string|max:255',
            'purchase_id' => 'required|integer|exists:purchases,id',
            'product_id' => 'required|exists:models,id',
            'quantity_sold' => 'required|integer|min:1',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $purchase = Purchase::stockPurchases()->with('lines')->findOrFail((int) $validated['purchase_id']);
        $productId = (int) $validated['product_id'];
        $qty = (int) $validated['quantity_sold'];

        $service = app(DistributionSaleService::class);
        $prices = $service->getPricesForProductOnPurchase($productId, $purchase);
        $buyPrice = $prices['buy'];
        $sellUnit = (float) $validated['selling_price'];

        if ($sellUnit <= 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['selling_price' => 'Selling price must be greater than 0.']);
        }

        $items = ProductListItem::assignableFromAdminOnPurchase((int) $purchase->id, $productId)
            ->orderBy('id')
            ->limit($qty)
            ->get();

        if ($items->count() < $qty) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'quantity_sold' => 'Only '.$items->count().' device(s) available on this purchase for the selected model.',
                ]);
        }

        $validated['purchase_price'] = $buyPrice;
        $validated['total_selling_value'] = $qty * $sellUnit;
        $validated['total_purchase_value'] = $qty * $buyPrice;
        $validated['profit'] = $validated['total_selling_value'] - $validated['total_purchase_value'];
        $validated['selling_price'] = $sellUnit;

        $itemIds = $items->pluck('id')->all();

        DB::transaction(function () use ($validated, $request, $itemIds, $qty) {
            $attrs = [
                'customer_name' => $validated['customer_name'],
                'seller_name' => $validated['seller_name'] ?? $request->user()?->name,
                'product_id' => $validated['product_id'],
                'quantity_sold' => $qty,
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'total_purchase_value' => $validated['total_purchase_value'],
                'total_selling_value' => $validated['total_selling_value'],
                'profit' => $validated['profit'],
                'date' => $validated['date'],
            ];

            if (Schema::hasColumn('pending_sales', 'seller_id') && $request->user()) {
                $attrs['seller_id'] = $request->user()->id;
            }

            $pendingSale = \App\Models\PendingSale::create($attrs);

            ProductListItem::whereIn('id', $itemIds)->update([
                'pending_sale_id' => $pendingSale->id,
            ]);

            \App\Models\Product::where('id', $validated['product_id'])->decrement('stock_quantity', $qty);
        });

        return redirect()->route('admin.stock.pending-sales')->with('success', 'Sale recorded successfully. Please select payment option and save.');
    }

    public function pendingSales()
    {
        $pendingSales = \App\Models\PendingSale::with(['product.category', 'paymentOption'])
            ->latest('date')
            ->paginate(50)
            ->withQueryString();
        $paymentOptions = \App\Models\PaymentOption::visible()->orderBy('name')->get();

        return view('admin.stock.pending-sales', compact('pendingSales', 'paymentOptions'));
    }

    public function savePendingSale(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_option_id' => 'required|exists:payment_options,id',
        ]);

        $pendingSale = \App\Models\PendingSale::findOrFail($id);
        $pendingSale->update($validated);

        // Add amount to payment option balance
        if ($pendingSale->paymentOption) {
            $pendingSale->paymentOption->increment('balance', $pendingSale->total_selling_value);
        }

        // Move to agent_sales table
        $agentSaleAttrs = [
            'customer_name' => $pendingSale->customer_name,
            'seller_name' => $pendingSale->seller_name,
            'product_id' => $pendingSale->product_id,
            'quantity_sold' => $pendingSale->quantity_sold,
            'purchase_price' => $pendingSale->purchase_price,
            'selling_price' => $pendingSale->selling_price,
            'total_purchase_value' => $pendingSale->total_purchase_value,
            'total_selling_value' => $pendingSale->total_selling_value,
            'profit' => $pendingSale->profit,
            'balance' => 0, // Already paid via payment option
            'date' => $pendingSale->date,
        ];
        if (Schema::hasColumn('agent_sales', 'agent_id') && $pendingSale->seller_id) {
            $agentSaleAttrs['agent_id'] = $pendingSale->seller_id;
        }
        if (Schema::hasColumn('agent_sales', 'payment_option_id') && $pendingSale->payment_option_id) {
            $agentSaleAttrs['payment_option_id'] = $pendingSale->payment_option_id;
        }
        $agentSale = AgentSale::create($agentSaleAttrs);

        // Update product_list items linked to this pending sale
        ProductListItem::where('pending_sale_id', $pendingSale->id)
            ->update([
                'agent_sale_id' => $agentSale->id,
                'pending_sale_id' => null,
                'sold_at' => now(),
            ]);

        // Remove from pending sales (hard delete — row must not remain for reports)
        DB::table('pending_sales')->where('id', $pendingSale->id)->delete();

        return redirect()->route('admin.stock.agent-sales')->with('success', 'Sale saved successfully. Amount added to payment option balance.');
    }

    /**
     * Update all existing products to use sell_price from their latest purchase.
     * This ensures front page products show the correct sell_price instead of unit_price.
     */
    public function updateAllProductPrices()
    {
        $products = \App\Models\Product::all();
        $updatedCount = 0;

        foreach ($products as $product) {
            $latestPurchase = Purchase::where('product_id', $product->id)
                ->whereNotNull('sell_price')
                ->latest('date')
                ->latest('id')
                ->first();
            
            if ($latestPurchase && $latestPurchase->sell_price) {
                $product->update(['price' => $latestPurchase->sell_price]);
                $updatedCount++;
            }
        }

        return redirect()->route('admin.stock.purchases')
            ->with('success', "Updated {$updatedCount} product(s) to use sell_price from their latest purchase.");
    }

    /**
     * Search product_list by IMEI / serial (partial match). Detail: admin.stock.imei-item.
     */
    public function imeiSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $normalized = $q === '' ? '' : preg_replace('/\s+/', '', $q);

        $results = collect();
        if ($normalized !== '' && strlen($normalized) >= 3) {
            $like = '%'.addcslashes($normalized, '%_\\').'%';
            $results = ProductListItem::query()
                ->with(['stock:id,name', 'category:id,name', 'product:id,name'])
                ->where('imei_number', 'like', $like)
                ->orderBy('imei_number')
                ->limit(100)
                ->get();
        }

        return view('admin.stock.imei-search', [
            'q' => $q,
            'normalized' => $normalized,
            'results' => $results,
        ]);
    }

    /**
     * Full admin view for one product_list row (all IMEI / sale / assignment context).
     */
    public function showImeiItem(ProductListItem $productListItem)
    {
        $item = $productListItem->load([
            'purchase.paymentOption',
            'stock',
            'category',
            'product',
            'regionalManagerProductListAssignment.regionalManager:id,name,email',
            'teamLeaderProductListAssignment.teamLeader:id,name,email',
            'agentProductListAssignment.agent:id,name,email',
            'agentCredit.agent',
            'agentCredit.paymentOption',
            'pendingSale',
            'agentSale.agent',
            'distributionSale',
        ]);

        return view('admin.stock.imei-detail', compact('item'));
    }

    /**
     * Build a detailed error message categorized by failure reason.
     */
}
