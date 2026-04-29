<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Services\Admin\OrderManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly OrderManagementService $orderService
    ) {}

    // ── GET /api/admin/orders ─────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'    => ['sometimes', 'nullable', 'in:pending,confirmed,processing,shipped,delivered,cancelled'],
            'search'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to'   => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = $this->orderService->list($request->all());

        return response()->json([
            'success' => true,
            'data'    => [
                'orders' => AdminOrderResource::collection($orders),
                'meta'   => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                ],
            ],
        ]);
    }

    // ── GET /api/admin/orders/{orderNumber} ───────────────────────────────────

    public function show(string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->findByOrderNumber($orderNumber);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['order' => new AdminOrderResource($order)],
        ]);
    }

    // ── PUT /api/admin/orders/{orderNumber}/status ────────────────────────────

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'status' => [
                'required',
                'in:pending,confirmed,processing,shipped,delivered,cancelled',
            ],
        ]);

        try {
            $order = $this->orderService->findByOrderNumber($orderNumber);
            $order = $this->orderService->updateStatus($order, $request->status);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Order status updated to '{$order->status}'.",
            'data'    => ['order' => new AdminOrderResource($order)],
        ]);
    }
}
