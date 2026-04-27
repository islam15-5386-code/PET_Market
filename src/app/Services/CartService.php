<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    /**
     * Return the user's full cart — items with product data + computed summary.
     */
    public function getCart(User $user): array
    {
        $items = CartItem::with('product.category')
            ->where('user_id', $user->id)
            ->get();

        return [
            'items'   => $items,
            'summary' => $this->buildSummary($items),
        ];
    }

    /**
     * Add a product to the cart.
     * If the product already exists, quantities are merged.
     *
     * @throws \Exception
     */
    public function add(User $user, int $productId, int $quantity): CartItem
    {
        $product = Product::findOrFail($productId);

        $this->assertProductAvailable($product);

        // Load or initialise the cart row
        $cartItem = CartItem::firstOrNew([
            'user_id'    => $user->id,
            'product_id' => $productId,
        ]);

        $newQuantity = $cartItem->exists
            ? $cartItem->quantity + $quantity
            : $quantity;

        $this->assertSufficientStock($product, $newQuantity);

        $cartItem->quantity = $newQuantity;
        $cartItem->save();

        return $cartItem->load('product.category');
    }

    /**
     * Set a new quantity for an existing cart item.
     *
     * @throws \Exception
     */
    public function update(User $user, int $productId, int $quantity): CartItem
    {
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $this->assertSufficientStock($cartItem->product, $quantity);

        $cartItem->update(['quantity' => $quantity]);

        return $cartItem->load('product.category');
    }

    /**
     * Remove a specific product from the cart.
     *
     * @throws \Exception
     */
    public function remove(User $user, int $productId): void
    {
        $deleted = CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        if (!$deleted) {
            throw new \Exception('Item not found in cart.', 404);
        }
    }

    /**
     * Delete all items in the user's cart.
     */
    public function clear(User $user): void
    {
        CartItem::where('user_id', $user->id)->delete();
    }

    /**
     * Build the cart summary: subtotal, shipping fee, total, counts.
     * Accepts a Collection so OrderService can reuse this without a DB hit.
     */
    public function buildSummary(Collection $items): array
    {
        $subtotal      = $items->sum(fn ($i) => ($i->product?->price ?? 0) * $i->quantity);
        $shippingFee   = $this->calculateShippingFee($subtotal);
        $totalQuantity = $items->sum('quantity');

        return [
            'items_count'    => $items->count(),
            'total_quantity' => $totalQuantity,
            'subtotal'       => number_format($subtotal, 2, '.', ''),
            'shipping_fee'   => number_format($shippingFee, 2, '.', ''),
            'total'          => number_format($subtotal + $shippingFee, 2, '.', ''),
        ];
    }

    /**
     * Shipping fee rule — isolated here so it can be replaced without
     * touching controllers or OrderService.
     *
     * Rule: free for orders ৳2000+, otherwise ৳60 flat.
     */
    public function calculateShippingFee(float $subtotal): float
    {
        return $subtotal >= 2000.0 ? 0.0 : 60.0;
    }

    // ── Private guards ────────────────────────────────────────────────────────

    /** @throws \Exception */
    private function assertProductAvailable(Product $product): void
    {
        if (!$product->is_available || $product->stock_quantity <= 0) {
            throw new \Exception("'{$product->name}' is currently out of stock.", 422);
        }
    }

    /** @throws \Exception */
    private function assertSufficientStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock_quantity) {
            throw new \Exception(
                "Only {$product->stock_quantity} unit(s) of '{$product->name}' are available.",
                422
            );
        }
    }
}
