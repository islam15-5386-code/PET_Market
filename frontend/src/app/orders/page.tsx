'use client'

import React from 'react'
import { ChevronLeft, ChevronRight, Package } from 'lucide-react'
import { OrderCard } from '@/components/order/OrderCard'
import { Spinner } from '@/components/ui/Spinner'
import { useOrderHistory } from '@/hooks/useOrders'

export default function OrdersPage() {
  const { orders, meta, page, setPage, loading, error } = useOrderHistory()

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">My Orders</h1>

      {loading && (
        <div className="flex items-center justify-center py-20">
          <Spinner size="lg" />
        </div>
      )}

      {!loading && error && (
        <div className="text-center py-16 text-gray-500">{error}</div>
      )}

      {!loading && !error && orders.length === 0 && (
        <div className="text-center py-20">
          <Package className="h-14 w-14 text-gray-300 mx-auto mb-4" />
          <p className="font-semibold text-gray-700">No orders yet</p>
          <p className="text-sm text-gray-500 mt-1">
            When you place an order, it will appear here.
          </p>
        </div>
      )}

      {!loading && orders.length > 0 && (
        <>
          <div className="flex flex-col gap-3">
            {orders.map((order) => (
              <OrderCard key={order.id} order={order} />
            ))}
          </div>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-center gap-3 mt-8">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="p-2 rounded-xl border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
              >
                <ChevronLeft className="h-4 w-4" />
              </button>
              <span className="text-sm text-gray-600">
                Page {meta.current_page} of {meta.last_page}
              </span>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="p-2 rounded-xl border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
              >
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}
