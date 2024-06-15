<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;



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
    public function showByUserId($userId)
    {
        // Retrieve orders by user ID with their associated order items and products
        $orders = Order::where('user_id', $userId)
            ->with(['orderItems.product', 'orderItems.product.brand', 'orderItems.product.category'])
            ->get();

        return response()->json([
            "orders" =>  $orders
        ]);
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




    //* Order Period 
    public function getTotalOrdersByPeriod(Request $request)
    {
        // Validate the period parameter
        $validatedData = $request->validate([
            'period' => 'required|in:day,month,year'
        ]);

        $period = $validatedData['period'];

        switch ($period) {
            case 'day':
                $orders = Order::select(
                    DB::raw('DATE(created_at) as period'),
                    DB::raw('DAY(created_at) as day'),
                    DB::raw('count(*) as total')
                )
                    ->groupBy(DB::raw('DATE(created_at)'), DB::raw('DAY(created_at)'))
                    ->get();
                break;
            case 'month':
                $orders = Order::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('count(*) as total')
                )
                    ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), DB::raw('MONTH(created_at)'))
                    ->get();
                break;
            case 'year':
                $orders = Order::select(
                    DB::raw('YEAR(created_at) as period'),
                    DB::raw('count(*) as total')
                )
                    ->groupBy(DB::raw('YEAR(created_at)'))
                    ->get();
                break;
            default:
                return response()->json(['error' => 'Invalid period'], 400);
        }

        return response()->json(['orders_period' => $orders]);
    }

    public function getTrendingProducts()
    {
        $trendingProducts = OrderItem::select('product_id', DB::raw('count(*) as order_count'))
            ->groupBy('product_id')
            ->havingRaw('count(*) >= 5')
            ->with('product')
            ->orderBy('order_count', 'desc')
            ->get();

        return response()->json(['trending_products' => $trendingProducts]);
    }
}
