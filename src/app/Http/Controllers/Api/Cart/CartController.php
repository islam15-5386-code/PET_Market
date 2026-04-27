<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartItemResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    // ── GET /api/cart ─────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $cartData = $this->cartService->getCart($request->user());

        return response()->json([
            'success' => true,
            'data'    => [
                'cart' => [
                    'items'   => CartItemResource::collection($cartData['items']),
                    'summary' => $cartData['summary'],
                ],
            ],
        ]);
    }

    // ── POST /api/cart ────────────────────────────────────────────────────────

    public function add(AddToCartRequest $request): JsonResponse
    {
        try {
            $cartItem = $this->cartService->add(
                $request->user(),
                $request->product_id,
                $request->quantity,
            );

            // Return the full cart so the frontend can update all totals at once
            $cartData = $this->cartService->getCart($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart.',
                'data'    => [
                    'added_item' => new CartItemResource($cartItem),
                    'cart'       => [
                        'items'   => CartItemResource::collection($cartData['items']),
                        'summary' => $cartData['summary'],
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    // ── PUT /api/cart/{productId} ─────────────────────────────────────────────

    public function update(UpdateCartRequest $request, int $productId): JsonResponse
    {
        try {
            $cartItem = $this->cartService->update(
                $request->user(),
                $productId,
                $request->quantity,
            );

            $cartData = $this->cartService->getCart($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Cart updated.',
                'data'    => [
                    'updated_item' => new CartItemResource($cartItem),
                    'cart'         => [
                        'items'   => CartItemResource::collection($cartData['items']),
                        'summary' => $cartData['summary'],
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

    // ── DELETE /api/cart/{productId} ──────────────────────────────────────────

    public function remove(Request $request, int $productId): JsonResponse
    {
        try {
            $this->cartService->remove($request->user(), $productId);

            $cartData = $this->cartService->getCart($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'data'    => [
                    'cart' => [
                        'items'   => CartItemResource::collection($cartData['items']),
                        'summary' => $cartData['summary'],
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

    // ── DELETE /api/cart ──────────────────────────────────────────────────────

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared.',
            'data'    => [
                'cart' => [
                    'items'   => [],
                    'summary' => [
                        'items_count'    => 0,
                        'total_quantity' => 0,
                        'subtotal'       => '0.00',
                        'shipping_fee'   => '0.00',
                        'total'          => '0.00',
                    ],
                ],
            ],
        ]);
    }
}
