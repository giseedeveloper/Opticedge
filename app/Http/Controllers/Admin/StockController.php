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
use App\Support\PurchaseInvoiceNumber;
use App\Services\AgentSaleCreditMigrationService;
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
                $purchases = Purchase::withCount([
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

        return view('admin.stock.stocks', [
            'stocks' => $stocksData,
            'hasPurchases' => $usingPurchases,
            'stockDashboard' => $stockDashboard,
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
                'agentProductListAssignment.agent:id,name,email',
                'agentCredit.agent:id,name,email',
                'agentCredit.paymentOption:id,name',
                'pendingSale',
                'agentSale.agent:id,name,email',
            ])
            ->orderBy('model')
            ->orderBy('imei_number')
            ->get();

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
        $stock->load(['productListItems' => function ($q) {
            $q->with([
                'category',
                'product',
                'purchase',
                'stock:id,name',
                'agentProductListAssignment.agent:id,name,email',
                'agentCredit.agent:id,name,email',
                'agentCredit.paymentOption:id,name',
                'pendingSale',
                'agentSale.agent:id,name,email',
            ])->orderBy('model')->orderBy('imei_number');
        }]);

        $available = $stock->productListItems->whereNull('sold_at')->count();
        $atLimit = $available >= $stock->stock_limit;

        return view('admin.stock.stock-show', compact('stock', 'atLimit'));
    }

    public function purchases(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $preset = $request->input('preset');

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

        $query = Purchase::with(['product', 'stock', 'branch', 'lines.product']);

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $purchases = $query->latest('date')->get();

        $purchaseDashboard = [
            'count' => $purchases->count(),
            'total_value' => (float) $purchases->sum(function ($p) {
                return (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));
            }),
            'pending_amount' => (float) $purchases->sum(function ($p) {
                $total = (float) ($p->total_amount ?? ($p->quantity * $p->unit_price));

                return max(0, $total - (float) ($p->paid_amount ?? 0));
            }),
        ];

        return view('admin.stock.purchases', compact('purchases', 'dateFrom', 'dateTo', 'preset', 'purchaseDashboard'));
    }

    public function exportPurchasesCsv(Request $request)
    {
        $query = Purchase::with(['product.category', 'branch', 'lines.product.category']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $purchases = $query->latest('date')->get();
        $filename = 'purchases-' . now()->format('Ymd-His') . '.csv';

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

    /**
     * View all payment receipts for all purchases.
     */
    public function viewAllReceipts()
    {
        $purchases = Purchase::with(['product', 'stock'])
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
        
        $distributionSales = $query->latest('date')->get();

        $distributionDashboard = [
            'count' => $distributionSales->count(),
            'total_sell' => (float) $distributionSales->sum('total_selling_value'),
            'total_profit' => (float) $distributionSales->sum('profit'),
            'pending' => $distributionSales->filter(function ($s) {
                $total = (float) ($s->total_selling_value ?? 0);
                $paid = (float) ($s->paid_amount ?? 0);

                return $paid < $total - 0.0001;
            })->count(),
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
        $query = AgentSale::with(['product.category', 'agent', 'paymentOption']);
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        
        $agentSales = $query->latest('date')->get();
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();

        $agentSalesDashboard = [
            'count' => $agentSales->count(),
            'total_sell' => (float) $agentSales->sum('total_selling_value'),
            'total_profit' => (float) $agentSales->sum('profit'),
        ];

        return view('admin.stock.agent-sales', compact('agentSales', 'paymentOptions', 'agentSalesDashboard'));
    }

    public function exportAgentSalesCsv(Request $request)
    {
        $query = AgentSale::with(['product.category', 'agent', 'paymentOption']);

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
        $canStoreExpenseId = Schema::hasColumn('agent_sales', 'commission_expense_id');

        $defaultChannelRaw = Setting::query()->where('key', 'default_agent_commission_channel_id')->value('value');
        $defaultChannelId = $defaultChannelRaw !== null && $defaultChannelRaw !== '' ? (int) $defaultChannelRaw : null;
        if ($newCommission > $eps && ! $defaultChannelId) {
            return redirect()->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => 'Choose a default commission channel in Store settings before saving commission.']);
        }

        try {
            DB::transaction(function () use ($sale, $newCommission, $defaultChannelId, $eps, $canStoreExpenseId) {
                $sale->refresh();

                $linkedExpense = null;
                if ($canStoreExpenseId && $sale->commission_expense_id) {
                    $linkedExpense = Expense::query()->lockForUpdate()->find($sale->commission_expense_id);
                }
                if (! $linkedExpense) {
                    $linkedExpense = Expense::query()
                        ->lockForUpdate()
                        ->where('activity', 'Agent sale commission (sale #' . $sale->id . ')')
                        ->latest('id')
                        ->first();
                }

                if ($linkedExpense) {
                    $opt = $linkedExpense->paymentOption;
                    if ($opt) {
                        $opt->increment('balance', (float) $linkedExpense->amount);
                    }
                    $sale->commission_expense_id = null;
                    $sale->saveQuietly();
                    DB::table('expenses')->where('id', $linkedExpense->id)->delete();
                }

                $commissionExpenseId = null;
                if ($newCommission > $eps) {
                    $option = PaymentOption::query()
                        ->visible()
                        ->whereKey($defaultChannelId)
                        ->lockForUpdate()
                        ->first();

                    if (! $option) {
                        throw new \InvalidArgumentException('The default commission channel is invalid or hidden. Update Store settings.');
                    }

                    if ((float) $option->balance + $eps < $newCommission) {
                        throw new \InvalidArgumentException('Insufficient balance in the default commission channel for this amount.');
                    }

                    $option->decrement('balance', $newCommission);

                    $expense = Expense::create([
                        'activity' => 'Agent sale commission (sale #' . $sale->id . ')',
                        'amount' => $newCommission,
                        'cash_used' => null,
                        'payment_option_id' => $option->id,
                        'date' => now()->toDateString(),
                    ]);
                    $commissionExpenseId = $expense->id;
                }

                $payload = ['commission_paid' => $newCommission];
                if ($canStoreExpenseId) {
                    $payload['commission_expense_id'] = $commissionExpenseId;
                }
                $sale->update($payload);
            });
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Agent sale commission save failed: ' . $e->getMessage(), ['exception' => $e]);

            return redirect()->route('admin.stock.agent-sales', $request->query())
                ->withErrors(['error' => 'Could not save commission. Try again or check logs.']);
        }

        return redirect()->route('admin.stock.agent-sales', $request->query())->with('success', 'Commission updated and expense synced.');
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

        if (Schema::hasColumn('agent_sales', 'commission_expense_id') && ! empty($sale->commission_expense_id)) {
            $expense = Expense::find($sale->commission_expense_id);
            if ($expense) {
                if ($expense->payment_option_id) {
                    $expOpt = PaymentOption::find($expense->payment_option_id);
                    if ($expOpt) {
                        $expOpt->increment('balance', (float) $expense->amount);
                    }
                }
                DB::table('expenses')->where('id', $expense->id)->delete();
            }
        }

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
            $purchasePickerRows = Purchase::query()
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->get(['id', 'name']);
        }

        return view('admin.stock.add-product', compact('stocks', 'addProductTarget', 'purchasePickerRows'));
    }

    /**
     * JSON: model + category for one purchase (web Add product when picking a purchase directly).
     */
    public function modelsForPurchaseAddProduct(Purchase $purchase)
    {
        $purchase->load([
            'lines.product.category:id,name',
            'product:id,name,category_id',
        ]);

        if ($purchase->lines->isNotEmpty()) {
            $rows = $purchase->lines
                ->map(function ($line) {
                    $product = $line->product;
                    if (! $product) {
                        return null;
                    }
                    $model = trim((string) ($product->name ?? ''));
                    $categoryId = $product->category_id ?? null;
                    if ($model === '' || empty($categoryId)) {
                        return null;
                    }
                    $catName = $product->category?->name ?? '—';
                    $unit = (float) $line->unit_price;
                    $sell = $line->sell_price !== null ? (float) $line->sell_price : null;
                    $rem = (int) $line->limit_remaining;

                    return [
                        'product_id' => (int) $product->id,
                        'model' => $model,
                        'category_id' => (int) $categoryId,
                        'category_name' => $catName,
                        'unit_price' => $unit,
                        'sell_price' => $sell,
                        'limit_remaining' => $rem,
                        'label' => $catName.' — '.$model.' · slots '.$rem.' · cost '.number_format($unit, 2).($sell !== null ? ' · sell '.number_format($sell, 2) : ''),
                    ];
                })
                ->filter()
                ->values();

            return response()->json(['data' => $rows->all()]);
        }

        $product = $purchase->product;
        if (! $product) {
            return response()->json(['data' => []]);
        }

        $model = trim((string) ($product->name ?? ''));
        $categoryId = $product->category_id ?? null;
        if ($model === '' || empty($categoryId)) {
            return response()->json(['data' => []]);
        }

        $unit = (float) ($purchase->unit_price ?? 0);
        $sell = $purchase->sell_price !== null ? (float) $purchase->sell_price : null;
        $rem = (int) ($purchase->limit_remaining ?? 0);

        return response()->json([
            'data' => [[
                'product_id' => (int) $product->id,
                'model' => $model,
                'category_id' => (int) $categoryId,
                'category_name' => $product->category?->name ?? '—',
                'unit_price' => $unit,
                'sell_price' => $sell,
                'limit_remaining' => $rem,
                'label' => ($product->category?->name ?? '—').' — '.$model.' · slots '.$rem.' · cost '.number_format($unit, 2).($sell !== null ? ' · sell '.number_format($sell, 2) : ''),
            ]],
        ]);
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
    public function storeProductFromForm(Request $request)
    {
        $baseRules = [
            'catalog_product_id' => 'required|exists:models,id',
            'imei_numbers' => 'required|string|max:65535',
        ];

        if ($request->filled('purchase_id')) {
            $validated = $request->validate($baseRules + [
                'purchase_id' => 'required|exists:purchases,id',
            ]);
            $purchase = Purchase::with(['product', 'stock', 'lines'])->findOrFail($validated['purchase_id']);
            if ($purchase->limit_status !== 'pending' || (int) $purchase->limit_remaining <= 0) {
                return redirect()->route('admin.stock.add-product')
                    ->withInput()
                    ->withErrors(['purchase_id' => 'This purchase has no remaining device slots.']);
            }
            $stockIdForRow = $purchase->stock_id;
        } elseif ($request->filled('stock_id')) {
            $validated = $request->validate($baseRules + [
                'stock_id' => 'required|exists:stocks,id',
            ]);
            $stock = Stock::findOrFail($validated['stock_id']);
            $purchase = Purchase::with(['product', 'lines'])
                ->where('stock_id', $stock->id)
                ->where('limit_status', 'pending')
                ->where('limit_remaining', '>', 0)
                ->latest('date')->latest('id')->first();

            if (! $purchase) {
                return redirect()->route('admin.stock.add-product')
                    ->withInput()
                    ->withErrors(['stock_id' => 'No pending purchase limit for this stock.']);
            }
            $stockIdForRow = $stock->id;
        } else {
            $pickField = Stock::query()->exists() ? 'stock_id' : 'purchase_id';
            $pickMessage = $pickField === 'stock_id'
                ? 'Select stock first.'
                : 'Select a purchase first.';

            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors([$pickField => $pickMessage]);
        }

        $catalogProduct = Product::with('category')->findOrFail((int) $validated['catalog_product_id']);
        $categoryId = (int) $catalogProduct->category_id;
        $model = (string) $catalogProduct->name;

        $purchaseLine = null;
        if ($purchase->lines->isNotEmpty()) {
            $purchaseLine = $purchase->lines->firstWhere('product_id', $catalogProduct->id);
            if (! $purchaseLine || (int) $purchaseLine->limit_remaining <= 0) {
                return redirect()->route('admin.stock.add-product')
                    ->withInput()
                    ->withErrors(['catalog_product_id' => 'Pick a model from this purchase that still has open IMEI slots.']);
            }
            $remainingForModel = (int) $purchaseLine->limit_remaining;
        } else {
            if ($purchase->product_id && (int) $purchase->product_id !== (int) $catalogProduct->id) {
                return redirect()->route('admin.stock.add-product')
                    ->withInput()
                    ->withErrors(['catalog_product_id' => 'Selected model does not match this purchase.']);
            }
            $remainingForModel = (int) $purchase->limit_remaining;
        }

        $imeis = ImeiListParser::parse($validated['imei_numbers']);

        if ($imeis === []) {
            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors(['imei_numbers' => 'Enter at least one IMEI. Use one per line, or separate with spaces, commas, or semicolons.']);
        }

        $lenErrors = ImeiListParser::lengthErrors($imeis);
        if ($lenErrors !== []) {
            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors(['imei_numbers' => implode(' ', $lenErrors)]);
        }

        if (count($imeis) > $remainingForModel) {
            return redirect()->route('admin.stock.add-product')
                ->withInput()
                ->withErrors([
                    'imei_numbers' => 'Not enough slots for this model. Remaining for this line: '.$remainingForModel.'.',
                ]);
        }

        $failed = [];
        $failureReasons = [
            'duplicates' => [],
            'limit_exhausted' => [],
        ];
        $created = 0;

        DB::transaction(function () use ($purchase, $purchaseLine, $stockIdForRow, $categoryId, $model, $catalogProduct, $imeis, &$failed, &$failureReasons, &$created) {
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
                    $failed[] = $imei.' (already in list)';
                    $failureReasons['duplicates'][] = $imei;
                    continue;
                }

                $purchase->refresh();
                if ($purchaseLine) {
                    $purchaseLine->refresh();
                    if ((int) $purchaseLine->limit_remaining <= 0) {
                        $failed[] = $imei.' (purchase limit exhausted for this model)';
                        $failureReasons['limit_exhausted'][] = $imei;
                        break;
                    }
                } elseif ($purchase->limit_remaining <= 0) {
                    $failed[] = $imei.' (purchase limit exhausted)';
                    $failureReasons['limit_exhausted'][] = $imei;
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
                $created++;
            }
        });

        if ($created > 0) {
            $msg = 'Added '.$created.' device(s) ('.count($imeis).' IMEI(s) parsed).';
            if ($failed !== []) {
                $msg .= ' Skipped: '.implode('; ', array_slice($failed, 0, 10)).(count($failed) > 10 ? '…' : '');
            }

            return redirect()->route('admin.stock.add-product')->with('success', $msg);
        }

        return redirect()->route('admin.stock.add-product')
            ->withInput()
            ->withErrors(['imei_numbers' => $this->buildDetailedErrorMessage($imeis, $failureReasons)]);
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

        return view('admin.stock.create-purchase', compact('vendors', 'fromStock', 'branches', 'productsForSelect', 'paymentOptions'));
    }

    public function storePurchase(Request $request)
    {
        $paymentOptionId = $request->filled('payment_option_id') ? $request->input('payment_option_id') : null;
        $hasNoteColumn = Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'note');

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
                $dateStr = PurchaseInvoiceNumber::dateString($validated['date']);
                $validated['name'] = PurchaseInvoiceNumber::unique($validated['distributor_name'] ?? null, $dateStr);
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
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $totalAmount = (float) $validated['total_amount'];
            $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');
            $validated['payment_status'] = $paymentStatus;
            $validated['paid_amount'] = $paidAmount;
            $validated['limit_status'] = 'pending';
            $validated['limit_remaining'] = $quantity;
            $validated['sell_price'] = $request->filled('sell_price') ? $request->input('sell_price') : null;

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

            return redirect()->route('admin.stock.purchases')->with('success', 'Purchase recorded successfully.');
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
            $dateStr = PurchaseInvoiceNumber::dateString($validated['date']);
            $purchaseName = PurchaseInvoiceNumber::unique($validated['distributor_name'] ?? null, $dateStr);
        } else {
            $purchaseName = $nameInput;
        }

        $paidAmount = (float) ($validated['paid_amount'] ?? 0);
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
            'limit_status' => 'pending',
            'limit_remaining' => $totalQty,
            'sell_price' => null,
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

        DB::transaction(function () use ($header, $linePayload, $paidAmount, $request, $validated, &$purchase) {
            $purchase = Purchase::create($header);

            foreach ($linePayload as $row) {
                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'sell_price' => $row['sell_price'],
                    'limit_remaining' => $row['quantity'],
                ]);

                $row['product']->increment('stock_quantity', $row['quantity']);
            }

            $purchase->update([
                'limit_remaining' => (int) $purchase->lines()->sum('limit_remaining'),
            ]);

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

        return redirect()->route('admin.stock.purchases')->with('success', 'Purchase recorded successfully.');
    }

    public function editPurchase($id)
    {
        $purchase = Purchase::with(['product.category', 'payments.paymentOption'])->findOrFail($id);
        
        // Get all categories for the select dropdown
        $categories = \App\Models\Category::orderBy('name')->get();
            
        // Get unique distributors for the datalist
        $distributors = Purchase::select('distributor_name')
            ->whereNotNull('distributor_name')
            ->distinct()
            ->pluck('distributor_name');
        
        // Get payment options with balance for selection
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
            
        return view('admin.stock.edit-purchase', compact('purchase', 'categories', 'distributors', 'paymentOptions'));
    }

    public function updatePurchase(Request $request, $id)
    {
        $purchase = Purchase::with('product')->findOrFail($id);

        $rules = [
            'name' => 'nullable|string|max:255',
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

        return redirect()
            ->route('admin.stock.edit-purchase', $purchase->id)
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroyPurchase($id)
    {
        $purchase = Purchase::with(['product', 'productListItems'])->findOrFail($id);
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
            return redirect()->route('admin.stock.purchases')
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Purchase delete failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('admin.stock.purchases')
                ->withErrors(['error' => 'Could not delete this purchase. It may be linked to records that block removal.']);
        }

        return redirect()->route('admin.stock.purchases')->with('success', 'Purchase deleted, including linked stock and agent sale / credit / pending data.');
    }

    // Distribution Sales
    public function createDistribution()
    {
        $products = Product::whereHas('purchases')
            ->with('category')
            ->orderBy('name')
            ->get();

        $dealers = User::where('role', 'dealer')->orderBy('name')->get();

        return view('admin.stock.create-distribution', compact('products', 'dealers'));
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
            'lines.*.product_id' => 'required|integer|exists:models,id',
            'lines.*.quantity_sold' => 'required|integer|min:1',
            'lines.*.selling_price' => 'required|numeric|min:0.01',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $service = app(\App\Services\DistributionSaleService::class);
        $dealer = User::findOrFail((int) $validated['dealer_id']);
        $dealerName = $dealer->business_name ?? $dealer->name;

        $linePayloads = [];
        $grandSellingTotal = 0.0;

        foreach ($validated['lines'] as $line) {
            $pid = (int) $line['product_id'];
            $qty = (int) $line['quantity_sold'];
            $sellUnit = (float) $line['selling_price'];

            $product = Product::findOrFail($pid);
            $stock = (int) ($product->stock_quantity ?? 0);
            if ($qty > $stock) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'lines' => "Insufficient stock for {$product->name}: requested {$qty}, available {$stock}.",
                    ]);
            }

            $buyPrice = $service->getBuyPriceForProduct($pid);
            $totalSell = $qty * $sellUnit;
            $totalBuy = $qty * $buyPrice;
            $grandSellingTotal += $totalSell;

            $linePayloads[] = [
                'product' => $product,
                'quantity_sold' => $qty,
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

        DB::transaction(function () use ($validated, $dealerName, $request, $linePayloads, $paidShares, $eps) {
            foreach ($linePayloads as $idx => $payload) {
                $paidLine = (float) ($paidShares[$idx] ?? 0);
                $totalSell = (float) $payload['total_selling_value'];
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
                    'commission' => 0,
                    'profit' => $payload['profit'],
                    'paid_amount' => $paidLine,
                    'balance' => max(0, $totalSell - $paidLine),
                    'status' => $paidLine >= $totalSell - $eps ? 'complete' : 'pending',
                ];

                DistributionSale::create($attrs);

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
            $share = min($share, $lt, $paidTotal - $allocated);
            if ($share < 0) {
                $share = 0.0;
            }
            $out[$i] = $share;
            $allocated += $share;
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

        $invoiceNo = str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT);
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
        $quantitySold = $sale->quantity_sold;

        DB::table('distribution_sales')->where('id', $sale->id)->delete();
        
        // Keep product.stock_quantity in sync
        if ($product) {
            $product->increment('stock_quantity', $quantitySold);
        }

        return redirect()->route('admin.stock.distribution')->with('success', 'Distribution sale deleted successfully.');
    }

    // Agent Sales
    public function createAgentSale()
    {
        // Fetch products that have been purchased at least once
        $products = \App\Models\Product::whereHas('purchases')->orderBy('name')->get();

        return view('admin.stock.create-agent-sale', compact('products'));
    }

    public function storeAgentSale(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'seller_name' => 'nullable|string|max:255',
            'product_id' => 'required|exists:models,id',
            'quantity_sold' => 'required|integer|min:1',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $service = app(\App\Services\DistributionSaleService::class);
        $buyPrice = $service->getBuyPriceForProduct($validated['product_id']);
        
        $validated['purchase_price'] = $buyPrice;
        $validated['total_selling_value'] = $validated['quantity_sold'] * $validated['selling_price'];
        $validated['total_purchase_value'] = $validated['quantity_sold'] * $buyPrice;
        $validated['profit'] = $validated['total_selling_value'] - $validated['total_purchase_value'];

        // Save to pending sales instead of agent_sales
        \App\Models\PendingSale::create($validated);

        // Keep product.stock_quantity in sync for Category Management / dashboards
        \App\Models\Product::where('id', $validated['product_id'])->decrement('stock_quantity', $validated['quantity_sold']);

        return redirect()->route('admin.stock.pending-sales')->with('success', 'Sale recorded successfully. Please select payment option and save.');
    }

    public function pendingSales()
    {
        $pendingSales = \App\Models\PendingSale::with(['product.category', 'paymentOption'])->latest('date')->get();
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
        \App\Models\ProductListItem::where('pending_sale_id', $pendingSale->id)
            ->update([
                'agent_sale_id' => $agentSale->id,
                'pending_sale_id' => null,
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
            'agentProductListAssignment.agent',
            'agentCredit.agent',
            'agentCredit.paymentOption',
            'pendingSale',
            'agentSale.agent',
        ]);

        return view('admin.stock.imei-detail', compact('item'));
    }

    /**
     * Build a detailed error message categorized by failure reason.
     */
    private function buildDetailedErrorMessage(array $imeis, array $failureReasons): string
    {
        $duplicateCount = count($failureReasons['duplicates'] ?? []);
        $limitExhaustedCount = count($failureReasons['limit_exhausted'] ?? []);
        $totalParsed = count($imeis);
        $totalFailed = $duplicateCount + $limitExhaustedCount;

        $messages = [];
        $messages[] = "❌ No devices added. Parsed $totalParsed IMEI(s), but all failed.";

        if ($duplicateCount > 0) {
            $samples = array_slice($failureReasons['duplicates'], 0, 3);
            $sampleList = implode(', ', $samples);
            $more = $duplicateCount > 3 ? " (+ " . ($duplicateCount - 3) . " more)" : '';
            $messages[] = "• All duplicates: $duplicateCount IMEI(s) already exist in the system. Examples: $sampleList$more";
        }

        if ($limitExhaustedCount > 0) {
            $samples = array_slice($failureReasons['limit_exhausted'], 0, 3);
            $sampleList = implode(', ', $samples);
            $more = $limitExhaustedCount > 3 ? " (+ " . ($limitExhaustedCount - 3) . " more)" : '';
            $messages[] = "• Purchase limit exhausted: $limitExhaustedCount IMEI(s) could not be added because the purchase limit has been reached. Examples: $sampleList$more";
        }

        $messages[] = "\n💡 Solutions:";
        if ($duplicateCount > 0) {
            $messages[] = "  • Check if these IMEIs have already been added to the system";
        }
        if ($limitExhaustedCount > 0) {
            $messages[] = "  • Create a new purchase with additional quantity for this stock";
        }
        if ($duplicateCount === 0 && $limitExhaustedCount === 0) {
            $messages[] = "  • Verify you selected the correct stock and model";
            $messages[] = "  • Check that all IMEIs are properly formatted";
        }

        return implode("\n", $messages);
    }
}
