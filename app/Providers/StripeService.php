<?php

namespace App\Providers;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
   public function __construct()
   {
      Stripe::setApiKey(config('services.stripe.secret'));
   }

   public function createCheckoutSession($order)
   {
      return Session::create([
         'payment_method_types' => ['card'],
         'line_items' => array_map(function ($item) {
            return [
               'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                     'name' => $item['product_name'],
                  ],
                  'unit_amount' => $item['price'] * 100,
               ],
               'quantity' => $item['quantity'],
            ];
         }, $order['cart_items']),
         'mode' => 'payment',
         'success_url' => 'http://localhost:5173/payment/success?session_id={CHECKOUT_SESSION_ID}&status=success',
         'cancel_url' => 'http://localhost:5173/payment/cancel',
         'metadata' => [
            'order_id' => $order['order_id'],
         ],
      ]);
   }
}
