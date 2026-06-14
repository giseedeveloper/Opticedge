<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsShopCatalog;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Selcompay;
use App\Services\DistributionSaleService;
use App\Services\SelcomApiService;
use App\Services\SelcomCredentialResolver;
use App\Support\TanzaniaMobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopCommerceApiController extends Controller
{
    use FormatsShopCatalog;

    public function categories()
    {
        return app(PublicShopApiController::class)->categories();
    }

    public function products(Request $request)
    {
        return app(PublicShopApiController::class)->products($request);
    }

    public function showProduct(Product $product)
    {
        return app(PublicShopApiController::class)->showProduct($product);
    }

    public function cart()
    {
        $cart = Cart::with(['items.product.category'])
            ->firstOrCreate(['user_id' => Auth::id()]);

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:models,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($item) {
            $item->quantity += $validated['quantity'];
            $item->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        $cart->load(['items.product.category']);

        return response()->json([
            'message' => 'Product added to cart.',
            'data' => $this->formatCart($cart),
        ]);
    }

    public function updateCartItem(Request $request, CartItem $item)
    {
        if ($item->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item->update(['quantity' => $validated['quantity']]);
        $cart = $item->cart()->with(['items.product.category'])->first();

        return response()->json([
            'message' => 'Cart updated.',
            'data' => $this->formatCart($cart),
        ]);
    }

    public function removeCartItem(CartItem $item)
    {
        if ($item->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cart = $item->cart;
        $item->delete();
        $cart->load(['items.product.category']);

        return response()->json([
            'message' => 'Item removed.',
            'data' => $this->formatCart($cart),
        ]);
    }

    public function addresses()
    {
        $addresses = Auth::user()->addresses()->latest()->get();

        return response()->json([
            'data' => $addresses->map(fn (Address $a) => $this->formatAddress($a))->values(),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'required|string',
            'type' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $address = Auth::user()->addresses()->create($validated);

        return response()->json([
            'message' => 'Address added.',
            'data' => $this->formatAddress($address),
        ], 201);
    }

    public function updateAddress(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'required|string',
            'type' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated.',
            'data' => $this->formatAddress($address),
        ]);
    }

    public function destroyAddress(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }

    public function orders()
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->with(['items.product', 'address'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $o) => $this->formatOrder($o))->values(),
        ]);
    }

    public function showOrder(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['items.product', 'address']);

        return response()->json(['data' => $this->formatOrder($order, detailed: true)]);
    }

    public function checkoutPreview()
    {
        $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();
        $addresses = Auth::user()->addresses;

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);
        $tax = $subtotal * 0.18;

        return response()->json([
            'data' => [
                'cart' => $this->formatCart($cart),
                'addresses' => $addresses->map(fn (Address $a) => $this->formatAddress($a))->values(),
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'total' => round($subtotal + $tax, 2),
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cod,selcom',
            'payment_phone' => 'required_if:payment_method,selcom|nullable|string',
        ]);

        $address = Address::findOrFail($validated['address_id']);
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $total = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);
        $tax = $total * 0.18;
        $grandTotal = $total + $tax;

        $order = Order::create([
            'user_id' => Auth::id(),
            'total_price' => $grandTotal,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'address_id' => $validated['address_id'],
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);

            if ($validated['payment_method'] === 'cod') {
                $product = $item->product;
                if ($product->stock_quantity >= $item->quantity) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }
        }

        app(\App\Services\NotificationDispatchService::class)->orderCreated($order->fresh(['user']));

        if ($validated['payment_method'] === 'selcom') {
            try {
                $result = $this->initiateSelcomPayment($order, $validated['payment_phone']);
            } catch (\Throwable $e) {
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
                app(\App\Services\NotificationDispatchService::class)->orderPaymentFailed($order->fresh(['user']), $e->getMessage());

                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Payment initiated. Approve the request on your phone.',
                'data' => [
                    'order' => $this->formatOrder($order->fresh(['items.product', 'address']), detailed: true),
                    'payment' => $result,
                ],
            ], 201);
        }

        $cart->items()->delete();
        $cart->delete();

        if (Auth::user()->role === 'dealer') {
            $order->load(['items.product.category', 'user']);
            app(DistributionSaleService::class)->createFromOrder($order, 'pending');
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => $this->formatOrder($order->fresh(['items.product', 'address']), detailed: true),
        ], 201);
    }

    public function paymentStatus(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $selcompay = Selcompay::where('local_order_id', $order->id)->latest()->first();

            if (! $selcompay) {
                return response()->json(['status' => 'error', 'message' => 'No payment record found for this order.']);
            }

            if (! $selcompay->order_id) {
                $minutesPending = $selcompay->created_at->diffInMinutes(now());
                if ($minutesPending > 10) {
                    $selcompay->update(['payment_status' => 'timeout']);
                    $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                    app(\App\Services\NotificationDispatchService::class)->orderPaymentFailed($order->fresh(['user']), 'Payment timed out.');

                    return response()->json(['status' => 'timeout', 'message' => 'Payment request timed out. Please try again.']);
                }

                return response()->json(['status' => 'pending', 'message' => 'Waiting for payment confirmation...']);
            }

            $creds = app(SelcomCredentialResolver::class)->resolve();
            $selcom = new SelcomApiService(
                $creds['vendor'],
                $creds['api_key'],
                $creds['api_secret'],
                $creds['live']
            );
            $statusArr = $selcom->orderStatus($selcompay->order_id);

            if (! isset($statusArr['resultcode'])) {
                return response()->json(['status' => 'error', 'message' => 'Unable to verify payment status.']);
            }

            if ($statusArr['resultcode'] !== '000') {
                $errorMessage = $statusArr['message'] ?? $statusArr['result'] ?? 'Unknown error';

                return response()->json(['status' => 'error', 'message' => 'Payment verification failed: '.$errorMessage]);
            }

            $paymentStatus = $statusArr['data'][0]['payment_status'] ?? null;

            if ($paymentStatus === 'COMPLETED') {
                $selcompay->update(['payment_status' => 'completed']);
                $order->update(['payment_status' => 'paid', 'status' => 'processed']);

                $order->load(['items.product.category', 'user']);
                app(\App\Services\NotificationDispatchService::class)->orderPaymentSuccess($order);
                if ($order->user && $order->user->role === 'dealer') {
                    app(DistributionSaleService::class)->createFromOrder($order, 'complete');
                }

                $cart = Cart::where('user_id', $order->user_id)->first();
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

            if (in_array($paymentStatus, ['FAILED', 'CANCELLED', 'EXPIRED', 'REJECTED', 'USERCANCELLED'], true)) {
                $selcompay->update(['payment_status' => 'failed']);
                $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                app(\App\Services\NotificationDispatchService::class)->orderPaymentFailed(
                    $order->fresh(['user']),
                    'Payment '.strtolower($paymentStatus ?? 'failed').'.',
                );

                return response()->json(['status' => 'failed', 'message' => 'Payment '.strtolower($paymentStatus ?? 'failed').'. Please try again.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment is being processed...']);
        } catch (\Throwable $e) {
            Log::error('Shop payment status check error', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Error checking payment status: '.$e->getMessage()]);
        }
    }

    /**
     * @return array{payment_phone: string, transid: string}
     */
    protected function initiateSelcomPayment(Order $order, string $paymentPhone): array
    {
        $order->load('items', 'user');
        $cleanPhone = TanzaniaMobileNumber::normalize($paymentPhone);

        $transid = 'ORDER_'.$order->id.'_'.time();
        $selcomOrderId = 'SELCOM'.now()->timestamp.rand(1000, 9999);

        $creds = app(SelcomCredentialResolver::class)->resolve();
        $webhookUrl = route('selcom.checkout-callback');

        $selcom = new SelcomApiService(
            $creds['vendor'],
            $creds['api_key'],
            $creds['api_secret'],
            $creds['live']
        );

        $createPayload = [
            'vendor' => $creds['vendor'],
            'order_id' => $selcomOrderId,
            'buyer_email' => $order->user->email,
            'buyer_name' => $order->user->name,
            'buyer_phone' => $cleanPhone,
            'amount' => (int) round($order->total_price ?? 0),
            'currency' => 'TZS',
            'redirect_url' => base64_encode(config('app.url')),
            'cancel_url' => base64_encode(config('app.url')),
            'webhook' => base64_encode($webhookUrl),
            'buyer_remarks' => 'Order '.$order->id,
            'merchant_remarks' => '',
            'no_of_items' => (int) $order->items->count(),
            'expiry' => (int) (config('selcom.expiry') ?? 60),
        ];

        $createResponse = $selcom->createOrderMinimal($createPayload);

        if (isset($createResponse['resultcode']) && $createResponse['resultcode'] !== '000') {
            $errorMessage = $createResponse['message'] ?? $createResponse['result'] ?? 'Payment gateway error';
            throw new \RuntimeException('Payment gateway error: '.$errorMessage);
        }

        $walletResponse = $selcom->walletPayment($transid, $selcomOrderId, $cleanPhone);

        if (isset($walletResponse['resultcode']) && $walletResponse['resultcode'] !== '000' && $walletResponse['resultcode'] !== '111') {
            $errorMessage = $walletResponse['message'] ?? $walletResponse['result'] ?? 'Payment request failed';
            throw new \RuntimeException('Payment request failed: '.$errorMessage);
        }

        Selcompay::create([
            'transid' => $transid,
            'order_id' => $selcomOrderId,
            'phone_number' => $cleanPhone,
            'amount' => $createPayload['amount'],
            'payment_status' => 'pending',
            'local_order_id' => $order->id,
            'purpose' => Selcompay::PURPOSE_ORDER_PAYMENT,
        ]);

        return [
            'payment_phone' => $cleanPhone,
            'transid' => $transid,
        ];
    }

    protected function formatCart(Cart $cart): array
    {
        $items = $cart->relationLoaded('items') ? $cart->items : $cart->items()->with('product.category')->get();
        $subtotal = $items->sum(fn ($item) => ($item->product?->price ?? 0) * $item->quantity);

        return [
            'id' => $cart->id,
            'items' => $items->map(function (CartItem $item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => $item->product ? $this->formatProduct($item->product) : null,
                    'line_total' => $item->product ? round($item->product->price * $item->quantity, 2) : 0,
                ];
            })->values(),
            'item_count' => $items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
        ];
    }

    protected function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'type' => $address->type,
            'address' => $address->address,
            'city' => $address->city,
            'state' => $address->state,
            'zip' => $address->zip,
            'country' => $address->country,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
    }

    protected function formatOrder(Order $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_price' => (float) $order->total_price,
            'created_at' => $order->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['address'] = $order->relationLoaded('address') && $order->address
                ? $this->formatAddress($order->address)
                : null;
            $data['items'] = $order->relationLoaded('items')
                ? $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'product' => $item->product ? $this->formatProduct($item->product) : null,
                ])->values()
                : [];
        } else {
            $data['item_count'] = $order->relationLoaded('items') ? $order->items->sum('quantity') : null;
        }

        return $data;
    }
}
