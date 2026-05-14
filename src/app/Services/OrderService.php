<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductImageService $productImageService
    ) {}

    /**
     * Build the full order payload from the current cart.
     * Does NOT write anything to the database.
     * Used by the billing confirmation page before the user clicks "Confirm Order".
     *
     * @throws \Exception
     */
    public function preview(User $user, array $shipping): array
    {
        $cartData = $this->cartService->getCart($user);

        $this->assertCartNotEmpty($cartData['items']);
        $this->assertStockForAll($cartData['items']);

        return $this->buildPayload($cartData, $shipping);
    }

    /**
     * Place a confirmed order.
     * Everything — order creation, line items, stock decrement, cart clear —
     * happens inside a single DB transaction. Either all succeeds or none does.
     *
     * @throws \Exception
     */
    public function checkout(User $user, array $shipping): Order
    {
        return DB::transaction(function () use ($user, $shipping) {
            $cartData = $this->cartService->getCart($user);

            $this->assertCartNotEmpty($cartData['items']);
            $this->assertStockForAll($cartData['items']);

            $payload = $this->buildPayload($cartData, $shipping);

            // ── 1. Create order header ────────────────────────────────────────
            $order = Order::create([
                'user_id'              => $user->id,
                'order_number'         => Order::generateOrderNumber(),
                'status'               => Order::STATUS_PENDING,
                'subtotal'             => $payload['subtotal'],
                'shipping_fee'         => $payload['shipping_fee'],
                'total_amount'         => $payload['total_amount'],
                'shipping_name'        => $shipping['shipping_name'],
                'shipping_phone'       => $shipping['shipping_phone'],
                'shipping_address'     => $shipping['shipping_address'],
                'shipping_city'        => $shipping['shipping_city'],
                'shipping_postal_code' => $shipping['shipping_postal_code'] ?? null,
                'notes'                => $shipping['notes'] ?? null,
            ]);

            // ── 2. Create order line items with product snapshot ──────────────
            foreach ($payload['line_items'] as $line) {
                $order->items()->create([
                    'product_id'    => $line['product_id'],
                    'product_name'  => $line['product_name'],
                    'product_image' => $line['product_image'],
                    'quantity'      => $line['quantity'],
                    'unit_price'    => $line['unit_price'],
                    'total_price'   => $line['total_price'],
                ]);

                // ── 3. Decrement stock ────────────────────────────────────────
                $line['_product']->decrementStock($line['quantity']);
            }

            // ── 4. Clear the cart ─────────────────────────────────────────────
            $this->cartService->clear($user);

            return $order->load('items');
        });
    }

    /**
     * Cancel an order — only if it belongs to the user and is still cancellable.
     *
     * @throws \Exception
     */
    public function cancel(User $user, string $orderNumber): Order
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->user_id !== $user->id) {
            throw new \Exception('You are not authorised to cancel this order.', 403);
        }

        if (!$order->isCancellable()) {
            throw new \Exception(
                "This order cannot be cancelled. Current status: {$order->status}.",
                422
            );
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return $order->fresh(['items']);
    }

    /**
     * Get one order for the authenticated user, or throw.
     *
     * @throws \Exception
     */
    public function getForUser(User $user, string $orderNumber): Order
    {
        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        if ($order->user_id !== $user->id) {
            // Return 404 — don't reveal that the order exists
            throw new \Exception('Order not found.', 404);
        }

        return $order;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build the full order payload array.
     * Reused by both preview() and checkout() to keep maths in one place.
     */
    private function buildPayload(array $cartData, array $shipping): array
    {
        $items       = $cartData['items'];
        $subtotal    = $items->sum(fn ($i) => ($i->product?->price ?? 0) * $i->quantity);
        $shippingFee = $this->cartService->calculateShippingFee($subtotal);
        $total       = $subtotal + $shippingFee;

        $lineItems = $items->map(function ($cartItem) {
            $product   = $cartItem->product;
            $unitPrice = (float) $product->price;
            $images    = $product->images ?? [];
            $fallbackImage = $this->productImageService->getImage(
                $this->productImageService->inferPetTypeFromProduct($product),
                $this->productImageService->inferCategoryFromProduct($product),
                $this->productImageService->inferSubCategoryFromProduct($product),
            );

            return [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'product_image' => $images[0] ?? $fallbackImage,
                'quantity'      => $cartItem->quantity,
                'unit_price'    => $unitPrice,
                'total_price'   => round($unitPrice * $cartItem->quantity, 2),
                '_product'      => $product,   // kept in memory for stock decrement; not serialised
            ];
        })->all();

        return [
            'line_items'   => $lineItems,
            'subtotal'     => $subtotal,
            'shipping_fee' => $shippingFee,
            'total_amount' => $total,
            'shipping'     => $shipping,
        ];
    }

    /** @throws \Exception */
    private function assertCartNotEmpty($items): void
    {
        if ($items->isEmpty()) {
            throw new \Exception('Your cart is empty. Add items before checking out.', 422);
        }
    }

    /** @throws \Exception */
    private function assertStockForAll($items): void
    {
        foreach ($items as $cartItem) {
            $product = $cartItem->product;

            if (!$product || !$product->is_available) {
                throw new \Exception(
                    "'{$cartItem->product?->name}' is no longer available. Please remove it from your cart.",
                    422
                );
            }

            if ($cartItem->quantity > $product->stock_quantity) {
                throw new \Exception(
                    "Only {$product->stock_quantity} unit(s) of '{$product->name}' are in stock. Please update your cart.",
                    422
                );
            }
        }
    }
}
