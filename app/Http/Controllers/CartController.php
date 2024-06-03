<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\ProductResource;


class CartController extends Controller
{
    //* Adding Cart 

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Find or create a cart for the current user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Find the cart item or create a new one
        $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

        if ($cartItem) {
            // Update the quantity by adding the new quantity
            $cartItem->quantity = $validated['quantity']; // Update later
            $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
            $cartItem->save();
        } else {
            // Create a new cart item
            $product = Products::findOrFail($validated['product_id']);
            $cartItem = $cart->items()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_price' => $validated['quantity'] * $product->price
            ]);
        }

        // Calculate the total price of the cart
        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json([
            'cartItem' => $cartItem,
            'totalCartPrice' => $totalCartPrice
        ], 201);
    }
    public function viewCart()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch the cart with items for the authenticated user, including brand, category, and discount details
        $cart = Cart::with(['items.product.brand', 'items.product.category', 'items.product.discounts'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Transform the products using the ProductResource and apply discounts
        $transformedItems = $cart->items->map(function ($item) {
            $product = $item->product;
            $finalPrice = (float) $product->price;

            // Apply discount if available
            $discount = $product->discounts->first();
            if ($discount && isset($discount->percentage) && $discount->percentage > 0) {
                $discountPercentage = (float) $discount->percentage;
                $finalPrice = $finalPrice * ((100 - $discountPercentage) / 100);
            }

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'total_price' => (float) ($finalPrice * $item->quantity), // Cast to float
                'product' => new ProductResource($product),
                'final_price' => (float) $finalPrice, // Cast to float
            ];
        });

        // Calculate the total price of the cart after applying discounts
        $totalCartPrice = $transformedItems->sum('total_price');

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'user_id' => $cart->user_id,
                'items' => $transformedItems,
                'totalCartPrice' => (float) $totalCartPrice, // Cast to float
            ],
        ]);
    }


    public function updateQuantityByProductId(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch the cart for the authenticated user
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        // Find the cart item by product ID
        $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        // Update the quantity and total price of the cart item
        $cartItem->quantity = $validated['quantity'];
        $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
        $cartItem->save();

        // Recalculate the total price of the cart
        $totalCartPrice = $cart->items->sum('total_price');

        // Transform the updated items using the ProductResource
        $transformedItems = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'total_price' => $item->total_price,
                'product' => new ProductResource($item->product)
            ];
        });

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'user_id' => $cart->user_id,
                'items' => $transformedItems,
                'totalCartPrice' => $totalCartPrice
            ],
        ]);
    }

    public function removeFromCart($product_id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch the cart for the authenticated user
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        // Find the cart item by product ID
        $cartItem = $cart->items()->where('product_id', $product_id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        // Delete the cart item
        $cartItem->delete();

        // Recalculate the total price of the cart
        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json(['message' => 'Item removed from cart', 'totalCartPrice' => $totalCartPrice], 200);
    }
}
