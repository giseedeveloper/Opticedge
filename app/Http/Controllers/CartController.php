<?php

namespace App\Http\Controllers;

use App\Support\TeamLeaderRoutes;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        if (Auth::check() && TeamLeaderRoutes::isTeamLeader(Auth::user())) {
            return redirect()->route('team-leader.cart');
        }

        $cart = null;
        if (Auth::check()) {
            $cart = Cart::with(['items.product'])->firstOrCreate(
                ['user_id' => Auth::id()]
            );
        }
        
        return view('public.cart.index', compact('cart'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:models,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to add items to cart.');
        }

        $cart = Cart::firstOrCreate(
            ['user_id' => Auth::id()]
        );

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::findOrFail($id);
        // Ensure the cart item belongs to the user's cart
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return redirect()->back()->with('success', 'Cart updated!');
    }

    public function destroy($id)
    {
        $cartItem = CartItem::findOrFail($id);
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }
        $cartItem->delete();

        return redirect()->back()->with('success', 'Item removed from cart!');
    }
}
