'use client'

import React from 'react'
import Link from 'next/link'
import { ArrowRight, ShoppingBag, Trash2, Truck } from 'lucide-react'
import { CartItemRow } from '@/components/cart/CartItemRow'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { EmptyState } from '@/components/ui/EmptyState'
import { PageHeader } from '@/components/ui/PageHeader'
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
      <div className="mx-auto max-w-lg px-4 py-24">
        <EmptyState title="Your cart is empty" description="Looks like you haven&apos;t added anything yet." icon={<ShoppingBag className="h-7 w-7" />} />
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
      <div className="mb-6 flex items-center justify-between">
        <PageHeader
          title="Shopping Cart"
          description={`${summary?.total_quantity ?? 0} items in your cart`}
        />
        <button
          onClick={empty}
          disabled={actionLoading}
          className="flex items-center gap-1.5 text-sm text-red-500 transition-colors hover:text-red-700 disabled:opacity-40"
        >
          <Trash2 className="h-4 w-4" />
          Clear cart
        </button>
      </div>

      {/* Free shipping banner */}
      {freeShippingRemaining > 0 && (
        <div className="mb-6 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          <Truck className="h-4 w-4 shrink-0" />
          Add <strong className="mx-1">৳{freeShippingRemaining.toLocaleString()}</strong> more for free shipping!
        </div>
      )}
      {freeShippingRemaining === 0 && (
        <div className="mb-6 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
          <Truck className="h-4 w-4 shrink-0" />
          <strong>Free shipping</strong>&nbsp;applied to your order!
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Items list */}
        <div className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white px-5 shadow-[0_16px_34px_-28px_rgba(15,23,42,.7)] lg:col-span-2">
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
          <div className="sticky top-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_16px_34px_-28px_rgba(15,23,42,.7)]">
            <h2 className="mb-5 text-base font-semibold text-slate-900">
              Order Summary
            </h2>

            <div className="flex flex-col gap-3 text-sm">
              <div className="flex justify-between text-slate-600">
                <span>Subtotal ({summary?.total_quantity} items)</span>
                <span>৳{Number(summary?.subtotal).toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-slate-600">
                <span>Shipping</span>
                <span>
                  {summary?.shipping_fee === '0.00' ? (
                    <span className="text-green-600 font-medium">FREE</span>
                  ) : (
                    `৳${Number(summary?.shipping_fee).toLocaleString()}`
                  )}
                </span>
              </div>
              <div className="flex justify-between border-t border-slate-100 pt-3 text-base font-bold text-slate-900">
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
              className="mt-3 block text-center text-sm text-slate-500 hover:text-slate-700"
            >
              Continue shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
