<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    // ── POST /api/orders/preview ──────────────────────────────────────────────
    // Returns the full bill breakdown without placing the order.
    // Powers the confirmation screen before the user clicks "Confirm Order".

    public function preview(CheckoutRequest $request): JsonResponse
    {
        try {
            $payload = $this->orderService->preview(
                $request->user(),
                $request->validated()
            );

            // Strip the internal _product key before serialising
            $items = array_map(fn ($line) => [
                'product_id'    => $line['product_id'],
                'product_name'  => $line['product_name'],
                'product_image' => $this->resolveUrl($line['product_image']),
                'quantity'      => $line['quantity'],
                'unit_price'    => number_format($line['unit_price'], 2, '.', ''),
                'total_price'   => number_format($line['total_price'], 2, '.', ''),
            ], $payload['line_items']);

            return response()->json([
                'success' => true,
                'data'    => [
                    'preview' => [
                        'items'        => $items,
                        'subtotal'     => number_format($payload['subtotal'], 2, '.', ''),
                        'shipping_fee' => number_format($payload['shipping_fee'], 2, '.', ''),
                        'total_amount' => number_format($payload['total_amount'], 2, '.', ''),
                        'shipping'     => $payload['shipping'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    // ── POST /api/orders ──────────────────────────────────────────────────────

    public function store(CheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->checkout(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data'    => ['order' => new OrderResource($order)],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    // ── GET /api/orders/{orderNumber} ─────────────────────────────────────────

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->getForUser($request->user(), $orderNumber);

            return response()->json([
                'success' => true,
                'data'    => ['order' => new OrderResource($order)],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 404);
        }
    }

    // ── POST /api/orders/{orderNumber}/cancel ─────────────────────────────────

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->cancel($request->user(), $orderNumber);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data'    => ['order' => new OrderResource($order)],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function resolveUrl(?string $path): ?string
    {
        if (!$path) return null;
        return str_starts_with($path, 'http') ? $path : asset('storage/' . $path);
    }
}
