<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\user\OrderResource;
use Illuminate\Http\Request;
use App\Models\user\Order;
use App\Models\user\OrderItem;
use App\Models\user\Cart;
use App\Models\user\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $stats = [
            'total' => Order::where('user_id', $userId)->count(),
            'processing' => Order::where('user_id', $userId)->where('status', 'processing')->count(),
            'shipped' => Order::where('user_id', $userId)->where('status', 'shipped')->count(),
            'delivered' => Order::where('user_id', $userId)->where('status', 'delivered')->count(),
            'canceled' => Order::where('user_id', $userId)->where('status', 'canceled')->count(),
            'returned' => Order::where('user_id', $userId)->where('status', 'returned')->count(),
            "return_requested" => Order::where('user_id', $userId)->where('return_status', 'processing')->count(),
            "return_pending" => Order::where('user_id', $userId)->where('return_status', 'waiting')->count(),
            "return_rejected" => Order::where('user_id', $userId)->where('return_status', 'rejected')->count(),
        ];

        $orders = Order::where('user_id', $userId)
            ->with('orderItems.product', 'paymentMethod')
            ->orderBy('created_at', 'desc')
            ->paginate(12); // 12 طلبات في الصفحة

        return response()->json([
            'message' => $orders->isEmpty() ? 'No orders found' : 'Orders retrieved successfully',
            'stats' => $stats,
            'orders' => OrderResource::collection($orders),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $order = Order::with('orderItems.product', 'paymentMethod')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order' => new OrderResource($order)
        ]);
    }


    public function store(OrderRequest $request)
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->with('cartItems.product')
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        DB::beginTransaction();

        try {
            $subtotal = $cart->cartItems->sum(
                fn($item) =>
                $item->quantity * $item->product->price
            );

            $order = Order::create([
                'user_id' => auth()->id(),
                'name' => $request->name,
                'phone' => $request->phone,
                'city' => $request->city,
                'email' => $request->email,
                'address' => $request->address,
                'total_price' => $subtotal,
                'status' => 'processing'
            ]);

            PaymentMethod::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'receipt_image' => $request->receipt_image ?? null,
                'status' => 'processing',
            ]);

            foreach ($cart->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->quantity * $item->product->price,
                    'color_id' => $item->color_id,
                ]);
            }

            $cart->status = 'closed';
            $cart->save();

            DB::commit();

            $order->load('orderItems.product', 'paymentMethod');

            return response()->json([
                'order' => new OrderResource($order),
                'message' => 'Order created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create order',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again'
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (!in_array($order->status, ['processing'])) {
            return response()->json([
                'message' => 'Order cannot be canceled at this stage.'
            ], 422);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $order->update([
            'status' => 'canceled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return response()->json([
            'message' => 'Order canceled successfully',
            'order'   => new OrderResource($order->refresh())
        ]);
    }

    public function return(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== 'delivered') {
            return response()->json([
                'message' => 'Order cannot be returned at this stage.'
            ], 422);
        }

        // تحقق من وجود return request مفتوح
        if (in_array($order->return_status, ['processing', 'waiting', 'returned'])) {
            return response()->json([
                'message' => 'Return request already exists for this order.'
            ], 422);
        }

        $request->validate([
            'return_reason' => 'required|string|max:255',
        ]);

        $order->update([
            'return_reason' => $request->return_reason,
            'return_status' => 'processing',
            'return_requested_at' => now(), // ✅ ده صح
        ]);

        return response()->json([
            'message' => 'Return request submitted successfully, pending approval.',
            'order' => new OrderResource($order->refresh())
        ]);
    }
}
