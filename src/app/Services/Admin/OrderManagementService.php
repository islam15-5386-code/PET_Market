<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderManagementService
{
    /**
     * Valid status transitions map.
     * Keys = current status, values = allowed next statuses.
     */
    private const TRANSITIONS = [
        Order::STATUS_PENDING    => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
        Order::STATUS_CONFIRMED  => [Order::STATUS_PROCESSING, Order::STATUS_CANCELLED],
        Order::STATUS_PROCESSING => [Order::STATUS_SHIPPED],
        Order::STATUS_SHIPPED    => [Order::STATUS_DELIVERED],
        Order::STATUS_DELIVERED  => [],   // terminal
        Order::STATUS_CANCELLED  => [],   // terminal
    ];

    /**
     * Paginated list of all orders with filters.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Order::with('user')->withCount('items');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search by order number or customer name (via join)
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'ilike', "%{$term}%")
                  ->orWhere('shipping_name', 'ilike', "%{$term}%");
            });
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a single order by order number for admin (includes user + items).
     */
    public function findByOrderNumber(string $orderNumber): Order
    {
        return Order::with(['user', 'items'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();
    }

    /**
     * Update order status with transition validation.
     *
     * @throws \Exception
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        $allowed = self::TRANSITIONS[$order->status] ?? [];

        if (empty($allowed)) {
            throw new \Exception(
                "Order status '{$order->status}' is terminal and cannot be changed.",
                422
            );
        }

        if (!in_array($newStatus, $allowed)) {
            $allowedStr = implode(', ', $allowed);
            throw new \Exception(
                "Cannot move order from '{$order->status}' to '{$newStatus}'. "
                . "Allowed transitions: {$allowedStr}.",
                422
            );
        }

        $order->update(['status' => $newStatus]);

        return $order->fresh(['user', 'items']);
    }
}
