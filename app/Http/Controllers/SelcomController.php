<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Selcompay;
use App\Support\TeamLeaderRoutes;
use App\Services\DistributionSaleService;
use App\Services\SelcomApiService;
use App\Services\SelcomCredentialResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SelcomController extends Controller
{
    /**
     * @return array{vendor: string, api_key: string, api_secret: string, live: bool}
     */
    protected function getSelcomCredentialsFromDb(): array
    {
        return app(SelcomCredentialResolver::class)->resolve();
    }

    protected function getSelcomService(): SelcomApiService
    {
        $creds = $this->getSelcomCredentialsFromDb();
        return new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );
    }

    public function pay(Order $order)
    {
        $paymentPhone = session('payment_phone');

        if (!$paymentPhone) {
            Log::warning('Selcom payment attempt without phone number', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);
            return redirect()->route('checkout.create')->with('error', 'Payment phone number is missing. Please provide a valid phone number.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $paymentPhone);
        if (!preg_match('/^(255)?[67]\d{8}$/', $cleanPhone)) {
            Log::warning('Invalid phone number format for Selcom payment', [
                'order_id' => $order->id,
                'phone' => $paymentPhone,
            ]);
            return redirect()->route('checkout.create')->with('error', 'Invalid phone number format. Please use format: 7XXXXXXXX');
        }

        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '255' . $cleanPhone;
        }

        try {
            $transid = 'ORDER_' . $order->id . '_' . time();
            $orderId = 'SELCOM' . now()->timestamp . rand(1000, 9999);

            $creds = $this->getSelcomCredentialsFromDb();
            $redirectUrl = config('selcom.redirect_url') ?: TeamLeaderRoutes::ordersIndexUrl($order->user);
            $cancelUrl = config('selcom.cancel_url') ?: route('checkout.create');
            $webhookUrl = route('selcom.checkout-callback');

            $selcom = $this->getSelcomService();

            $createPayload = [
                'vendor' => $creds['vendor'],
                'order_id' => $orderId,
                'buyer_email' => $order->user->email,
                'buyer_name' => $order->user->name,
                'buyer_phone' => $cleanPhone,
                'amount' => (int) round($order->total_price ?? $order->total ?? 0),
                'currency' => 'TZS',
                'redirect_url' => base64_encode($redirectUrl),
                'cancel_url' => base64_encode($cancelUrl),
                'webhook' => base64_encode($webhookUrl),
                'buyer_remarks' => 'Order ' . $order->id,
                'merchant_remarks' => '',
                'no_of_items' => (int) $order->items->count(),
                'expiry' => (int) (config('selcom.expiry') ?? 60),
            ];
            $colors = config('selcom.colors', []);
            if (!empty($colors['header'])) {
                $createPayload['header_colour'] = $colors['header'];
            }
            if (!empty($colors['link'])) {
                $createPayload['link_colour'] = $colors['link'];
            }
            if (!empty($colors['button'])) {
                $createPayload['button_colour'] = $colors['button'];
            }

            Log::info('Initiating Selcom Checkout', [
                'order_id' => $order->id,
                'transaction_id' => $transid,
                'amount' => $createPayload['amount'],
                'phone' => substr($cleanPhone, -4),
                'user_id' => $order->user_id,
            ]);

            $createResponse = $selcom->createOrderMinimal($createPayload);

            if (isset($createResponse['resultcode']) && $createResponse['resultcode'] !== '000') {
                $errorMessage = $createResponse['message'] ?? $createResponse['result'] ?? 'Payment gateway error';
                Log::error('Selcom Create Order Error', [
                    'order_id' => $order->id,
                    'transaction_id' => $transid,
                    'response' => $createResponse,
                ]);
                return redirect()->route('checkout.create')->with('error', 'Payment gateway error: ' . $errorMessage . '. Please try again or contact support.');
            }

            $walletResponse = $selcom->walletPayment($transid, $orderId, $cleanPhone);

            Log::info('Selcom Wallet Payment Response', [
                'order_id' => $order->id,
                'transaction_id' => $transid,
                'response' => $walletResponse,
            ]);

            if (isset($walletResponse['resultcode']) && $walletResponse['resultcode'] !== '000' && $walletResponse['resultcode'] !== '111') {
                $errorMessage = $walletResponse['message'] ?? $walletResponse['result'] ?? 'Payment request failed';
                Log::error('Selcom Wallet Payment Error', [
                    'order_id' => $order->id,
                    'response' => $walletResponse,
                ]);
                return redirect()->route('checkout.create')->with('error', 'Payment request failed: ' . $errorMessage . '. Please try again.');
            }

            try {
                Selcompay::create([
                    'transid' => $transid,
                    'order_id' => $orderId,
                    'phone_number' => $cleanPhone,
                    'amount' => $createPayload['amount'],
                    'payment_status' => 'pending',
                    'local_order_id' => $order->id,
                    'purpose' => Selcompay::PURPOSE_ORDER_PAYMENT,
                ]);

                Log::info('Selcom payment record created', [
                    'order_id' => $order->id,
                    'transid' => $transid,
                    'selcom_order_id' => $orderId,
                ]);

                session(['payment_phone' => $cleanPhone]);

                return view('checkout.payment-processing', compact('order'));
            } catch (\Exception $dbError) {
                Log::error('Failed to save Selcom payment record', [
                    'order_id' => $order->id,
                    'error' => $dbError->getMessage(),
                ]);
                return redirect()->route('checkout.create')->with('error', 'An error occurred saving payment details. Please try again.');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Selcom Connection Error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return redirect()->route('checkout.create')->with('error', 'Connection Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Selcom Payment Unexpected Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('checkout.create')->with('error', 'Payment Error: ' . $e->getMessage());
        }
    }

    public function checkStatus(Order $order)
    {
        try {
            $selcompay = Selcompay::where('local_order_id', $order->id)->latest()->first();

            if (!$selcompay) {
                Log::warning('No Selcom payment record found for order', ['order_id' => $order->id]);
                return response()->json(['status' => 'error', 'message' => 'No payment record found for this order.']);
            }

            if (!$selcompay->order_id) {
                $createdAt = \Carbon\Carbon::parse($selcompay->created_at);
                $minutesPending = $createdAt->diffInMinutes(now());
                if ($minutesPending > 10) {
                    $selcompay->update(['payment_status' => 'timeout']);
                    $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                    return response()->json(['status' => 'timeout', 'message' => 'Payment request timed out. Please try again.']);
                }
                return response()->json(['status' => 'pending', 'message' => 'Waiting for payment confirmation...']);
            }

            $selcom = $this->getSelcomService();
            $statusArr = $selcom->orderStatus($selcompay->order_id);

            Log::info('Selcom status check', [
                'order_id' => $order->id,
                'selcom_order_id' => $selcompay->order_id,
                'status_response' => $statusArr,
            ]);

            if (!isset($statusArr['resultcode'])) {
                return response()->json(['status' => 'error', 'message' => 'Unable to verify payment status. Please contact support.']);
            }

            if ($statusArr['resultcode'] !== '000') {
                $errorMessage = $statusArr['message'] ?? $statusArr['result'] ?? 'Unknown error';
                return response()->json(['status' => 'error', 'message' => 'Payment verification failed: ' . $errorMessage]);
            }

            $paymentStatus = $statusArr['data'][0]['payment_status'] ?? null;

            if ($paymentStatus === 'COMPLETED') {
                $selcompay->update(['payment_status' => 'completed']);
                $order->update(['payment_status' => 'paid', 'status' => 'processed']);

                $order->load(['items.product.category', 'user']);
                if ($order->user && $order->user->role === 'dealer') {
                    app(DistributionSaleService::class)->createFromOrder($order, 'complete');
                }

                $cart = \App\Models\Cart::where('user_id', $order->user_id)->first();
                if ($cart) {
                    foreach ($order->items as $orderItem) {
                        $product = $orderItem->product;
                        if ($product && $product->stock_quantity >= $orderItem->quantity) {
                            $product->decrement('stock_quantity', $orderItem->quantity);
                        }
                    }
                    $cart->items()->delete();
                    $cart->delete();
                }

                return response()->json(['status' => 'completed', 'message' => 'Payment successful!']);
            }

            if (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'USERCANCELLED'])) {
                $selcompay->update(['payment_status' => 'failed']);
                $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                return response()->json(['status' => 'failed', 'message' => 'Payment ' . strtolower($paymentStatus ?? 'failed') . '. Please try again.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment is being processed...']);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['status' => 'error', 'message' => 'Unable to connect to payment gateway. Please check your connection.']);
        } catch (\Exception $e) {
            Log::error('Selcom status check error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Error checking payment status: ' . $e->getMessage()]);
        }
    }
}
