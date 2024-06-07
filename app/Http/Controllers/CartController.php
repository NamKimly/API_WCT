<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\ProductResource;

class CartController extends Controller
{
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

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

        if ($cartItem) {
            $cartItem->quantity = $validated['quantity'];
            $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
            $cartItem->save();
        } else {
            $product = Products::findOrFail($validated['product_id']);
            $cartItem = $cart->items()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_price' => $validated['quantity'] * $product->price
            ]);
        }

        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json([
            'cartItem' => $cartItem,
            'totalCartPrice' => $totalCartPrice
        ], 201);
    }

    public function addPromotionToCart(Request $request)
    {
        $validated = $request->validate([
            'promotion_id' => 'required|exists:promotions,id'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $promotion = Promotion::with('products')->findOrFail($validated['promotion_id']);

        foreach ($promotion->products as $product) {
            $cartItem = $cart->items()->where('product_id', $product->id)->first();
            if (!$cartItem) {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'total_price' => $product->pivot->is_free ? 0 : $product->price,
                    'is_promotion' => true,
                    'is_free' => $product->pivot->is_free
                ]);
            }
        }

        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json([
            'message' => 'Promotion added to cart successfully',
            'promotion' => $promotion,
            'totalCartPrice' => $totalCartPrice
        ], 201);
    }


    public function viewCart()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $cart = Cart::with(['items.product.brand', 'items.product.category', 'items.product.discounts', 'items.product.promotions'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $transformedItems = $cart->items->map(function ($item) {
            $product = $item->product;
            $finalPrice = (float) $product->price;

            // If the item is marked as free, set the final price to 0
            if ($item->is_free) {
                $finalPrice = 0;
            } else {
                // Apply discounts if available
                $discount = $product->discounts->first();
                if ($discount && isset($discount->percentage) && $discount->percentage > 0) {
                    $discountPercentage = (float) $discount->percentage;
                    $finalPrice = $finalPrice * ((100 - $discountPercentage) / 100);
                }
            }

            // Check if the product is part of a promotion and marked as free
            $promotionIsFree = $product->promotions->first() ? $product->promotions->first()->pivot->is_free : false;

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'total_price' => (float) ($finalPrice * $item->quantity),
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ],
                    'brand' => [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'logo_url' => $product->brand->logo_url,
                    ],
                    'price' => (float) $product->price,
                    'images' => $product->images,
                    'description' => $product->description,
                    'is_new_arrival' => $product->is_new_arrival,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'promotions' => $product->promotions->map(function ($promotion) use ($product) {
                        return [
                            'promotion_id' => $promotion->id,
                            'product_id' => $product->id,
                            'is_free' => $promotion->pivot->is_free,
                        ];
                    }),
                ],
                'final_price' => $promotionIsFree ? 0 : (float) $finalPrice,
            ];
        });

        // Calculate the total cart price excluding items marked as free
        $totalCartPrice = $transformedItems->where('is_free', false)->sum('final_price');

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'user_id' => $cart->user_id,
                'items' => $transformedItems,
                'totalCartPrice' => (float) $totalCartPrice,
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

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->quantity = $validated['quantity'];
        $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
        $cartItem->save();

        $totalCartPrice = $cart->items->sum('total_price');

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

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItem = $cart->items()->where('product_id', $product_id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->delete();

        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json(['message' => 'Item removed from cart', 'totalCartPrice' => $totalCartPrice], 200);
    }
}
