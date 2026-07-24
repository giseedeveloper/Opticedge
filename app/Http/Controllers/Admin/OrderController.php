<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\PaymentOption;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(50)->withQueryString();

        $orderDashboard = [
            'total_orders' => Order::count(),
            'total_value' => (float) Order::sum('total_price'),
            'pending' => Order::where('status', 'pending')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'orderDashboard'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'address', 'paymentOption']);
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get();
        return view('admin.orders.show', compact('order', 'paymentOptions'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processed,on the way,delivered,cancelled',
            'payment_option_id' => 'nullable|exists:payment_options,id',
        ]);

        $oldOptionId = $order->payment_option_id;
        $newOptionId = $validated['payment_option_id'] ?? null;
        $amount = (float) ($order->total_price ?? 0);

        if ($amount > 0) {
            // If channel changed, adjust balances
            if ($oldOptionId && $oldOptionId != $newOptionId) {
                $oldOption = PaymentOption::find($oldOptionId);
                if ($oldOption) {
                    $oldOption->decrement('balance', $amount);
                }
            }

            if ($newOptionId) {
                $newOption = PaymentOption::find($newOptionId);
                if ($newOption) {
                    $newOption->increment('balance', $amount);
                }
            } elseif ($oldOptionId && !$newOptionId) {
                // Channel removed: remove amount from previous channel
                $oldOption = PaymentOption::find($oldOptionId);
                if ($oldOption) {
                    $oldOption->decrement('balance', $amount);
                }
            }
        }

        $order->update([
            'status' => $validated['status'],
            'payment_option_id' => $newOptionId,
        ]);

        return redirect()->back()->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order)
    {
        $amount = (float) ($order->total_price ?? 0);
        $optionId = $order->payment_option_id;

        if ($amount > 0 && $optionId) {
            $option = PaymentOption::find($optionId);
            if ($option) {
                $option->decrement('balance', $amount);
            }
        }

        $order->items()->delete();
        $order->delete();

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Order deleted successfully.');
    }
}
