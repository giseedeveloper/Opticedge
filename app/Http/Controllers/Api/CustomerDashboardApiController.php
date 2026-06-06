<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardApiController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $recentOrders = Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'business_name' => $user->business_name,
                ],
                'stats' => [
                    'orders_total' => Order::where('user_id', $user->id)->count(),
                    'orders_pending' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
                    'addresses_total' => $user->addresses()->count(),
                ],
                'recent_orders' => $recentOrders->map(fn (Order $o) => [
                    'id' => $o->id,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'total_price' => (float) $o->total_price,
                    'created_at' => $o->created_at?->toIso8601String(),
                    'item_count' => $o->items->sum('quantity'),
                ])->values(),
            ],
        ]);
    }
}
