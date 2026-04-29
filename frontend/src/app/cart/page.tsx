'use client'

import React from 'react'
import Link from 'next/link'
import { ArrowRight, ShoppingBag, Trash2, Truck } from 'lucide-react'
import { CartItemRow } from '@/components/cart/CartItemRow'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { useCart } from '@/hooks/useCart'

export default function CartPage() {
  const { cart, loading, actionLoading, update, remove, empty } = useCart()

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  const items = cart?.items ?? []
  const summary = cart?.summary

  if (items.length === 0) {
    return (
      <div className="max-w-lg mx-auto px-4 py-24 text-center">
        <ShoppingBag className="h-16 w-16 text-gray-300 mx-auto mb-4" />
        <h1 className="text-2xl font-bold text-gray-900">Your cart is empty</h1>
        <p className="mt-2 text-gray-500">
          Looks like you haven&apos;t added anything yet.
        </p>
        <Link href="/products">
          <Button size="lg" className="mt-8">
            Start Shopping
          </Button>
        </Link>
      </div>
    )
  }

  const freeShippingRemaining =
    Number(summary?.subtotal ?? 0) < 2000
      ? 2000 - Number(summary?.subtotal ?? 0)
      : 0

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">
          Shopping Cart
          <span className="ml-2 text-base font-normal text-gray-400">
            ({summary?.total_quantity ?? 0} items)
          </span>
        </h1>
        <button
          onClick={empty}
          disabled={actionLoading}
          className="flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 disabled:opacity-40"
        >
          <Trash2 className="h-4 w-4" />
          Clear cart
        </button>
      </div>

      {/* Free shipping banner */}
      {freeShippingRemaining > 0 && (
        <div className="mb-6 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
          <Truck className="h-4 w-4 shrink-0" />
          Add <strong className="mx-1">৳{freeShippingRemaining.toLocaleString()}</strong> more for free shipping!
        </div>
      )}
      {freeShippingRemaining === 0 && (
        <div className="mb-6 flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800">
          <Truck className="h-4 w-4 shrink-0" />
          <strong>Free shipping</strong>&nbsp;applied to your order!
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Items list */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-gray-200 px-5 divide-y divide-gray-100">
          {items.map((item) => (
            <CartItemRow
              key={item.id}
              item={item}
              onUpdate={update}
              onRemove={remove}
              disabled={actionLoading}
            />
          ))}
        </div>

        {/* Summary */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24">
            <h2 className="text-base font-semibold text-gray-900 mb-5">
              Order Summary
            </h2>

            <div className="flex flex-col gap-3 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>Subtotal ({summary?.total_quantity} items)</span>
                <span>৳{Number(summary?.subtotal).toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-gray-600">
                <span>Shipping</span>
                <span>
                  {summary?.shipping_fee === '0.00' ? (
                    <span className="text-green-600 font-medium">FREE</span>
                  ) : (
                    `৳${Number(summary?.shipping_fee).toLocaleString()}`
                  )}
                </span>
              </div>
              <div className="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900 text-base">
                <span>Total</span>
                <span>৳{Number(summary?.total).toLocaleString()}</span>
              </div>
            </div>

            <Link href="/checkout">
              <Button fullWidth size="lg" className="mt-6">
                Proceed to Checkout <ArrowRight className="h-4 w-4" />
              </Button>
            </Link>

            <Link
              href="/products"
              className="block mt-3 text-center text-sm text-gray-500 hover:text-gray-700"
            >
              Continue shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
