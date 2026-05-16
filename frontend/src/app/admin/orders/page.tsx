'use client'

import React from 'react'
import { Search } from 'lucide-react'
import { OrderStatusBadge } from '@/components/order/OrderStatusBadge'
import { Spinner } from '@/components/ui/Spinner'
import { EmptyState } from '@/components/ui/EmptyState'
import { PageHeader } from '@/components/ui/PageHeader'
import { useAdminOrders } from '@/hooks/admin/useAdmin'
import type { OrderStatus } from '@/types'

const ORDER_STATUSES = [
  'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled',
]

// Valid next statuses per current status
const TRANSITIONS: Record<string, string[]> = {
  pending:    ['confirmed', 'cancelled'],
  confirmed:  ['processing', 'cancelled'],
  processing: ['shipped'],
  shipped:    ['delivered'],
  delivered:  [],
  cancelled:  [],
}

export default function AdminOrdersPage() {
  const {
    orders, meta, filterStatus, setFilterStatus,
    search, setSearch, page, setPage,
    loading, updatingId, changeStatus,
  } = useAdminOrders()

  return (
    <div className="flex flex-col gap-6">
      <PageHeader title="Orders" description="Track order lifecycle and update fulfillment status." />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            placeholder="Search order # or customer..."
            className="w-64 rounded-xl border border-slate-300 py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
          />
        </div>

        <select
          value={filterStatus}
          onChange={(e) => { setFilterStatus(e.target.value); setPage(1) }}
          className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
        >
          <option value="">All Statuses</option>
          {ORDER_STATUSES.map((s) => (
            <option key={s} value={s} className="capitalize">{s}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_34px_-28px_rgba(15,23,42,.7)]">
        {loading ? (
          <div className="flex items-center justify-center py-20"><Spinner /></div>
        ) : orders.length === 0 ? (
          <div className="p-4">
            <EmptyState title="No orders found" description="Try changing filters or search term." />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50/80 text-left">
                  <th className="px-4 py-3 font-medium text-slate-500">Order</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Customer</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Items</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Total</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Date</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Status</th>
                  <th className="px-4 py-3 text-right font-medium text-slate-500">Update</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-50">
                {orders.map((order) => {
                  const allowed = TRANSITIONS[order.status] ?? []
                  const isUpdating = updatingId === order.order_number

                  return (
                    <tr key={order.id} className="transition-colors hover:bg-slate-50">
                      <td className="px-4 py-3">
                        <p className="font-mono text-xs font-medium text-slate-900">
                          {order.order_number}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        {order.customer ? (
                          <div>
                            <p className="font-medium text-slate-900">{order.customer.name}</p>
                            <p className="text-xs text-slate-500">{order.customer.email}</p>
                          </div>
                        ) : (
                          <span className="text-slate-400">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-slate-700">{order.items_count}</td>
                      <td className="px-4 py-3 font-semibold text-slate-900">
                        ৳{Number(order.total_amount).toLocaleString()}
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-500">
                        {new Date(order.created_at).toLocaleDateString('en-GB', {
                          day: 'numeric', month: 'short', year: 'numeric',
                        })}
                      </td>
                      <td className="px-4 py-3">
                        <OrderStatusBadge status={order.status as OrderStatus} />
                      </td>
                      <td className="px-4 py-3 text-right">
                        {allowed.length > 0 ? (
                          <select
                            disabled={isUpdating}
                            defaultValue=""
                            onChange={(e) => {
                              if (e.target.value) {
                                changeStatus(order.order_number, e.target.value)
                                e.target.value = ''
                              }
                            }}
                            className="cursor-pointer rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:opacity-40"
                          >
                            <option value="" disabled>
                              {isUpdating ? 'Updating…' : 'Move to…'}
                            </option>
                            {allowed.map((s) => (
                              <option key={s} value={s} className="capitalize">{s}</option>
                            ))}
                          </select>
                        ) : (
                          <span className="text-xs italic text-slate-400">Terminal</span>
                        )}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <p className="text-xs text-slate-500">
              {meta.total} orders · Page {meta.current_page} of {meta.last_page}
            </p>
            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-slate-50"
              >
                Prev
              </button>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-slate-50"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
