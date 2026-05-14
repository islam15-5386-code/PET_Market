<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Return the full dashboard snapshot — stats, breakdowns, recent activity.
     */
    public function getSnapshot(): array
    {
        return [
            'stats'                  => $this->getCoreStats(),
            'order_status_breakdown' => $this->getOrderStatusBreakdown(),
            'recent_orders'          => $this->getRecentOrders(),
            'recent_users'           => $this->getRecentUsers(),
            'low_stock_alerts'       => $this->getLowStockAlerts(),
        ];
    }

    /**
     * Daily revenue aggregated over the last 30 days — ready for chart rendering.
     */
    public function getRevenueChart(): array
    {
        $rows = DB::table('orders')
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(total_amount) as total"),
                DB::raw("COUNT(*) as orders")
            )
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        $daily = $rows->keyBy('date');
        $revenue = [];
        $grandTotal = 0.0;

        // Always return a full 30-day timeline (including zero-revenue days)
        // so the frontend chart can render a stable dynamic graph.
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $row = $daily->get($day);
            $total = (float) ($row->total ?? 0);
            $orders = (int) ($row->orders ?? 0);
            $grandTotal += $total;

            $revenue[] = [
                'date'   => $day,
                'total'  => number_format($total, 2, '.', ''),
                'orders' => $orders,
            ];
        }

        return [
            'revenue'     => $revenue,
            'period'      => 'last_30_days',
            'grand_total' => number_format((float) $grandTotal, 2, '.', ''),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getCoreStats(): array
    {
        $totalRevenue   = Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total_amount');
        $revenueToday   = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->whereDate('created_at', today())
            ->sum('total_amount');

        return [
            'total_users'        => User::where('role', 'user')->count(),
            'total_products'     => Product::count(),
            'total_orders'       => Order::count(),
            'total_revenue'      => number_format((float) $totalRevenue, 2, '.', ''),
            'orders_today'       => Order::whereDate('created_at', today())->count(),
            'revenue_today'      => number_format((float) $revenueToday, 2, '.', ''),
            'pending_orders'     => Order::where('status', Order::STATUS_PENDING)->count(),
            'low_stock_products' => Product::where('stock_quantity', '<=', 5)
                ->where('is_available', true)
                ->count(),
        ];
    }

    private function getOrderStatusBreakdown(): array
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        $counts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses present even if count is 0
        $breakdown = [];
        foreach ($statuses as $status) {
            $breakdown[$status] = (int) ($counts[$status] ?? 0);
        }

        return $breakdown;
    }

    private function getRecentOrders(): array
    {
        return Order::with('user')
            ->withCount('items')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($order) => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'status'       => $order->status,
                'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
                'items_count'  => $order->items_count,
                'customer'     => $order->user?->name,
                'created_at'   => $order->created_at?->toISOString(),
            ])
            ->all();
    }

    private function getRecentUsers(): array
    {
        return User::where('role', 'user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at?->toISOString(),
            ])
            ->all();
    }

    private function getLowStockAlerts(): array
    {
        return Product::where('stock_quantity', '<=', 5)
            ->where('is_available', true)
            ->orderBy('stock_quantity')
            ->take(10)
            ->get(['id', 'name', 'stock_quantity', 'is_available', 'category_id'])
            ->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'stock_quantity' => $p->stock_quantity,
                'is_available'   => $p->is_available,
            ])
            ->all();
    }
}
