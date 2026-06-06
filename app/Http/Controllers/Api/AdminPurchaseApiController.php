<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Admin\StockController as WebStockController;
use App\Http\Controllers\Api\Concerns\AdaptsWebAdminResponses;
use App\Http\Controllers\Controller;
use App\Models\ProductListItem;
use App\Models\Purchase;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPurchaseApiController extends Controller
{
    use AdaptsWebAdminResponses;

    public function store(Request $request): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->storePurchase($request),
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->updatePurchase($request, $id)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->destroyPurchase($id)
        );
    }

    public function destroyItem(int $purchaseId, int $productListItemId): JsonResponse
    {
        $purchase = Purchase::stockPurchases()->findOrFail($purchaseId);
        $item = ProductListItem::findOrFail($productListItemId);

        return $this->adaptWebResponse(
            app(WebStockController::class)->destroyPurchaseItem($purchase, $item)
        );
    }

    public function updateAllProductPrices(): JsonResponse
    {
        return $this->adaptWebResponse(
            app(WebStockController::class)->updateAllProductPrices()
        );
    }

    public function exportCsv(Request $request): mixed
    {
        return app(WebStockController::class)->exportPurchasesCsv($request);
    }

    public function receipts(): JsonResponse
    {
        $purchases = Purchase::stockPurchases()
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->limit(200)
            ->get()
            ->map(fn (Purchase $p) => [
                'id' => $p->id,
                'name' => $p->name ?? 'Purchase #'.$p->id,
                'date' => $p->date,
                'distributor_name' => $p->distributor_name,
                'payment_receipt_image' => $p->payment_receipt_image,
                'payment_receipt_url' => $p->payment_receipt_image
                    ? asset('storage/'.$p->payment_receipt_image)
                    : null,
            ]);

        return response()->json(['data' => $purchases]);
    }

    public function stockReceipts(int $stockId): JsonResponse
    {
        $stock = Stock::findOrFail($stockId);
        $purchases = Purchase::stockPurchases()
            ->where('stock_id', $stock->id)
            ->whereNotNull('payment_receipt_image')
            ->latest('date')
            ->get()
            ->map(fn (Purchase $p) => [
                'id' => $p->id,
                'name' => $p->name ?? 'Purchase #'.$p->id,
                'date' => $p->date,
                'payment_receipt_url' => asset('storage/'.$p->payment_receipt_image),
            ]);

        return response()->json([
            'data' => [
                'stock_id' => $stock->id,
                'stock_name' => $stock->name,
                'receipts' => $purchases,
            ],
        ]);
    }

    public function imagesGallery(): JsonResponse
    {
        return app(PurchaseController::class)->imagesGallery();
    }
}
