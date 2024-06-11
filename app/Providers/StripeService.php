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
                     'name' => $item['product_name'], // Ensure this field exists in your order items
                  ],
                  'unit_amount' => $item['price'] * 100, // Convert to cents
               ],
               'quantity' => $item['quantity'],
            ];
         }, $order['cart_items']),
         'mode' => 'payment',
         'success_url' => 'http://localhost:5173/payment/success',
         'cancel_url' => 'http://localhost:5173/payment/cancel',
      ]);
   }
}
