<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\user\Order;
use App\Http\Resources\admin\OrderResource;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderItems.product', 'paymentMethod')
            ->orderBy('id', 'desc')
            ->paginate(12);

        $stats = [
            'total' => Order::count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'canceled' => Order::where('status', 'canceled')->count(),
            'returned' => Order::where('status', 'returned')->count(),
            'return_requested' => Order::where('return_status', 'processing')->count(),
            'return_pending' => Order::where('return_status', 'waiting')->count(),
            'return_rejected' => Order::where('return_status', 'rejected')->count(),
        ];

        return response()->json([
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



    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 🔒 منع تغيير حالة order لو هي بالفعل returned
        if ($order->status === 'returned') {
            return response()->json([
                'message' => 'Returned orders cannot be updated to another status.'
            ], 422);
        }

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,canceled',
            'cancellation_reason' => 'required_if:status,canceled|string|max:500'
        ]);

        $newStatus = $request->status;

        switch ($newStatus) {
            case 'canceled':
                if ($order->status !== 'processing') {
                    return response()->json([
                        'message' => 'Orders can only be canceled if they are in processing status.'
                    ], 422);
                }

                $order->update([
                    'status' => 'canceled',
                    'cancellation_reason' => $request->cancellation_reason
                ]);
                break;

            default:
                $order->update([
                    'status' => $newStatus,
                    'cancellation_reason' => null
                ]);
                break;
        }

        return response()->json([
            'order' => new OrderResource($order->refresh()->load('orderItems.product', 'paymentMethod')),
            'message' => "Order updated successfully by admin"
        ]);
    }


    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully by admin'
        ]);
    }
    public function handleReturnRequest(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // التعامل مع الحالات المختلفة
        if ($order->return_status === 'waiting') {
            // تأكيد استلام المنتج
            $request->validate([
                'confirm_return' => 'required|boolean'
            ]);

            if ($request->confirm_return) {
                $order->update([
                    'return_status' => 'returned',
                    'status' => 'returned',
                    'return_handled_at' => now()
                ]);
                $message = 'Return confirmed and product received successfully.';
            } else {
                return response()->json(['message' => 'Product receipt must be confirmed.'], 422);
            }
        } elseif ($order->return_status === 'processing') {
            // معالجة الطلب الأولي
            $request->validate([
                'return_status' => 'required|in:approved,rejected',
                'return_reason' => 'required_if:return_status,rejected|string|max:500',
                'confirm_return' => 'sometimes|boolean'
            ]);

            if ($request->return_status === 'approved') {
                if ($request->confirm_return === true) {
                    // موافقة + تأكيد مباشر
                    $order->update([
                        'return_status' => 'returned',
                        'status' => 'returned',
                        'return_handled_at' => now()
                    ]);
                    $message = 'Return approved and completed successfully.';
                } else {
                    // موافقة فقط
                    $order->update([
                        'return_status' => 'waiting',
                        'return_handled_at' => now()
                    ]);
                    $message = 'Return approved. Waiting for product receipt.';
                }
            } else {
                // رفض
                $order->update([
                    'return_status' => 'rejected',
                    'return_reason' => $request->return_reason,
                    'return_handled_at' => now()
                ]);
                $message = 'Return request rejected successfully.';
            }
        } else {
            return response()->json([
                'message' => 'No valid return request found for this order.'
            ], 422);
        }

        return response()->json([
            'message' => $message,
            'order' => new OrderResource($order->fresh()->load('orderItems.product', 'paymentMethod'))
        ]);
    }
}



// public function handleReturnRequest(Request $request, Order $order)
// {
//     if (auth()->user()->role !== 'admin') {
//         return response()->json(['message' => 'Unauthorized'], 403);
//     }

//     if ($order->return_status === 'approved') {
//         $request->validate([
//             'confirm_return' => 'required|boolean'
//         ]);

//         if ($request->confirm_return) {
//             $order->return_status = 'returned';
//             $order->status = 'returned';
//         }

//     } else {
//         if ($order->return_status !== 'processing') {
//             return response()->json([
//                 'message' => 'No processing return request found for this order.'
//             ], 422);
//         }

//         $request->validate([
//             'return_status' => 'required|in:approved,rejected',
//             'return_reason' => 'required_if:return_status,rejected|string|max:500',
//             'confirm_return' => 'sometimes|boolean'
//         ]);

//         if ($request->return_status === 'approved') {
//             if (!$request->has('confirm_return') || !$request->confirm_return) {
//                 $order->return_status = 'waiting';
//             } else {
//                 $order->return_status = 'returned';
//                 $order->status = 'returned';
//             }
//         } elseif ($request->return_status === 'rejected') {
//             $order->return_status = 'rejected';
//             $order->return_reason = $request->return_reason;
//         }
//     }

//     $order->save();

//     return response()->json([
//         'order' => new OrderResource($order->refresh()->load('orderItems.product', 'paymentMethod')),
//         'message' => "Return request {$order->return_status} successfully handled by admin"
//     ]);
// }
