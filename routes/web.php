<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CommandCenterController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\SelcomWebhookController;
use Livewire\Volt\Volt;

// API routes (for Flutter app) – loaded here so /api/* is always available
Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));

Route::view('/', 'welcome')->name('welcome');

// Selcom Checkout webhook (no auth; CSRF excluded in bootstrap/app.php)
$selcomPrefix = config('selcom.prefix', 'selcom');
Route::post("{$selcomPrefix}/checkout-callback", SelcomWebhookController::class)->name('selcom.checkout-callback');
Route::get('/product/{product}', [App\Http\Controllers\PublicProductController::class , 'show'])->name('product.show');
Route::get('/category/{category}', [App\Http\Controllers\PublicCategoryController::class , 'show'])->name('category.show');
// External DB — same ?pass= as config optic.db_seed_pass (default 1234)
Route::get('db/seed', App\Http\Controllers\ExternalDbSeedController::class)
    ->middleware('throttle:12,1')
    ->name('db.seed.external');
Route::get('db/migrate', App\Http\Controllers\ExternalDbMigrateController::class)
    ->middleware('throttle:12,1')
    ->name('db.migrate.external');

Route::get('/assets/app-icon.png', function () {
    $iconPath = public_path('assets/app_icon.png');
    if (!is_file($iconPath) || !is_readable($iconPath)) {
        abort(404);
    }

    return response()->file($iconPath, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('assets.app-icon');

Route::get('dashboard', function () {
    if (in_array(auth()->user()->role, ['admin', 'subadmin'], true)) {
        return redirect()->route('admin.dashboard');
    }
    if (auth()->user()->role === 'agent') {
        return redirect()->route('agent.dashboard');
    }
    if (auth()->user()->role === 'teamleader') {
        return redirect()->route('team-leader.dashboard');
    }
    if (auth()->user()->role === 'regional_manager') {
        return redirect()->route('regional-manager.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware('guest')->group(function () {
    Volt::route('register/dealer', 'pages.auth.dealer-register')->name('dealer.register');
    Route::get('register/dealer/pending', [App\Http\Controllers\DealerRegisterController::class , 'pending'])->name('dealer.pending');
    Volt::route('register/agent', 'pages.auth.agent-register')->name('agent.register');
});

Route::get('profile', function () {
    if (auth()->user()?->role === 'teamleader') {
        return redirect()->route('team-leader.profile');
    }
    if (auth()->user()?->role === 'regional_manager') {
        return redirect()->route('regional-manager.profile');
    }

    return view('profile');
})->middleware(['auth'])->name('profile');

// Command center (UI) + POST actions — must be registered before GET command/{command}
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('command', [CommandCenterController::class, 'index'])->name('command.center');
    Route::post('command/execute', [CommandCenterController::class, 'execute'])->name('command.execute');
    Route::post('command/migrate-path', [CommandCenterController::class, 'migratePath'])->name('command.migrate-path');
    Route::post('command/seed-class', [CommandCenterController::class, 'seedClass'])->name('command.seed-class');
    Route::post('command/empty-table', [CommandCenterController::class, 'emptyTable'])->name('command.empty-table');
    Route::post('command/extension-track', [CommandCenterController::class, 'trackExtension'])->name('command.extension-track');
    Route::post('command/extension-untrack', [CommandCenterController::class, 'untrackExtension'])->name('command.extension-untrack');
});

// Run whitelisted artisan command: GET /command/{command} (admin only, JSON)
Route::get('command/{command}', App\Http\Controllers\Admin\ArtisanCommandController::class)
    ->middleware(['auth', 'admin'])
    ->where('command', '[a-zA-Z0-9:_-]+')
    ->name('command.run');

Route::middleware(['auth', 'admin', 'subadmin.ability'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', function () {
            $totalCustomers = \App\Models\User::where('role', 'customer')->count();
            $totalOrders = \App\Models\Order::count();
            $totalProducts = \App\Models\Product::count();
            $recentOrders = \App\Models\Order::with('user')->latest()->take(5)->get();
            $financialService = app(\App\Services\DashboardFinancialService::class);
            $salesMetrics = $financialService->getSalesMetrics();
            
            // Get date range from request or use defaults
            $startDate = request('start_date') ? \Carbon\Carbon::parse(request('start_date')) : \Carbon\Carbon::now()->subMonths(1);
            $endDate = request('end_date') ? \Carbon\Carbon::parse(request('end_date')) : \Carbon\Carbon::now();
            $topProducts = $financialService->getTopSellingProducts($startDate, $endDate, 10);

            // Financial summary date range (separate from top-selling chart range)
            $financialStartDate = request('financial_start_date')
                ? \Carbon\Carbon::parse(request('financial_start_date'))
                : \Carbon\Carbon::now()->startOfMonth();
            $financialEndDate = request('financial_end_date')
                ? \Carbon\Carbon::parse(request('financial_end_date'))
                : \Carbon\Carbon::now();
            if ($financialStartDate->gt($financialEndDate)) {
                [$financialStartDate, $financialEndDate] = [$financialEndDate, $financialStartDate];
            }
            $financialMetrics = $financialService->getMetrics($financialStartDate, $financialEndDate);
            
            // Get payment options with balances
            $paymentOptions = \App\Models\PaymentOption::visible()->orderBy('name')->get();
            $agentAgingAssetsCount = \App\Models\ProductListItem::query()
                ->whereNull('sold_at')
                ->whereHas('agentProductListAssignment')
                ->count();
            $agentAgingAssets = \App\Models\ProductListItem::query()
                ->with(['agentProductListAssignment.agent:id,name'])
                ->whereNull('sold_at')
                ->whereHas('agentProductListAssignment')
                ->addSelect([
                    'assigned_at' => \App\Models\AgentProductListAssignment::query()
                        ->select('created_at')
                        ->whereColumn('agent_product_list_assignments.product_list_id', 'product_list.id')
                        ->limit(1),
                ])
                ->orderBy('assigned_at', 'asc')
                ->orderBy('id', 'asc')
                ->limit(50)
                ->get();

            // Overdue purchases: not fully paid, oldest first
            $overduePurchases = \App\Models\Purchase::with(['product', 'branch'])
                ->where('payment_status', '!=', 'paid')
                ->whereBetween('date', [$financialStartDate->copy()->startOfDay(), $financialEndDate->copy()->endOfDay()])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->limit(20)
                ->get();

            // Manual payables (separate from purchase payables), for optional detail modal
            $overduePayables = \App\Models\Payable::whereBetween('date', [$financialStartDate->copy()->startOfDay(), $financialEndDate->copy()->endOfDay()])
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->limit(20)
                ->get();

            $distributorReceivables = $financialService->getDistributorReceivableBreakdown($financialStartDate, $financialEndDate);
            $agentCreditReceivables = $financialService->getAgentCreditReceivableSummary($financialStartDate, $financialEndDate);
            
            return view('admin.dashboard', compact(
                'totalCustomers',
                'totalOrders',
                'totalProducts',
                'recentOrders',
                'financialMetrics',
                'salesMetrics',
                'topProducts',
                'startDate',
                'endDate',
                'financialStartDate',
                'financialEndDate',
                'paymentOptions',
                'agentAgingAssetsCount',
                'agentAgingAssets',
                'overduePurchases',
                'overduePayables',
                'distributorReceivables',
                'agentCreditReceivables'
            ));
        }
        )->name('dashboard');
        Route::get('products/{product}/imei', [ProductController::class, 'showImei'])->name('products.imei');
        Route::resource('products', ProductController::class);
        Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
        Route::get('customer-needs', [App\Http\Controllers\Admin\CustomerNeedsController::class, 'index'])->name('customer-needs.index');
        Route::resource('vendors', App\Http\Controllers\Admin\VendorController::class)->only(['index', 'store', 'update', 'destroy']);

        // Dealers Management
        Route::get('dealers', [App\Http\Controllers\Admin\DealerController::class , 'index'])->name('dealers.index');
        Route::get('dealers/create', [App\Http\Controllers\Admin\DealerController::class, 'create'])->name('dealers.create');
        Route::post('dealers', [App\Http\Controllers\Admin\DealerController::class, 'store'])->name('dealers.store');
        Route::get('dealers/{user}', [App\Http\Controllers\Admin\DealerController::class , 'show'])->name('dealers.show');
        Route::patch('dealers/{user}', [App\Http\Controllers\Admin\DealerController::class, 'update'])->name('dealers.update');
        Route::patch('dealers/{user}/approve', [App\Http\Controllers\Admin\DealerController::class , 'approve'])->name('dealers.approve');
        Route::patch('dealers/{user}/reject', [App\Http\Controllers\Admin\DealerController::class , 'reject'])->name('dealers.reject');
        Route::delete('dealers/{user}', [App\Http\Controllers\Admin\DealerController::class, 'destroy'])->name('dealers.destroy');

        // Agents Management
        Route::get('agents', [App\Http\Controllers\Admin\AgentController::class, 'index'])->name('agents.index');
        Route::get('agents/create', [App\Http\Controllers\Admin\AgentController::class, 'create'])->name('agents.create');
        Route::post('agents', [App\Http\Controllers\Admin\AgentController::class, 'store'])->name('agents.store');
        Route::get('subadmins', [App\Http\Controllers\Admin\AgentController::class, 'subadminsIndex'])->name('subadmins.index');
        Route::get('subadmins/create', [App\Http\Controllers\Admin\AgentController::class, 'createSubadmin'])->name('subadmins.create');
        Route::post('subadmins', [App\Http\Controllers\Admin\AgentController::class, 'storeSubadmin'])->name('subadmins.store');
        Route::get('agents/assign-products', [App\Http\Controllers\Admin\AgentController::class, 'assignProductsForm'])->name('agents.assign-products');
        Route::post('agents/assign-products', [App\Http\Controllers\Admin\AgentController::class, 'storeAssignment'])->name('agents.store-assignment');
        Route::get('agents/assignable-imeis', [App\Http\Controllers\Admin\AgentController::class, 'assignableImeis'])->name('assignable-imeis');
        Route::get('agents/{agent}', [App\Http\Controllers\Admin\AgentController::class, 'show'])->name('agents.show');
        Route::patch('agents/{agent}', [App\Http\Controllers\Admin\AgentController::class, 'update'])->name('agents.update');
        Route::patch('agents/{agent}/transfer-branch', [App\Http\Controllers\Admin\AgentController::class, 'transferBranch'])->name('agents.transfer-branch');
        Route::patch('agents/{agent}/team-leader', [App\Http\Controllers\Admin\AgentController::class, 'updateTeamLeader'])->name('agents.update-team-leader');
        Route::patch('agents/{user}/activate', [App\Http\Controllers\Admin\AgentController::class, 'activate'])->name('agents.activate');
        Route::patch('agents/{user}/deactivate', [App\Http\Controllers\Admin\AgentController::class, 'deactivate'])->name('agents.deactivate');
        Route::delete('agents/{user}', [App\Http\Controllers\Admin\AgentController::class, 'destroy'])->name('agents.destroy');
        Route::patch('subadmins/{user}/activate', [App\Http\Controllers\Admin\AgentController::class, 'activate'])->name('subadmins.activate');
        Route::patch('subadmins/{user}/deactivate', [App\Http\Controllers\Admin\AgentController::class, 'deactivate'])->name('subadmins.deactivate');
        Route::delete('subadmins/{user}', [App\Http\Controllers\Admin\AgentController::class, 'destroy'])->name('subadmins.destroy');

        // Orders
        Route::resource('orders', App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show', 'update']);

        // Customers
        Route::get('customers', [App\Http\Controllers\Admin\CustomerController::class , 'index'])->name('customers.index');
        Route::get('customers/regional-managers', [App\Http\Controllers\Admin\CustomerController::class, 'regionalManagersIndex'])->name('customers.regional-managers.index');
        Route::post('customers/regional-managers', [App\Http\Controllers\Admin\CustomerController::class, 'storeRegionalManager'])->name('customers.regional-managers.store');
        Route::get('customers/team-leaders', [App\Http\Controllers\Admin\CustomerController::class, 'teamLeadersIndex'])->name('customers.team-leaders.index');
        Route::post('customers/team-leaders', [App\Http\Controllers\Admin\CustomerController::class, 'storeTeamLeader'])->name('customers.team-leaders.store');
        Route::patch('customers/{user}/activate', [App\Http\Controllers\Admin\CustomerController::class, 'activate'])->name('customers.activate');
        Route::patch('customers/{user}/deactivate', [App\Http\Controllers\Admin\CustomerController::class, 'deactivate'])->name('customers.deactivate');
        Route::delete('customers/{user}', [App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('users/{user}/reset-password', [App\Http\Controllers\Admin\UserPasswordController::class, 'reset'])
            ->name('users.reset-password');

        // Settings
        Route::get('settings', [App\Http\Controllers\Admin\SettingController::class , 'index'])->name('settings.index');
        Route::post('settings', [App\Http\Controllers\Admin\SettingController::class , 'update'])->name('settings.update');
        Route::post('settings/subadmin-roles', [App\Http\Controllers\Admin\SettingController::class, 'storeSubadminRole'])->name('settings.subadmin-roles.store');
        Route::put('settings/subadmin-roles/{role}', [App\Http\Controllers\Admin\SettingController::class, 'updateSubadminRolePermissions'])->name('settings.subadmin-roles.update');

        // Command center mirror under /admin/command (same UI as /command)
        Route::get('command', [CommandCenterController::class, 'index'])->name('command.center');

        // Run whitelisted artisan command: GET /admin/command/{command}
        Route::get('command/{command}', App\Http\Controllers\Admin\ArtisanCommandController::class)
            ->where('command', '[a-zA-Z0-9:_-]+')
            ->name('command.run');

        // Reports
        Route::get('reports', [App\Http\Controllers\Admin\ReportController::class , 'index'])->name('reports.index');
        Route::get('reports/agent-stock-export', [App\Http\Controllers\Admin\ReportController::class, 'exportAgentDailyStock'])
            ->name('reports.agent-stock-export');

        // Branches (locations / stores)
        Route::resource('branches', App\Http\Controllers\Admin\BranchController::class)->except(['show']);

        // Expenses
        Route::resource('expenses', App\Http\Controllers\Admin\ExpenseController::class)->except(['show']);

        Route::get('payout', [App\Http\Controllers\Admin\PayoutController::class, 'index'])->name('payout.index');
        Route::post('payout/agent-commission/selcom-bulk', [App\Http\Controllers\Admin\CommissionSelcomPayoutController::class, 'bulkStart'])->name('payout.commission-selcom.bulk');
        Route::get('payout/selcom/{selcompay}/wait', [App\Http\Controllers\Admin\CommissionSelcomPayoutController::class, 'wait'])->name('payout.selcom.wait');
        Route::get('payout/selcom/{selcompay}/status', [App\Http\Controllers\Admin\CommissionSelcomPayoutController::class, 'status'])->name('payout.selcom.status');

        // Payment Options
        Route::patch('payment-options/{payment_option}/toggle-visibility', [App\Http\Controllers\Admin\PaymentOptionController::class, 'toggleVisibility'])->name('payment-options.toggle-visibility');
        Route::patch('payment-options/{payment_option}/shrink-balance', [App\Http\Controllers\Admin\PaymentOptionController::class, 'shrinkBalance'])->name('payment-options.shrink-balance');
        Route::resource('payment-options', App\Http\Controllers\Admin\PaymentOptionController::class)->except(['show']);

        // Payment Transfers
        Route::prefix('payment-transfer')->name('payment-transfer.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PaymentTransferController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\PaymentTransferController::class, 'store'])->name('store');
            Route::get('history', [App\Http\Controllers\Admin\PaymentTransferController::class, 'history'])->name('history');
        });

        // Stock Management
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('imei-search', [App\Http\Controllers\Admin\StockController::class, 'imeiSearch'])->name('imei-search');
            Route::get('imei/{productListItem}', [App\Http\Controllers\Admin\StockController::class, 'showImeiItem'])->name('imei-item');
            Route::get('stocks', [App\Http\Controllers\Admin\StockController::class, 'stocks'])->name('stocks');
            Route::get('add-product/purchases/{purchase}/models', [App\Http\Controllers\Admin\StockController::class, 'modelsForPurchaseAddProduct'])->name('add-product.purchase.models');
            Route::get('stocks/{stock}/models', [App\Http\Controllers\Admin\StockController::class, 'modelsForStock'])->name('stocks.models');
            Route::get('stocks/{stock}', [App\Http\Controllers\Admin\StockController::class, 'showStock'])->name('stocks.show');
            Route::get('stocks/{stock}/receipts', [App\Http\Controllers\Admin\StockController::class, 'viewStockReceipts'])->name('stock-receipts');
            Route::get('add-product', [App\Http\Controllers\Admin\StockController::class, 'addProductForm'])->name('add-product');
            Route::post('add-product', [App\Http\Controllers\Admin\StockController::class, 'storeProductFromForm'])->name('store-add-product');
            Route::post('decode-barcodes', [App\Http\Controllers\Admin\StockController::class, 'decodeBarcodeImages'])->name('decode-barcodes');
            Route::get('purchase/{id}', [App\Http\Controllers\Admin\StockController::class, 'showPurchase'])->name('purchase.show');
            Route::delete('purchase/{purchase}/items/{productListItem}', [App\Http\Controllers\Admin\StockController::class, 'destroyPurchaseItem'])->name('purchase.item.destroy');
            Route::get('purchases', [App\Http\Controllers\Admin\StockController::class , 'purchases'])->name('purchases');
            Route::get('purchases/export-csv', [App\Http\Controllers\Admin\StockController::class, 'exportPurchasesCsv'])->name('purchases.export-csv');
            Route::get('purchases/receipts', [App\Http\Controllers\Admin\StockController::class , 'viewAllReceipts'])->name('purchases.receipts');
            Route::get('purchases/create', [App\Http\Controllers\Admin\StockController::class , 'createPurchase'])->name('create-purchase');
            Route::post('purchases', [App\Http\Controllers\Admin\StockController::class , 'storePurchase'])->name('store-purchase');
            Route::get('purchases/{id}/edit', [App\Http\Controllers\Admin\StockController::class , 'editPurchase'])->name('edit-purchase');
            Route::put('purchases/{id}', [App\Http\Controllers\Admin\StockController::class , 'updatePurchase'])->name('update-purchase');
            Route::delete('purchases/{id}', [App\Http\Controllers\Admin\StockController::class , 'destroyPurchase'])->name('destroy-purchase');
            Route::post('purchases/update-prices', [App\Http\Controllers\Admin\StockController::class , 'updateAllProductPrices'])->name('update-product-prices');
            
            // Distribution Sales
            Route::get('distribution', [App\Http\Controllers\Admin\StockController::class , 'distribution'])->name('distribution');
            Route::get('distribution/consolidated-invoice', [App\Http\Controllers\Admin\StockController::class, 'downloadConsolidatedDistributionInvoice'])->name('distribution-consolidated-invoice');
            Route::get('distribution/export-csv', [App\Http\Controllers\Admin\StockController::class, 'exportDistributionCsv'])->name('distribution.export-csv');
            Route::get('distribution/create', [App\Http\Controllers\Admin\StockController::class, 'createDistribution'])->name('create-distribution');
            Route::post('distribution', [App\Http\Controllers\Admin\StockController::class, 'storeDistribution'])->name('store-distribution');
            Route::get('distribution/{id}/edit', [App\Http\Controllers\Admin\StockController::class, 'editDistribution'])->name('edit-distribution');
            Route::get('distribution/{id}/invoice', [App\Http\Controllers\Admin\StockController::class, 'downloadDistributionInvoice'])->name('distribution-invoice');
            Route::put('distribution/{id}', [App\Http\Controllers\Admin\StockController::class, 'updateDistribution'])->name('update-distribution');
            Route::delete('distribution/{id}', [App\Http\Controllers\Admin\StockController::class, 'destroyDistribution'])->name('destroy-distribution');
            Route::patch('distribution/{id}/status', [App\Http\Controllers\Admin\StockController::class, 'updateDistributionStatus'])->name('distribution-update-status');
            Route::post('distribution/{id}/channel', [App\Http\Controllers\Admin\StockController::class, 'saveDistributionChannel'])->name('distribution-save-channel');

            // Agent Sales
            Route::get('agent-sales', [App\Http\Controllers\Admin\StockController::class , 'agentSales'])->name('agent-sales');
            Route::get('agent-sales/export-csv', [App\Http\Controllers\Admin\StockController::class, 'exportAgentSalesCsv'])->name('agent-sales.export-csv');
            Route::get('agent-sales/create', [App\Http\Controllers\Admin\StockController::class, 'createAgentSale'])->name('create-agent-sale');
            Route::post('agent-sales', [App\Http\Controllers\Admin\StockController::class, 'storeAgentSale'])->name('store-agent-sale');
            Route::get('agent-sales/{id}/invoice', [App\Http\Controllers\Admin\StockController::class, 'downloadAgentSaleInvoice'])->name('agent-sale-invoice');
            Route::patch('agent-sales/{id}/commission', [App\Http\Controllers\Admin\StockController::class, 'updateAgentSaleCommission'])->name('agent-sales-update-commission');
            Route::post('agent-sales/{id}/channel', [App\Http\Controllers\Admin\StockController::class, 'saveAgentSaleChannel'])->name('agent-sales-save-channel');
            Route::post('agent-sales/{id}/convert-to-credit', [App\Http\Controllers\Admin\StockController::class, 'convertAgentSaleToCredit'])->name('agent-sales-convert-to-credit');
            Route::delete('agent-sales/{id}', [App\Http\Controllers\Admin\StockController::class, 'destroyAgentSale'])->name('destroy-agent-sale');

            Route::get('agent-transfers', [App\Http\Controllers\Admin\AgentTransferController::class, 'index'])->name('agent-transfers');
            Route::get('agent-transfers/{agent_product_transfer}', [App\Http\Controllers\Admin\AgentTransferController::class, 'show'])->name('agent-transfers.show');
            Route::post('agent-transfers/{agent_product_transfer}/approve', [App\Http\Controllers\Admin\AgentTransferController::class, 'approve'])->name('agent-transfers.approve');
            Route::post('agent-transfers/{agent_product_transfer}/reject', [App\Http\Controllers\Admin\AgentTransferController::class, 'reject'])->name('agent-transfers.reject');

            Route::get('branch-transfer/logs', [App\Http\Controllers\Admin\BranchTransferController::class, 'logs'])->name('branch-transfer.logs');
            Route::get('branch-transfer/items', [App\Http\Controllers\Admin\BranchTransferController::class, 'branchItems'])->name('branch-transfer.items');
            Route::get('branch-transfer', [App\Http\Controllers\Admin\BranchTransferController::class, 'create'])->name('branch-transfer');
            Route::post('branch-transfer', [App\Http\Controllers\Admin\BranchTransferController::class, 'store'])->name('branch-transfer.store');

            Route::get('agent-credits', [App\Http\Controllers\Admin\AgentCreditController::class, 'index'])->name('agent-credits');
            Route::get('agent-credits/export-csv', [App\Http\Controllers\Admin\AgentCreditController::class, 'exportCsv'])->name('agent-credits.export-csv');
            Route::get('agent-credits/{id}/edit', [App\Http\Controllers\Admin\AgentCreditController::class, 'edit'])->name('edit-agent-credit');
            Route::get('agent-credits/{id}/invoice', [App\Http\Controllers\Admin\AgentCreditController::class, 'downloadInvoice'])->name('agent-credit-invoice');
            Route::patch('agent-credits/{id}/payment-channel', [App\Http\Controllers\Admin\AgentCreditController::class, 'updatePaymentChannel'])->name('agent-credit-payment-channel');
            Route::patch('agent-credits/{id}/commission', [App\Http\Controllers\Admin\AgentCreditController::class, 'updateCommission'])->name('agent-credits-update-commission');
            Route::post('agent-credits/pay', [App\Http\Controllers\Admin\AgentCreditController::class, 'pay'])->name('agent-credits-pay');
            Route::post('agent-credits/{id}/pay-remaining', [App\Http\Controllers\Admin\AgentCreditController::class, 'payRemaining'])->name('agent-credit-pay-remaining');
            Route::put('agent-credits/{id}', [App\Http\Controllers\Admin\AgentCreditController::class, 'update'])->name('update-agent-credit');
            Route::delete('agent-credits/{id}', [App\Http\Controllers\Admin\AgentCreditController::class, 'destroy'])->name('destroy-agent-credit');

            Route::get('shop-records', [App\Http\Controllers\Admin\StockController::class , 'shopRecords'])->name('shop-records');
            Route::get('payables', [App\Http\Controllers\Admin\StockController::class , 'payables'])->name('payables');
        }
        );

        // System Helpers (for cPanel/Shared Hosting) - creates public/storage dir (no symlink needed)
        Route::get('system/storage-link', function () {
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                abort(403);
            }

            $storageDir = public_path('storage');
            $legacyDir = storage_path('app/public');

            try {
                // If it's a symlink, remove it so we can use a real directory
                if (is_link($storageDir)) {
                    unlink($storageDir);
                }
                if (!is_dir($storageDir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($storageDir, 0755, true);
                    \Illuminate\Support\Facades\File::put($storageDir . '/.gitignore', "*\n!.gitignore\n");
                }
                // Ensure subdirs exist for uploads
                foreach (['products', 'categories'] as $sub) {
                    $path = $storageDir . '/' . $sub;
                    if (!is_dir($path)) {
                        \Illuminate\Support\Facades\File::makeDirectory($path, 0755, true);
                    }
                }
                // Migrate existing files from storage/app/public if present
                if (is_dir($legacyDir)) {
                    foreach (['products', 'categories'] as $sub) {
                        $src = $legacyDir . '/' . $sub;
                        $dst = $storageDir . '/' . $sub;
                        if (is_dir($src)) {
                            $files = glob($src . '/*');
                            foreach ($files as $file) {
                                if (is_file($file)) {
                                    $dest = $dst . '/' . basename($file);
                                    if (!file_exists($dest)) {
                                        if (!is_dir($dst)) {
                                            \Illuminate\Support\Facades\File::makeDirectory($dst, 0755, true);
                                        }
                                        copy($file, $dest);
                                    }
                                }
                            }
                        }
                    }
                }
                return 'Storage directory ready. Uploads are stored in public/storage (no symlink). <a href="' . route('admin.dashboard') . '">Back to Dashboard</a>';
            } catch (\Exception $e) {
                return '<div class="p-6 max-w-2xl"><strong>Error:</strong> ' . e($e->getMessage()) . '<p class="mt-4"><a href="' . route('admin.dashboard') . '">Back to Dashboard</a></p></div>';
            }
        }
        )->name('system.storage-link');
    });

// Regional manager portal
Route::middleware(['auth', 'verified', 'active', 'regionalmanager'])->prefix('regional-manager')->name('regional-manager.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\RegionalManagerController::class, 'dashboard'])->name('dashboard');
    Route::get('region-inventory', [App\Http\Controllers\RegionalManagerController::class, 'regionInventory'])->name('region-inventory');
    Route::get('profile', [App\Http\Controllers\RegionalManagerController::class, 'profile'])->name('profile');
});

// Team leader portal
Route::middleware(['auth', 'verified', 'active', 'teamleader'])->prefix('team-leader')->name('team-leader.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\TeamLeaderController::class, 'dashboard'])->name('dashboard');
    Route::get('team-inventory', [App\Http\Controllers\TeamLeaderController::class, 'teamInventory'])->name('team-inventory');
    Route::get('profile', [App\Http\Controllers\TeamLeaderController::class, 'profile'])->name('profile');
    Route::get('orders', [App\Http\Controllers\TeamLeaderController::class, 'orders'])->name('orders');
    Route::get('cart', [App\Http\Controllers\TeamLeaderController::class, 'cart'])->name('cart');
    Route::get('addresses', [App\Http\Controllers\TeamLeaderController::class, 'addressesIndex'])->name('addresses.index');
    Route::get('addresses/create', [App\Http\Controllers\TeamLeaderController::class, 'addressesCreate'])->name('addresses.create');
    Route::get('addresses/{address}/edit', [App\Http\Controllers\TeamLeaderController::class, 'addressesEdit'])->name('addresses.edit');
});

// Agent dashboard and sales (role = agent)
Route::middleware(['auth', 'verified', 'active', 'agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\AgentController::class, 'dashboard'])->name('dashboard');
    Route::get('assignments/{assignment}/record-sale', [App\Http\Controllers\AgentController::class, 'recordSaleForm'])->name('record-sale-form');
    Route::post('record-sale', [App\Http\Controllers\AgentController::class, 'recordSale'])->name('record-sale');
    Route::get('transfer', [App\Http\Controllers\AgentController::class, 'transferCreate'])->name('transfer.create');
    Route::get('transferable-imeis', [App\Http\Controllers\AgentController::class, 'transferableImeis'])->name('transferable-imeis');
    Route::post('transfer', [App\Http\Controllers\AgentController::class, 'transferStore'])->name('transfer.store');
    Route::get('transfers', [App\Http\Controllers\AgentController::class, 'transfersIndex'])->name('transfers.index');
    Route::post('transfers/{agent_product_transfer}/cancel', [App\Http\Controllers\AgentController::class, 'transferCancel'])->name('transfers.cancel');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/cart', [App\Http\Controllers\CartController::class , 'index'])->name('cart.index');
    Route::post('/cart', [App\Http\Controllers\CartController::class , 'store'])->name('cart.store');
    Route::patch('/cart/{item}', [App\Http\Controllers\CartController::class , 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [App\Http\Controllers\CartController::class , 'destroy'])->name('cart.destroy');

    Route::get('/orders', [App\Http\Controllers\OrderController::class , 'index'])->name('orders.index');
    Route::get('/checkout', [App\Http\Controllers\OrderController::class , 'create'])->name('checkout.create');
    Route::post('/checkout', [App\Http\Controllers\OrderController::class , 'store'])->name('checkout.store');

    Route::get('checkout/pay/{order}', [App\Http\Controllers\SelcomController::class , 'pay'])->name('selcom.pay');
    Route::get('checkout/status/{order}', [App\Http\Controllers\SelcomController::class , 'checkStatus'])->name('selcom.status');
    Route::resource('addresses', App\Http\Controllers\AddressController::class);
});

require __DIR__ . '/auth.php';