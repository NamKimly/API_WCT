<?php

namespace App\Http\Controllers;

use App\Providers\StripeService;
use Illuminate\Http\Request;
use App\Models\Order;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'cart_items' => 'required|array',
            'cart_items.*.product_id' => 'required|integer|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.price' => 'required|numeric|min:0',
        ]);

        // Get the authenticated user
        $user = JWTAuth::parseToken()->authenticate();

        // Create the order first
        $order = Order::create([
            'user_id' => $user->id,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'status' => 'pending',
            'total' => array_reduce($request->cart_items, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0),
        ]);

        // Add order items
        foreach ($request->cart_items as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        try {
            $session = $this->stripeService->createCheckoutSession(array_merge($request->all(), ['order_id' => $order->id]));

            return response()->json(['url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
