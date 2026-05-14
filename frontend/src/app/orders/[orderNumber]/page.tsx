'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { useParams } from 'next/navigation'
import { ArrowLeft, Package, XCircle } from 'lucide-react'
import { OrderStatusBadge } from '@/components/order/OrderStatusBadge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Spinner } from '@/components/ui/Spinner'
import { useOrder } from '@/hooks/useOrders'

export default function OrderDetailPage() {
  const fallbackImage = '/placeholder-product.png'
  const params = useParams()
  const orderNumber = params.orderNumber as string
  const { order, loading, error, cancel, cancelling } = useOrder(orderNumber)
  const [cancelError, setCancelError] = useState('')
  const [cancelSuccess, setCancelSuccess] = useState(false)

  async function handleCancel() {
    if (!confirm('Are you sure you want to cancel this order?')) return
    setCancelError('')
    try {
      await cancel()
      setCancelSuccess(true)
    } catch (err: any) {
      setCancelError(err.message)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  if (error || !order) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-16 text-center">
        <Package className="h-14 w-14 text-gray-300 mx-auto mb-4" />
        <p className="font-semibold text-gray-700">Order not found</p>
        <Link href="/orders" className="mt-4 inline-block link-brand text-sm">
          Back to orders
        </Link>
      </div>
    )
  }

  const isCancellable = ['pending', 'confirmed'].includes(order.status)
  const date = new Date(order.created_at).toLocaleDateString('en-GB', {
    day: 'numeric', month: 'long', year: 'numeric',
  })

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 py-8">
      {/* Back */}
      <Link
        href="/orders"
        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6"
      >
        <ArrowLeft className="h-4 w-4" /> Back to orders
      </Link>

      {/* Header */}
      <div className="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
          <h1 className="text-xl font-bold text-gray-900 font-mono">{order.order_number}</h1>
          <p className="text-sm text-gray-500 mt-1">Placed on {date}</p>
        </div>
        <OrderStatusBadge status={order.status} />
      </div>

      {cancelError && <Alert variant="error" message={cancelError} className="mb-4" />}
      {cancelSuccess && (
        <Alert variant="success" message="Your order has been cancelled." className="mb-4" />
      )}

      {/* Items */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-5">
        <div className="px-5 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-900 text-sm">
            Items ({order.items?.length ?? 0})
          </h2>
        </div>
        <div className="divide-y divide-gray-100">
          {(order.items ?? []).map((item) => (
            <div key={item.id} className="flex items-center gap-4 px-5 py-4">
              <div className="relative h-14 w-14 shrink-0 rounded-xl overflow-hidden bg-gray-100">
                {item.product_image ? (
                  <Image
                    src={item.product_image}
                    alt={item.product_name}
                    fill
                    className="object-cover"
                    sizes="56px"
                    onError={(e) => {
                      const target = e.currentTarget as HTMLImageElement
                      target.src = fallbackImage
                    }}
                  />
                ) : (
                  <div className="h-full flex items-center justify-center">
                    <Package className="h-6 w-6 text-gray-300" />
                  </div>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 line-clamp-1">
                  {item.product_name}
                </p>
                <p className="text-xs text-gray-500 mt-0.5">
                  ৳{Number(item.unit_price).toLocaleString()} × {item.quantity}
                </p>
              </div>
              <span className="text-sm font-bold text-gray-900 shrink-0">
                ৳{Number(item.total_price).toLocaleString()}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Two-column: shipping + totals */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
        {/* Shipping */}
        <div className="bg-white rounded-2xl border border-gray-200 p-5">
          <h2 className="font-semibold text-gray-900 text-sm mb-3">Shipping To</h2>
          <div className="text-sm text-gray-600 leading-relaxed">
            <p className="font-medium text-gray-900">{order.shipping.name}</p>
            <p>{order.shipping.phone}</p>
            <p>{order.shipping.address}</p>
            <p>{order.shipping.city}{order.shipping.postal_code ? ` — ${order.shipping.postal_code}` : ''}</p>
          </div>
          {order.notes && (
            <p className="mt-3 text-xs text-gray-500 italic">Note: {order.notes}</p>
          )}
        </div>

        {/* Totals */}
        <div className="bg-white rounded-2xl border border-gray-200 p-5">
          <h2 className="font-semibold text-gray-900 text-sm mb-3">Payment Summary</h2>
          <div className="flex flex-col gap-2 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Subtotal</span>
              <span>৳{Number(order.subtotal).toLocaleString()}</span>
            </div>
            <div className="flex justify-between text-gray-600">
              <span>Shipping</span>
              {order.shipping_fee === '0.00' ? (
                <span className="text-green-600 font-medium">FREE</span>
              ) : (
                <span>৳{Number(order.shipping_fee).toLocaleString()}</span>
              )}
            </div>
            <div className="flex justify-between font-bold text-gray-900 border-t border-gray-100 pt-2">
              <span>Total</span>
              <span>৳{Number(order.total_amount).toLocaleString()}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Cancel */}
      {isCancellable && !cancelSuccess && (
        <div className="flex justify-end">
          <Button
            variant="danger"
            loading={cancelling}
            onClick={handleCancel}
          >
            <XCircle className="h-4 w-4" />
            Cancel Order
          </Button>
        </div>
      )}
    </div>
  )
}
