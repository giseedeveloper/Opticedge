<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user:id,name,email')
            ->latest()
            ->take(100)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'customer_name' => $order->user?->name ?? 'Guest',
                    'email' => $order->user?->email,
                    'status' => $order->status,
                    'total_price' => (float) $order->total_price,
                    'created_at' => $order->created_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $orders]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user:id,name,email,role', 'items.product:id,name', 'address', 'paymentOption:id,name']);

        $items = $order->items->map(function ($item) {
            $qty = (int) $item->quantity;
            $unit = (float) $item->price;

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown',
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $unit * $qty,
            ];
        })->values();

        $address = null;
        if ($order->address) {
            $a = $order->address;
            $address = [
                'type' => $a->type,
                'address' => $a->address,
                'city' => $a->city,
                'state' => $a->state,
                'zip' => $a->zip,
                'country' => $a->country,
                'latitude' => $a->latitude,
                'longitude' => $a->longitude,
            ];
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'total_price' => (float) $order->total_price,
                'created_at' => $order->created_at?->toISOString(),
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'shipping_address' => $order->shipping_address,
                'customer' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'role' => $order->user->role ?? 'customer',
                ] : null,
                'payment_option_id' => $order->payment_option_id,
                'payment_channel' => $order->paymentOption?->name,
                'address' => $address,
                'items' => $items,
            ],
        ]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processed,on the way,delivered,cancelled',
            'payment_option_id' => 'nullable|exists:payment_options,id',
        ]);

        $oldOptionId = $order->payment_option_id;
        $newOptionId = $validated['payment_option_id'] ?? null;
        $amount = (float) ($order->total_price ?? 0);

        if ($amount > 0) {
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
            } elseif ($oldOptionId && ! $newOptionId) {
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

        return $this->show($order->fresh(['user', 'items.product', 'address', 'paymentOption']));
    }
}
