<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StockController as ApiStockController;
use App\Http\Controllers\Api\PurchaseController as ApiPurchaseController;
use App\Http\Controllers\Api\ProductListController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController as ApiExpenseController;
use App\Http\Controllers\Api\PaymentOptionController as ApiPaymentOptionController;
use App\Http\Controllers\Api\AgentSaleController as ApiAgentSaleController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\DistributionSaleController as ApiDistributionSaleController;
use App\Http\Controllers\Api\PendingSaleController as ApiPendingSaleController;
use App\Http\Controllers\Api\BranchController as ApiBranchController;
use App\Http\Controllers\Api\ReportController as ApiReportController;
use App\Http\Controllers\Api\BarcodeDecodeController;
use App\Http\Controllers\Api\SettingController as ApiSettingController;
use App\Http\Controllers\Api\AgentCreditApiController;
use App\Http\Controllers\Api\AgentProductTransferApiController;
use App\Http\Controllers\Api\AdminAgentProductTransferApiController;
use App\Http\Controllers\Api\AdminBranchTransferApiController;
use App\Http\Controllers\Api\AgentCatalogController;
use App\Http\Controllers\Api\AgentCustomerNeedController;
use App\Http\Controllers\Api\RegionalManagerApiController;
use App\Http\Controllers\Api\RegionalManagerDashboardController;
use App\Http\Controllers\Api\TeamLeaderApiController;
use App\Http\Controllers\Api\TeamLeaderDashboardController;
use App\Http\Controllers\Api\UserProfileApiController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->only(['id', 'name', 'email', 'role']);
    });

    // Admin: stocks (with limit), create stock, add product to product_list
    Route::middleware(['admin', 'subadmin.ability'])->prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('stocks', [ApiStockController::class, 'index']);
        Route::post('stocks', [ApiStockController::class, 'store']);
        Route::get('stocks/under-limit', [ApiStockController::class, 'stocksUnderLimit']);
        Route::get('stocks/{id}/models', [ApiStockController::class, 'modelsForStock']);
        Route::get('branches', [ApiBranchController::class, 'index']);
        Route::get('purchases', [ApiPurchaseController::class, 'index']);
        Route::get('purchases/for-add-product', [ApiPurchaseController::class, 'forAddProduct']);
        Route::get('purchases/{id}', [ApiPurchaseController::class, 'show']);
        Route::get('purchases/{id}/items', [ApiPurchaseController::class, 'items']);
        Route::get('brands', [ApiCategoryController::class, 'index']);
        Route::get('categories', [ApiCategoryController::class, 'index']); // backward compatible alias
        Route::get('categories/{category}/models', [ApiCategoryController::class, 'models']);
        Route::post('product-list', [ProductListController::class, 'store']);
        Route::post('product-list/batch', [ProductListController::class, 'batchStore']);
        Route::post('barcodes/decode-image', [BarcodeDecodeController::class, 'decodeImage']);
        Route::get('expenses', [ApiExpenseController::class, 'index']);
        Route::get('expenses/{id}', [ApiExpenseController::class, 'show']);
        Route::post('expenses', [ApiExpenseController::class, 'store']);
        Route::put('expenses/{id}', [ApiExpenseController::class, 'update']);
        Route::delete('expenses/{id}', [ApiExpenseController::class, 'destroy']);
        Route::get('payment-options', [ApiPaymentOptionController::class, 'index']);
        Route::post('payment-options/transfers', [ApiPaymentOptionController::class, 'transfer']);
        Route::get('payment-options/transfers/history', [ApiPaymentOptionController::class, 'transferHistory']);
        Route::get('payment-options/{id}', [ApiPaymentOptionController::class, 'show']);
        Route::post('payment-options', [ApiPaymentOptionController::class, 'store']);
        Route::put('payment-options/{id}', [ApiPaymentOptionController::class, 'update']);
        Route::delete('payment-options/{id}', [ApiPaymentOptionController::class, 'destroy']);
        Route::patch('payment-options/{id}/toggle-visibility', [ApiPaymentOptionController::class, 'toggleVisibility']);
        Route::patch('payment-options/{id}/shrink-balance', [ApiPaymentOptionController::class, 'shrinkBalance']);
        Route::get('agent-sales', [ApiAgentSaleController::class, 'index']);
        Route::get('orders', [ApiOrderController::class, 'index']);
        Route::get('orders/{order}', [ApiOrderController::class, 'show']);
        Route::put('orders/{order}', [ApiOrderController::class, 'update']);
        Route::get('users', [ApiUserController::class, 'index']); // ?role=customer|dealer|agent
        Route::get('distribution-sales', [ApiDistributionSaleController::class, 'index']);
        Route::get('distribution-sales/{id}', [ApiDistributionSaleController::class, 'show']);
        Route::get('pending-sales', [ApiPendingSaleController::class, 'index']);
        Route::get('pending-sales/{id}', [ApiPendingSaleController::class, 'show']);
        Route::post('pending-sales/{id}/save', [ApiPendingSaleController::class, 'save']);
        Route::get('reports', [ApiReportController::class, 'index']);
        Route::get('reports/branches/{branchId}', [ApiReportController::class, 'branchDetail']);
        Route::get('settings', [ApiSettingController::class, 'index']);
        Route::put('settings', [ApiSettingController::class, 'update']);
        Route::get('settings/roles', [ApiSettingController::class, 'roles']);
        Route::post('settings/roles', [ApiSettingController::class, 'storeRole']);
        Route::get('settings/roles/{id}/permissions', [ApiSettingController::class, 'rolePermissions']);
        Route::put('settings/roles/{id}/permissions', [ApiSettingController::class, 'updateRolePermissions']);
        Route::post('agent-sales/{id}/channel', [ApiAgentSaleController::class, 'updateChannel']);
        Route::post('agent-sales/{id}/commission', [ApiAgentSaleController::class, 'updateCommission']);

        Route::get('agent-transfers', [AdminAgentProductTransferApiController::class, 'index']);
        Route::get('agent-transfers/{agent_product_transfer}', [AdminAgentProductTransferApiController::class, 'show']);
        Route::post('agent-transfers/{agent_product_transfer}/approve', [AdminAgentProductTransferApiController::class, 'approve']);
        Route::post('agent-transfers/{agent_product_transfer}/reject', [AdminAgentProductTransferApiController::class, 'reject']);

        Route::get('branch-transfer/items', [AdminBranchTransferApiController::class, 'items']);
        Route::post('branch-transfer', [AdminBranchTransferApiController::class, 'store']);
        Route::get('branch-transfer/logs', [AdminBranchTransferApiController::class, 'logs']);
    });

    // Agent: dashboard, available products (unsold only), get device by IMEI, record sale
    Route::middleware('agent')->prefix('agent')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\AgentDashboardController::class, 'index']);
        Route::get('dashboard/inventory', [\App\Http\Controllers\Api\AgentDashboardController::class, 'inventory']);
        Route::get('product-list/available', [ProductListController::class, 'available']);
        Route::get('product-list/by-imei/{imei}', [ProductListController::class, 'showByImei']);
        Route::get('assignments/total', [ProductListController::class, 'totalAssignments']);
        Route::get('assignments/total/by-imei/{imei}', [ProductListController::class, 'totalAssignmentByImei']);
        Route::get('payment-options', [ApiPaymentOptionController::class, 'indexVisible']);
        Route::get('sale-config', [ApiPaymentOptionController::class, 'agentSaleConfig']);
        Route::post('sell', [ProductListController::class, 'sell']);
        Route::post('sell-credit', [ProductListController::class, 'sellCredit']);
        Route::post('sell-given', [ProductListController::class, 'sellGiven']);
        Route::get('catalog/brands', [AgentCatalogController::class, 'categories']);
        Route::get('catalog/brands/{category}/models', [AgentCatalogController::class, 'productsByCategory']);
        Route::get('catalog/categories', [AgentCatalogController::class, 'categories']); // backward compatible alias
        Route::get('catalog/categories/{category}/products', [AgentCatalogController::class, 'productsByCategory']); // backward compatible alias
        Route::get('branches', [ApiBranchController::class, 'index']);
        Route::post('customer-needs', [AgentCustomerNeedController::class, 'store']);
        Route::get('customer-needs', [AgentCustomerNeedController::class, 'index']);
        Route::get('customer-needs/{id}', [AgentCustomerNeedController::class, 'show']);
        Route::get('credits', [AgentCreditApiController::class, 'index']);
        Route::get('credits/{id}', [AgentCreditApiController::class, 'show']);
        Route::post('credits/{id}/pay', [AgentCreditApiController::class, 'payInstallment']);
        Route::get('credits/{id}/invoice', [AgentCreditApiController::class, 'downloadInvoice']);
        Route::get('sales', [\App\Http\Controllers\Api\AgentDashboardController::class, 'sales']);
        Route::get('sales/{id}', [\App\Http\Controllers\Api\AgentDashboardController::class, 'saleDetail']);
        Route::get('sales/{id}/invoice', [\App\Http\Controllers\Api\AgentDashboardController::class, 'downloadSaleInvoice']);

        Route::get('transfer-recipients', [AgentProductTransferApiController::class, 'transferRecipients']);
        Route::get('transferable-imeis', [AgentProductTransferApiController::class, 'transferableImeis']);
        Route::get('transfers', [AgentProductTransferApiController::class, 'index']);
        Route::post('transfers', [AgentProductTransferApiController::class, 'store']);
        Route::get('transfers/{agent_product_transfer}', [AgentProductTransferApiController::class, 'show']);
        Route::post('transfers/{agent_product_transfer}/cancel', [AgentProductTransferApiController::class, 'cancel']);

        Route::get('return-devices/assignable-imeis', [AgentProductTransferApiController::class, 'returnableImeis']);
        Route::post('return-devices', [AgentProductTransferApiController::class, 'returnToTeamLeader']);
    });

    Route::middleware('regionalmanager')->prefix('regional-manager')->group(function () {
        Route::get('dashboard', [RegionalManagerDashboardController::class, 'index']);
        Route::get('region-inventory', [RegionalManagerApiController::class, 'regionInventory']);
        Route::get('assign-team-leader/form-data', [RegionalManagerApiController::class, 'assignTeamLeaderFormData']);
        Route::get('assign-team-leader/assignable-imeis', [RegionalManagerApiController::class, 'assignableImeisForTeamLeader']);
        Route::post('assign-team-leader/validate-imei', [RegionalManagerApiController::class, 'validateAssignTeamLeaderImei']);
        Route::post('assign-team-leader', [RegionalManagerApiController::class, 'storeAssignTeamLeader']);
        Route::get('return-devices/form-data', [RegionalManagerApiController::class, 'returnDevicesFormData']);
        Route::get('return-devices/assignable-imeis', [RegionalManagerApiController::class, 'returnableImeis']);
        Route::post('return-devices', [RegionalManagerApiController::class, 'storeReturnDevices']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
    });

    Route::middleware('teamleader')->prefix('team-leader')->group(function () {
        Route::get('dashboard', [TeamLeaderDashboardController::class, 'index']);
        Route::get('team-inventory', [TeamLeaderApiController::class, 'teamInventory']);
        Route::get('assign-agent/form-data', [TeamLeaderApiController::class, 'assignAgentFormData']);
        Route::get('assign-agent/assignable-imeis', [TeamLeaderApiController::class, 'assignableImeisForAgent']);
        Route::post('assign-agent/validate-imei', [TeamLeaderApiController::class, 'validateAssignAgentImei']);
        Route::post('assign-agent', [TeamLeaderApiController::class, 'storeAssignAgent']);
        Route::get('return-devices/form-data', [TeamLeaderApiController::class, 'returnDevicesFormData']);
        Route::get('return-devices/assignable-imeis', [TeamLeaderApiController::class, 'returnableImeis']);
        Route::post('return-devices', [TeamLeaderApiController::class, 'storeReturnDevices']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
    });
});
