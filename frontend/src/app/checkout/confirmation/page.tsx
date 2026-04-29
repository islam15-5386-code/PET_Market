'use client'

import React, { Suspense } from 'react'
import Link from 'next/link'
import { useSearchParams } from 'next/navigation'
import { CheckCircle2, Package, ShoppingBag } from 'lucide-react'
import { Button } from '@/components/ui/Button'

function ConfirmationContent() {
  const searchParams = useSearchParams()
  const orderNumber = searchParams.get('order') ?? ''

  return (
    <div className="max-w-lg mx-auto px-4 py-16 text-center">
      {/* Success icon */}
      <div className="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-green-100">
        <CheckCircle2 className="h-14 w-14 text-green-500" />
      </div>

      <h1 className="text-3xl font-extrabold text-gray-900">Order Placed!</h1>
      <p className="mt-3 text-gray-500">
        Thank you for your purchase. Your order has been received and is being processed.
      </p>

      {orderNumber && (
        <div className="mt-6 bg-gray-50 rounded-2xl border border-gray-200 p-5">
          <p className="text-sm text-gray-500 mb-1">Order number</p>
          <p className="text-lg font-bold text-gray-900 font-mono">{orderNumber}</p>
        </div>
      )}

      <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        {orderNumber && (
          <Link href={`/orders/${orderNumber}`}>
            <Button variant="outline" size="lg">
              <Package className="h-4 w-4" />
              View Order
            </Button>
          </Link>
        )}
        <Link href="/products">
          <Button size="lg">
            <ShoppingBag className="h-4 w-4" />
            Continue Shopping
          </Button>
        </Link>
      </div>

      <p className="mt-8 text-xs text-gray-400">
        You can track your order status in{' '}
        <Link href="/orders" className="link-brand">
          My Orders
        </Link>
      </p>
    </div>
  )
}

export default function ConfirmationPage() {
  return (
    <Suspense fallback={<div className="flex items-center justify-center min-h-[60vh]" />}>
      <ConfirmationContent />
    </Suspense>
  )
}
