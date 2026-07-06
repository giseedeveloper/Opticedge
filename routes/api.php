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
use App\Http\Controllers\Api\RegionalManagerProductTransferApiController;
use App\Http\Controllers\Api\TeamLeaderApiController;
use App\Http\Controllers\Api\TeamLeaderDashboardController;
use App\Http\Controllers\Api\TeamLeaderProductTransferApiController;
use App\Http\Controllers\Api\AgentDeviceReturnApiController;
use App\Http\Controllers\Api\TeamLeaderDeviceReturnApiController;
use App\Http\Controllers\Api\TeamLeaderSaleApiController;
use App\Http\Controllers\Api\TeamLeaderCustomerNeedController;
use App\Http\Controllers\Api\RegionalManagerDeviceReturnApiController;
use App\Http\Controllers\Api\AdminDeviceReturnApiController;
use App\Http\Controllers\Api\UserProfileApiController;
use App\Http\Controllers\Api\AdminImeiApiController;
use App\Http\Controllers\Api\AdminPassthroughApiController;
use App\Http\Controllers\Api\AdminAgentCreditsAdminApiController;
use App\Http\Controllers\Api\AdminLeadsReportApiController;
use App\Http\Controllers\Api\AdminTenantApiController;
use App\Http\Controllers\Api\AdminOrganizationApiController;
use App\Http\Controllers\Api\AdminPayablesApiController;
use App\Http\Controllers\Api\AdminShopRecordsApiController;
use App\Http\Controllers\Api\AdminPayoutApiController;
use App\Http\Controllers\Api\AdminProductApiController;
use App\Http\Controllers\Api\AdminGuestUserApiController;
use App\Http\Controllers\Api\AdminUserManagementApiController;
use App\Http\Controllers\Api\AdminRegionalManagerAssignApiController;
use App\Http\Controllers\Api\AdminVendorApiController;
use App\Http\Controllers\Api\AdminPurchaseApiController;
use App\Http\Controllers\Api\AdminRegionApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminDashboardApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminTenantApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminPackageApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminSubscriptionProfitApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminCommandCenterApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminRegionApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminBrandApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminModelApiController;
use App\Http\Controllers\Api\Superadmin\SuperadminPlatformSettingApiController;
use App\Http\Controllers\Api\PublicShopApiController;
use App\Http\Controllers\Api\ShopCommerceApiController;
use App\Http\Controllers\Api\CustomerDashboardApiController;
use App\Http\Controllers\Api\VendorSubscribeApiController;
use App\Http\Controllers\Api\DeviceTokenApiController;
use App\Http\Controllers\Api\GuestPortalApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PendingRequestCountsApiController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'loginWithGoogle']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/agent', [AuthController::class, 'registerAgent']);
Route::post('/register/dealer', [AuthController::class, 'registerDealer']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

Route::get('/public/categories', [PublicShopApiController::class, 'categories']);
Route::get('/public/products', [PublicShopApiController::class, 'products']);
Route::get('/public/products/{product}', [PublicShopApiController::class, 'showProduct']);
Route::get('/public/packages', [PublicShopApiController::class, 'packages']);
Route::post('/public/vendor-subscribe/intent', [VendorSubscribeApiController::class, 'storeIntent']);
Route::post('/public/vendor-subscribe/intent/{intent}/pay', [VendorSubscribeApiController::class, 'startPayment']);
Route::get('/public/vendor-subscribe/intent/{intent}/status', [VendorSubscribeApiController::class, 'status']);

Route::middleware(['auth:sanctum', 'tenant.context'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user()->only(['id', 'name', 'email', 'role', 'status', 'business_name']);
    });

    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);
    Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail']);

    Route::post('/device-tokens', [DeviceTokenApiController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenApiController::class, 'destroy']);
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::get('/pending-request-counts', [PendingRequestCountsApiController::class, 'show']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationApiController::class, 'markRead']);

    $shopRoutes = function () {
        Route::get('categories', [ShopCommerceApiController::class, 'categories']);
        Route::get('products', [ShopCommerceApiController::class, 'products']);
        Route::get('products/{product}', [ShopCommerceApiController::class, 'showProduct']);
        Route::get('cart', [ShopCommerceApiController::class, 'cart']);
        Route::post('cart', [ShopCommerceApiController::class, 'addToCart']);
        Route::patch('cart/{item}', [ShopCommerceApiController::class, 'updateCartItem']);
        Route::delete('cart/{item}', [ShopCommerceApiController::class, 'removeCartItem']);
        Route::get('addresses', [ShopCommerceApiController::class, 'addresses']);
        Route::post('addresses', [ShopCommerceApiController::class, 'storeAddress']);
        Route::put('addresses/{address}', [ShopCommerceApiController::class, 'updateAddress']);
        Route::delete('addresses/{address}', [ShopCommerceApiController::class, 'destroyAddress']);
        Route::get('orders', [ShopCommerceApiController::class, 'orders']);
        Route::get('orders/{order}', [ShopCommerceApiController::class, 'showOrder']);
        Route::get('checkout', [ShopCommerceApiController::class, 'checkoutPreview']);
        Route::post('checkout', [ShopCommerceApiController::class, 'checkout']);
        Route::get('checkout/status/{order}', [ShopCommerceApiController::class, 'paymentStatus']);
    };

    Route::middleware(['active', 'shop.buyer'])->prefix('customer')->group($shopRoutes);

    Route::middleware(['active', 'customer.dealer'])->prefix('customer')->group(function () {
        Route::get('dashboard', [CustomerDashboardApiController::class, 'index']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
    });

    Route::middleware(['active', 'guest.portal'])->prefix('guest')->group(function () {
        Route::get('dashboard', [GuestPortalApiController::class, 'dashboard']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
    });

    // Admin: stocks (with limit), create stock, add product to product_list
    Route::middleware(['admin', 'subadmin.ability'])->prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('stocks', [ApiStockController::class, 'index']);
        Route::post('stocks', [ApiStockController::class, 'store']);
        Route::get('stocks/under-limit', [ApiStockController::class, 'stocksUnderLimit']);
        Route::get('stocks/{id}', [ApiStockController::class, 'show']);
        Route::get('stocks/{id}/items', [ApiStockController::class, 'items']);
        Route::get('stocks/{id}/models', [ApiStockController::class, 'modelsForStock']);
        Route::get('stocks/{id}/receipts', [AdminPurchaseApiController::class, 'stockReceipts']);
        Route::get('branches', [ApiBranchController::class, 'index']);
        Route::post('branches', [ApiBranchController::class, 'store']);
        Route::put('branches/{branch}', [ApiBranchController::class, 'update']);
        Route::delete('branches/{branch}', [ApiBranchController::class, 'destroy']);
        Route::get('vendors', [AdminVendorApiController::class, 'index']);
        Route::get('vendors/{vendor}', [AdminVendorApiController::class, 'show']);
        Route::post('vendors', [AdminVendorApiController::class, 'store']);
        Route::put('vendors/{vendor}', [AdminVendorApiController::class, 'update']);
        Route::delete('vendors/{vendor}', [AdminVendorApiController::class, 'destroy']);
        Route::get('purchases', [ApiPurchaseController::class, 'index']);
        Route::post('purchases', [AdminPurchaseApiController::class, 'store']);
        Route::post('purchases/update-prices', [AdminPurchaseApiController::class, 'updateAllProductPrices']);
        Route::get('purchases/export-csv', [AdminPurchaseApiController::class, 'exportCsv']);
        Route::get('purchases/receipts', [AdminPurchaseApiController::class, 'receipts']);
        Route::get('purchases/images-gallery', [AdminPurchaseApiController::class, 'imagesGallery']);
        Route::get('purchases/for-add-product', [ApiPurchaseController::class, 'forAddProduct']);
        Route::get('purchases/{id}', [ApiPurchaseController::class, 'show']);
        Route::put('purchases/{id}', [AdminPurchaseApiController::class, 'update']);
        Route::delete('purchases/{id}', [AdminPurchaseApiController::class, 'destroy']);
        Route::get('purchases/{id}/items', [ApiPurchaseController::class, 'items']);
        Route::delete('purchases/{purchaseId}/items/{productListItemId}', [AdminPurchaseApiController::class, 'destroyItem']);
        Route::get('brands', [ApiCategoryController::class, 'index']);
        Route::get('categories', [ApiCategoryController::class, 'index']); // backward compatible alias
        Route::get('categories/{category}/models', [ApiCategoryController::class, 'models']);
        Route::post('categories', [ApiCategoryController::class, 'store']);
        Route::put('categories/{category}', [ApiCategoryController::class, 'update']);
        Route::delete('categories/{category}', [ApiCategoryController::class, 'destroy']);
        Route::get('products', [AdminProductApiController::class, 'index']);
        Route::post('products', [AdminProductApiController::class, 'store']);
        Route::put('products/{product}', [AdminProductApiController::class, 'update']);
        Route::delete('products/{product}', [AdminProductApiController::class, 'destroy']);
        Route::get('products/{product}/imei', [AdminProductApiController::class, 'imeiList']);
        Route::get('imei-search', [AdminImeiApiController::class, 'search']);
        Route::get('imei-items/{productListItem}', [AdminImeiApiController::class, 'show']);
        Route::get('passthrough-sales', [AdminPassthroughApiController::class, 'index']);
        Route::post('passthrough-sales', [AdminPassthroughApiController::class, 'store']);
        Route::get('passthrough-sales/{id}', [AdminPassthroughApiController::class, 'show']);
        Route::put('passthrough-sales/{id}', [AdminPassthroughApiController::class, 'update']);
        Route::delete('passthrough-sales/{id}', [AdminPassthroughApiController::class, 'destroy']);
        Route::get('agent-credits', [AdminAgentCreditsAdminApiController::class, 'index']);
        Route::get('agent-credits/{id}', [AdminAgentCreditsAdminApiController::class, 'show']);
        Route::put('agent-credits/{id}', [AdminAgentCreditsAdminApiController::class, 'update']);
        Route::delete('agent-credits/{id}', [AdminAgentCreditsAdminApiController::class, 'destroy']);
        Route::post('agent-credits/pay', [AdminAgentCreditsAdminApiController::class, 'pay']);
        Route::post('agent-credits/{id}/pay-remaining', [AdminAgentCreditsAdminApiController::class, 'payRemaining']);
        Route::get('agent-credits/{id}/invoice', [AdminAgentCreditsAdminApiController::class, 'invoice']);
        Route::get('customer-needs', [AdminLeadsReportApiController::class, 'index']);
        Route::get('tenant', [AdminTenantApiController::class, 'show']);
        Route::put('tenant', [AdminTenantApiController::class, 'update']);
        Route::post('tenant/subscribe/{package}', [AdminTenantApiController::class, 'subscribe']);
        Route::get('tenant/subscribe/intent/{intent}/status', [AdminTenantApiController::class, 'subscriptionStatus']);
        Route::get('organization-tree', [AdminOrganizationApiController::class, 'index']);
        Route::get('payables', [AdminPayablesApiController::class, 'index']);
        Route::get('shop-records', [AdminShopRecordsApiController::class, 'index']);
        Route::get('payout', [AdminPayoutApiController::class, 'index']);
        Route::post('payout/bulk-selcom', [AdminPayoutApiController::class, 'bulkSelcom']);
        Route::get('payout/selcom/{selcompay}/status', [AdminPayoutApiController::class, 'selcomStatus']);
        Route::get('users', [ApiUserController::class, 'index']); // ?role=customer|dealer|agent|subadmin|all
        Route::get('guest-users', [AdminGuestUserApiController::class, 'index']);
        Route::get('guest-users/{guestUser}', [AdminGuestUserApiController::class, 'show']);
        Route::post('guest-users/{guestUser}/assign', [AdminGuestUserApiController::class, 'assign']);
        Route::get('users/my-permissions', [AdminUserManagementApiController::class, 'myPermissions']);
        Route::get('users/create-form-data', [AdminUserManagementApiController::class, 'createFormData']);
        Route::get('users/{user}', [AdminUserManagementApiController::class, 'show']);
        Route::post('users', [AdminUserManagementApiController::class, 'store']);
        Route::put('users/{user}', [AdminUserManagementApiController::class, 'update']);
        Route::post('users/{user}/transfer-branch', [AdminUserManagementApiController::class, 'transferBranch']);
        Route::put('users/{user}/team-leader', [AdminUserManagementApiController::class, 'updateTeamLeader']);
        Route::post('users/{user}/activate', [AdminUserManagementApiController::class, 'activate']);
        Route::post('users/{user}/deactivate', [AdminUserManagementApiController::class, 'deactivate']);
        Route::post('users/{user}/approve-dealer', [AdminUserManagementApiController::class, 'approveDealer']);
        Route::post('users/{user}/reject-dealer', [AdminUserManagementApiController::class, 'rejectDealer']);
        Route::post('users/{user}/reset-password', [AdminUserManagementApiController::class, 'resetPassword']);
        Route::delete('users/{user}', [AdminUserManagementApiController::class, 'destroy']);
        Route::get('subadmin-roles', [AdminUserManagementApiController::class, 'subadminRoles']);
        Route::get('regional-managers/assign-devices/form-data', [AdminRegionalManagerAssignApiController::class, 'formData']);
        Route::get('regional-managers/assign-devices/purchases/{purchase}/models', [AdminRegionalManagerAssignApiController::class, 'assignableModels']);
        Route::get('regional-managers/assign-devices/assignable-imeis', [AdminRegionalManagerAssignApiController::class, 'assignableImeis']);
        Route::post('regional-managers/assign-devices', [AdminRegionalManagerAssignApiController::class, 'store']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
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
        Route::post('agent-sales', [ApiAgentSaleController::class, 'store']);
        Route::post('agent-sales/{id}/convert-to-credit', [ApiAgentSaleController::class, 'convertToCredit']);
        Route::delete('agent-sales/{id}', [ApiAgentSaleController::class, 'destroy']);
        Route::get('agent-sales/{id}/invoice', [ApiAgentSaleController::class, 'invoice']);
        Route::get('orders', [ApiOrderController::class, 'index']);
        Route::get('orders/{order}', [ApiOrderController::class, 'show']);
        Route::put('orders/{order}', [ApiOrderController::class, 'update']);
        Route::get('distribution-sales/form-data', [ApiDistributionSaleController::class, 'formData']);
        Route::get('distribution-sales/purchases/{purchaseId}/models', [ApiDistributionSaleController::class, 'modelsForPurchase']);
        Route::get('distribution-sales/assignable-imeis', [ApiDistributionSaleController::class, 'assignableImeis']);
        Route::post('distribution-sales/register-imeis', [ApiDistributionSaleController::class, 'registerImeis']);
        Route::get('distribution-sales', [ApiDistributionSaleController::class, 'index']);
        Route::post('distribution-sales', [ApiDistributionSaleController::class, 'store']);
        Route::get('distribution-sales/{id}', [ApiDistributionSaleController::class, 'show']);
        Route::put('distribution-sales/{id}', [ApiDistributionSaleController::class, 'update']);
        Route::delete('distribution-sales/{id}', [ApiDistributionSaleController::class, 'destroy']);
        Route::get('distribution-sales/{id}/invoice', [ApiDistributionSaleController::class, 'invoice']);
        Route::get('pending-sales', [ApiPendingSaleController::class, 'index']);
        Route::get('pending-sales/{id}', [ApiPendingSaleController::class, 'show']);
        Route::post('pending-sales/{id}/save', [ApiPendingSaleController::class, 'save']);
        Route::get('reports', [ApiReportController::class, 'index']);
        Route::get('reports/agent-stock-export', [ApiReportController::class, 'exportAgentStock']);
        Route::get('reports/branches/{branchId}', [ApiReportController::class, 'branchDetail']);
        Route::get('settings', [ApiSettingController::class, 'index']);
        Route::put('settings', [ApiSettingController::class, 'update']);
        Route::post('settings/storage-link', [ApiSettingController::class, 'storageLink']);
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

        Route::get('device-returns', [AdminDeviceReturnApiController::class, 'index']);
        Route::get('device-returns/{return}', [AdminDeviceReturnApiController::class, 'show']);
        Route::post('device-returns/{return}/accept', [AdminDeviceReturnApiController::class, 'accept']);
        Route::post('device-returns/{return}/decline', [AdminDeviceReturnApiController::class, 'decline']);

        Route::get('branch-transfer/items', [AdminBranchTransferApiController::class, 'items']);
        Route::post('branch-transfer', [AdminBranchTransferApiController::class, 'store']);
        Route::get('branch-transfer/logs', [AdminBranchTransferApiController::class, 'logs']);

        Route::get('regions', [AdminRegionApiController::class, 'index']);
        Route::post('regions', [AdminRegionApiController::class, 'store']);
        Route::put('regions/{region}', [AdminRegionApiController::class, 'update']);
        Route::delete('regions/{region}', [AdminRegionApiController::class, 'destroy']);
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

        Route::get('transfers', [AgentProductTransferApiController::class, 'index']);
        Route::get('transfers/{agent_product_transfer}', [AgentProductTransferApiController::class, 'show']);
        Route::post('transfers/{agent_product_transfer}/cancel', [AgentProductTransferApiController::class, 'cancel']);
        Route::post('transfers/{agent_product_transfer}/accept', [AgentProductTransferApiController::class, 'accept']);
        Route::post('transfers/{agent_product_transfer}/decline', [AgentProductTransferApiController::class, 'decline']);

        Route::get('return-devices/assignable-imeis', [AgentProductTransferApiController::class, 'returnableImeis']);
        Route::post('return-devices', [AgentProductTransferApiController::class, 'returnToTeamLeader']);

        Route::get('return-requests', [AgentDeviceReturnApiController::class, 'index']);
        Route::get('return-requests/{return}', [AgentDeviceReturnApiController::class, 'show']);
        Route::post('return-requests/{return}/cancel', [AgentDeviceReturnApiController::class, 'cancel']);

        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
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
        Route::get('return-requests/incoming', [RegionalManagerDeviceReturnApiController::class, 'indexIncoming']);
        Route::get('return-requests/outgoing', [RegionalManagerDeviceReturnApiController::class, 'indexOutgoing']);
        Route::post('return-requests/incoming/{return}/accept', [RegionalManagerDeviceReturnApiController::class, 'acceptIncoming']);
        Route::post('return-requests/incoming/{return}/decline', [RegionalManagerDeviceReturnApiController::class, 'declineIncoming']);
        Route::post('return-requests/outgoing/{return}/cancel', [RegionalManagerDeviceReturnApiController::class, 'cancelOutgoing']);
        Route::get('transfers', [RegionalManagerProductTransferApiController::class, 'index']);
        Route::get('transfers/{transfer}', [RegionalManagerProductTransferApiController::class, 'show']);
        Route::post('transfers/{transfer}/accept', [RegionalManagerProductTransferApiController::class, 'accept']);
        Route::post('transfers/{transfer}/decline', [RegionalManagerProductTransferApiController::class, 'decline']);
        Route::get('orders', [ShopCommerceApiController::class, 'orders']);
        Route::get('orders/{order}', [ShopCommerceApiController::class, 'showOrder']);
        Route::get('cart', [ShopCommerceApiController::class, 'cart']);
        Route::post('cart', [ShopCommerceApiController::class, 'addToCart']);
        Route::patch('cart/{item}', [ShopCommerceApiController::class, 'updateCartItem']);
        Route::delete('cart/{item}', [ShopCommerceApiController::class, 'removeCartItem']);
        Route::get('addresses', [ShopCommerceApiController::class, 'addresses']);
        Route::post('addresses', [ShopCommerceApiController::class, 'storeAddress']);
        Route::put('addresses/{address}', [ShopCommerceApiController::class, 'updateAddress']);
        Route::delete('addresses/{address}', [ShopCommerceApiController::class, 'destroyAddress']);
        Route::get('checkout', [ShopCommerceApiController::class, 'checkoutPreview']);
        Route::post('checkout', [ShopCommerceApiController::class, 'checkout']);
        Route::get('checkout/status/{order}', [ShopCommerceApiController::class, 'paymentStatus']);
        Route::get('categories', [ShopCommerceApiController::class, 'categories']);
        Route::get('products', [ShopCommerceApiController::class, 'products']);
        Route::get('products/{product}', [ShopCommerceApiController::class, 'showProduct']);
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
        Route::get('return-requests/incoming', [TeamLeaderDeviceReturnApiController::class, 'indexIncoming']);
        Route::get('return-requests/outgoing', [TeamLeaderDeviceReturnApiController::class, 'indexOutgoing']);
        Route::get('return-requests/incoming/{return}', [TeamLeaderDeviceReturnApiController::class, 'showIncoming']);
        Route::get('return-requests/outgoing/{return}', [TeamLeaderDeviceReturnApiController::class, 'showOutgoing']);
        Route::post('return-requests/incoming/{return}/accept', [TeamLeaderDeviceReturnApiController::class, 'acceptIncoming']);
        Route::post('return-requests/incoming/{return}/decline', [TeamLeaderDeviceReturnApiController::class, 'declineIncoming']);
        Route::post('return-requests/outgoing/{return}/cancel', [TeamLeaderDeviceReturnApiController::class, 'cancelOutgoing']);
        Route::get('transfers', [TeamLeaderProductTransferApiController::class, 'index']);
        Route::get('transfers/{transfer}', [TeamLeaderProductTransferApiController::class, 'show']);
        Route::post('transfers/{transfer}/accept', [TeamLeaderProductTransferApiController::class, 'accept']);
        Route::post('transfers/{transfer}/decline', [TeamLeaderProductTransferApiController::class, 'decline']);
        Route::get('product-list/available', [TeamLeaderSaleApiController::class, 'available']);
        Route::get('product-list/by-imei/{imei}', [TeamLeaderSaleApiController::class, 'showByImei']);
        Route::get('sale-config', [ApiPaymentOptionController::class, 'agentSaleConfig']);
        Route::post('sell-credit', [TeamLeaderSaleApiController::class, 'sellCredit']);
        Route::get('catalog/categories', [AgentCatalogController::class, 'categories']);
        Route::get('catalog/categories/{category}/products', [AgentCatalogController::class, 'productsByCategory']);
        Route::get('branches', [ApiBranchController::class, 'index']);
        Route::post('customer-needs', [TeamLeaderCustomerNeedController::class, 'store']);
        Route::get('customer-needs', [TeamLeaderCustomerNeedController::class, 'index']);
        Route::get('customer-needs/{id}', [TeamLeaderCustomerNeedController::class, 'show']);
        Route::get('credits', [TeamLeaderSaleApiController::class, 'credits']);
        Route::get('credits/{id}', [TeamLeaderSaleApiController::class, 'creditDetail']);
        Route::get('credits/{id}/invoice', [TeamLeaderSaleApiController::class, 'downloadInvoice']);
        Route::get('sales', [TeamLeaderSaleApiController::class, 'sales']);
        Route::get('orders', [ShopCommerceApiController::class, 'orders']);
        Route::get('orders/{order}', [ShopCommerceApiController::class, 'showOrder']);
        Route::get('cart', [ShopCommerceApiController::class, 'cart']);
        Route::post('cart', [ShopCommerceApiController::class, 'addToCart']);
        Route::patch('cart/{item}', [ShopCommerceApiController::class, 'updateCartItem']);
        Route::delete('cart/{item}', [ShopCommerceApiController::class, 'removeCartItem']);
        Route::get('addresses', [ShopCommerceApiController::class, 'addresses']);
        Route::post('addresses', [ShopCommerceApiController::class, 'storeAddress']);
        Route::put('addresses/{address}', [ShopCommerceApiController::class, 'updateAddress']);
        Route::delete('addresses/{address}', [ShopCommerceApiController::class, 'destroyAddress']);
        Route::get('checkout', [ShopCommerceApiController::class, 'checkoutPreview']);
        Route::post('checkout', [ShopCommerceApiController::class, 'checkout']);
        Route::get('checkout/status/{order}', [ShopCommerceApiController::class, 'paymentStatus']);
        Route::get('categories', [ShopCommerceApiController::class, 'categories']);
        Route::get('products', [ShopCommerceApiController::class, 'products']);
        Route::get('products/{product}', [ShopCommerceApiController::class, 'showProduct']);
        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
    });

    Route::middleware('superadmin')->prefix('superadmin')->group(function () {
        Route::get('dashboard', [SuperadminDashboardApiController::class, 'index']);

        Route::get('tenants/form-data', [SuperadminTenantApiController::class, 'formData']);
        Route::get('tenants', [SuperadminTenantApiController::class, 'index']);
        Route::get('tenants/{tenant}', [SuperadminTenantApiController::class, 'show']);
        Route::post('tenants', [SuperadminTenantApiController::class, 'store']);
        Route::put('tenants/{tenant}', [SuperadminTenantApiController::class, 'update']);
        Route::post('tenants/{tenant}/suspend', [SuperadminTenantApiController::class, 'suspend']);

        Route::get('packages', [SuperadminPackageApiController::class, 'index']);
        Route::get('packages/{id}', [SuperadminPackageApiController::class, 'show'])->whereNumber('id');
        Route::post('packages', [SuperadminPackageApiController::class, 'store']);
        Route::put('packages/{id}', [SuperadminPackageApiController::class, 'update'])->whereNumber('id');
        Route::delete('packages/{id}', [SuperadminPackageApiController::class, 'destroy'])->whereNumber('id');

        Route::get('subscription-profits', [SuperadminSubscriptionProfitApiController::class, 'index']);

        Route::get('command-center', [SuperadminCommandCenterApiController::class, 'index']);
        Route::post('command-center/execute', [SuperadminCommandCenterApiController::class, 'execute']);
        Route::post('command-center/migrate-path', [SuperadminCommandCenterApiController::class, 'migratePath']);
        Route::post('command-center/seed-class', [SuperadminCommandCenterApiController::class, 'seedClass']);
        Route::post('command-center/empty-table', [SuperadminCommandCenterApiController::class, 'emptyTable']);
        Route::post('command-center/extension-track', [SuperadminCommandCenterApiController::class, 'trackExtension']);
        Route::post('command-center/extension-untrack', [SuperadminCommandCenterApiController::class, 'untrackExtension']);
        Route::get('command-center/run/{command}', [SuperadminCommandCenterApiController::class, 'runCommand']);

        Route::get('regions', [SuperadminRegionApiController::class, 'index']);
        Route::post('regions', [SuperadminRegionApiController::class, 'store']);
        Route::put('regions/{region}', [SuperadminRegionApiController::class, 'update']);
        Route::delete('regions/{region}', [SuperadminRegionApiController::class, 'destroy']);

        Route::get('brands', [SuperadminBrandApiController::class, 'index']);
        Route::post('brands', [SuperadminBrandApiController::class, 'store']);
        Route::put('brands/{brand}', [SuperadminBrandApiController::class, 'update']);
        Route::delete('brands/{brand}', [SuperadminBrandApiController::class, 'destroy']);

        Route::get('models/form-data', [SuperadminModelApiController::class, 'formData']);
        Route::get('models', [SuperadminModelApiController::class, 'index']);
        Route::post('models', [SuperadminModelApiController::class, 'store']);
        Route::put('models/{model}', [SuperadminModelApiController::class, 'update']);
        Route::delete('models/{model}', [SuperadminModelApiController::class, 'destroy']);

        Route::get('settings', [SuperadminPlatformSettingApiController::class, 'index']);
        Route::put('settings', [SuperadminPlatformSettingApiController::class, 'update']);
        Route::post('settings/test-selcom', [SuperadminPlatformSettingApiController::class, 'testSelcom']);

        Route::get('profile', [UserProfileApiController::class, 'show']);
        Route::put('profile', [UserProfileApiController::class, 'update']);
        Route::put('profile/password', [UserProfileApiController::class, 'updatePassword']);
    });
});
