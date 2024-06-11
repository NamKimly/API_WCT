<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Tymon\JWTAuth\Facades\JWTAuth;


class OrderController extends Controller
{

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function index()
    {
        // Retrieve all pending orders with order items and product details
        $pendingOrders = Order::with(['orderItems.product', 'user'])
            ->where('status', self::STATUS_PENDING)
            ->get();

        return response()->json([
            'message' => 'Success',
            'orders' => $pendingOrders
        ]);
    }

    public function filterStatus(Request $request)
    {
        // Get the status from the query parameter, defaulting to 'pending'
        $status = $request->query('status', self::STATUS_PENDING);

        // Validate the status parameter
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED])) {
            return response()->json(['message' => 'Invalid status'], 400);
        }

        // Retrieve orders with the specified status, including order items and product details
        $orders = Order::with(['orderItems.product', 'user'])
            ->where('status', $status)
            ->get();

        return response()->json([
            'message' => 'Success',
            'orders' => $orders
        ]);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'cart_items' => 'required|array',
            'cart_items.*.product_id' => 'required|integer|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.price' => 'required|numeric|min:0',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $order = Order::create([
            'user_id' => $user->id,
            'total' => array_sum(array_map(function ($item) {
                return $item['price'] * $item['quantity'];
            }, $validatedData['cart_items'])),
            'status' => self::STATUS_PENDING,
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'address' => $validatedData['address'],
        ]);

        foreach ($validatedData['cart_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        return response()->json(['order' => $order], 201);
    }

    public function show($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return response()->json($order);
    }

    public function updateStatus($id, $status)
    {
        $order = Order::findOrFail($id);

        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED])) {
            return response()->json(['message' => 'Invalid status'], 400);
        }

        $order->status = $status;
        $order->save();

        return response()->json(['message' => "Order $status successfully"]);
    }

    public function approve($id)
    {
        return $this->updateStatus($id, self::STATUS_APPROVED);
    }

    public function reject($id)
    {
        return $this->updateStatus($id, self::STATUS_REJECTED);
    }
}
