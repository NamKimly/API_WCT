<?php

namespace App\Http\Controllers;

use App\Providers\StripeService;
use Illuminate\Http\Request;

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

        $order = $request->all();

        try {
            $session = $this->stripeService->createCheckoutSession($order);

            return response()->json(['url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
