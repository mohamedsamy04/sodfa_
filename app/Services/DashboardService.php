<?php

namespace App\Services;

use App\Models\User;
use App\Models\user\Order;
use App\Models\user\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats()
    {
        $today      = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $yearStart  = Carbon::now()->startOfYear();

        // ================= Users =================
        $totalUsers  = User::count();
        $usersToday  = User::whereDate('created_at', $today)->count();
        $usersMonth  = User::whereBetween('created_at', [$monthStart, Carbon::now()])->count();
        $usersYear   = User::whereBetween('created_at', [$yearStart, Carbon::now()])->count();

        // ================= Orders =================
        $totalOrders  = Order::count();
        $ordersToday  = Order::whereDate('created_at', $today)->count();
        $ordersMonth  = Order::whereBetween('created_at', [$monthStart, Carbon::now()])->count();
        $ordersYear   = Order::whereBetween('created_at', [$yearStart, Carbon::now()])->count();

        // Processing orders
        $processingOrdersToday = Order::where('status', 'processing')->whereDate('created_at', $today)->count();
        $processingOrdersMonth = Order::where('status', 'processing')->whereBetween('created_at', [$monthStart, Carbon::now()])->count();
        $processingOrdersYear  = Order::where('status', 'processing')->whereBetween('created_at', [$yearStart, Carbon::now()])->count();

        // ================= Revenue =================
        $totalRevenue  = Order::where('status', 'delivered')->sum('total_price');
        $revenueToday  = Order::where('status', 'delivered')->whereDate('created_at', $today)->sum('total_price');
        $revenueMonth  = Order::where('status', 'delivered')->whereBetween('created_at', [$monthStart, Carbon::now()])->sum('total_price');
        $revenueYear   = Order::where('status', 'delivered')->whereBetween('created_at', [$yearStart, Carbon::now()])->sum('total_price');

        // ================= Best 3 Selling =================
        $best3Today = $this->bestSelling($today, $today);
        $best3Month = $this->bestSelling($monthStart, Carbon::now());
        $best3Year  = $this->bestSelling($yearStart, Carbon::now());

        return [
            'users' => [
                'total' => $totalUsers,
                'today' => $usersToday,
                'month' => $usersMonth,
                'year'  => $usersYear,
            ],
            'orders' => [
                'total' => $totalOrders,
                'today' => $ordersToday,
                'month' => $ordersMonth,
                'year'  => $ordersYear,
                'processing' => [
                    'today' => $processingOrdersToday,
                    'month' => $processingOrdersMonth,
                    'year'  => $processingOrdersYear,
                ],
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'today' => $revenueToday,
                'month' => $revenueMonth,
                'year'  => $revenueYear,
            ],
            'best_selling' => [
                'today' => $best3Today,
                'month' => $best3Month,
                'year'  => $best3Year,
            ]
        ];
    }

    private function bestSelling($startDate, $endDate)
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'delivered')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('order_items.product_id', 'products.id', 'products.name', 'products.price')
            ->select([
                'order_items.product_id as id',
                'products.name as name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue'),
                'products.price as current_price'
            ])
            ->orderByDesc('total_quantity')
            ->limit(3)
            ->get();
    }
}
