<?php

namespace App\Http\Controllers;

use App\Models\Selcompay;
use App\Services\AgentCommissionExpenseService;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Handles Selcom Checkout webhook callback (payment status notification).
 * @see https://developers.selcommobile.com/#checkout-api (Webhook Callback)
 */
class SelcomWebhookController extends Controller
{
    /**
     * Selcom sends POST with: transid, order_id, reference, result, resultcode, payment_status.
     * Note: Webhook only on successful transactions.
     */
    public function __invoke(Request $request): Response
    {
        $orderId = $request->input('order_id');
        $paymentStatus = $request->input('payment_status');
        $reference = $request->input('reference');
        $transid = $request->input('transid');

        Log::info('Selcom webhook received', [
            'order_id' => $orderId,
            'payment_status' => $paymentStatus,
            'reference' => $reference,
            'transid' => $transid,
        ]);

        if (!$orderId) {
            return response('Missing order_id', 400);
        }

        $selcompay = Selcompay::where('order_id', $orderId)->first();
        if (!$selcompay) {
            Log::warning('Selcom webhook: no selcompay found for order_id', ['order_id' => $orderId]);
            return response('OK', 200);
        }

        $status = $paymentStatus ? strtolower($paymentStatus) : 'pending';
        if (in_array($status, ['completed', 'cancelled', 'failed', 'rejected', 'usercancelled'], true)) {
            $selcompay->update(['payment_status' => $status]);

            if ($status === 'completed' && Schema::hasColumn('selcompays', 'purpose')) {
                $selcompay->refresh();

                if ($selcompay->purpose === Selcompay::PURPOSE_AGENT_COMMISSION_CHECKOUT) {
                    app(AgentCommissionExpenseService::class)->bookFromSelcompay($selcompay);
                }

                if ($selcompay->purpose === Selcompay::PURPOSE_VENDOR_SUBSCRIPTION) {
                    app(VendorSubscriptionPaymentService::class)->handleWebhookCompleted($selcompay);
                }
            }
        }

        return response('OK', 200);
    }
}
