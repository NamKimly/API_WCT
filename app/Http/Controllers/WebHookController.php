<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Cart;
use App\Models\CartItems;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                // Update the order status
                $this->updateOrderStatus($session);
                break;

            default:
                Log::info('Received unknown event type ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    protected function updateOrderStatus($session)
    {
        // Retrieve the order ID from the session metadata
        $orderId = $session->metadata->order_id;

        // Find the order and update its status
        $order = Order::find($orderId);
        if ($order) {
            $order->status = 'approved'; // Update the status as needed
            $order->save();

            // Clear the user's cart
            $this->clearUserCart($order->user_id);
        }
    }

    protected function clearUserCart($userId)
    {
        // Find the user's cart
        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            // Delete all items in the cart
            CartItems::where('cart_id', $cart->id)->delete();

            // Optionally delete the cart itself
            $cart->delete();
        }
    }
}
