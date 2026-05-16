<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\TeamLeaderRoutes;
use App\Services\DistributionSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (TeamLeaderRoutes::isTeamLeader(Auth::user())) {
            return redirect()->route('team-leader.orders');
        }

        $orders = Order::where('user_id', Auth::id())
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('account.orders.index', compact('orders'));
    }

    public function create()
    {
        $cart = \App\Models\Cart::where('user_id', Auth::id())->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route(TeamLeaderRoutes::cartIndex())->with('error', 'Your cart is empty.');
        }

        $addresses = Auth::user()->addresses;

        return view('checkout.create', compact('cart', 'addresses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cod,selcom',
            'payment_phone' => 'required_if:payment_method,selcom',
        ]);

        $cart = \App\Models\Cart::where('user_id', Auth::id())->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route(TeamLeaderRoutes::cartIndex())->with('error', 'Cart is empty.');
        }

        // Calculate total
        $total = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        // Add Tax
        $tax = $total * 0.18;
        $grandTotal = $total + $tax;
        
        // Create Order
        $order = Order::create([
            'user_id' => Auth::id(),
            'total_price' => $grandTotal,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $request->payment_method,
            'address_id' => $request->address_id,
        ]);

        // Create Order Items
        foreach ($cart->items as $item) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);

            // Decrement Stock (only for COD, Selcom will decrement after successful payment)
            if ($request->payment_method === 'cod') {
                $product = $item->product;
                if ($product->stock_quantity >= $item->quantity) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }
        }

        // Handle based on payment method
        if ($request->payment_method === 'selcom') {
            // Don't clear cart yet - wait for payment confirmation
            // Cart will be cleared in SelcomController after successful payment
            return redirect()->route('selcom.pay', $order->id)->with('payment_phone', $request->payment_phone);
        }

        // For COD, clear cart immediately and decrement stock
        $cart->items()->delete();
        $cart->delete();

        // When dealer pays with cash/COD, create distribution sales with status pending
        if (Auth::user()->role === 'dealer') {
            $order->load(['items.product.category', 'user']);
            app(DistributionSaleService::class)->createFromOrder($order, 'pending');
        }

        return redirect()->route(TeamLeaderRoutes::ordersIndex())->with('success', 'Order placed successfully!');
    }
}
