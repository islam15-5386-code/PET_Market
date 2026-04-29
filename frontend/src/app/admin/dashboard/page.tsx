'use client'

import React from 'react'
import Link from 'next/link'
import {
  Users, Package, ShoppingBag, TrendingUp,
  CalendarDays, Banknote, Clock, AlertTriangle,
} from 'lucide-react'
import { StatsCard } from '@/components/admin/StatsCard'
import { RevenueChart } from '@/components/admin/RevenueChart'
import { OrderStatusBadge } from '@/components/order/OrderStatusBadge'
import { Spinner } from '@/components/ui/Spinner'
import { useDashboard } from '@/hooks/admin/useAdmin'
import type { OrderStatus } from '@/types'

export default function AdminDashboardPage() {
  const { snapshot, revenue, grandTotal, loading } = useDashboard()

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  const s = snapshot?.stats

  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

      {/* Stats grid */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatsCard title="Total Users"     value={s?.total_users ?? 0}    icon={Users}       color="blue"   />
        <StatsCard title="Total Products"  value={s?.total_products ?? 0} icon={Package}     color="purple" />
        <StatsCard title="Total Orders"    value={s?.total_orders ?? 0}   icon={ShoppingBag} color="orange" />
        <StatsCard title="Total Revenue"   value={`৳${Number(s?.total_revenue ?? 0).toLocaleString()}`} icon={TrendingUp} color="green" />
        <StatsCard title="Orders Today"    value={s?.orders_today ?? 0}   icon={CalendarDays} color="amber" />
        <StatsCard title="Revenue Today"   value={`৳${Number(s?.revenue_today ?? 0).toLocaleString()}`} icon={Banknote} color="green" />
        <StatsCard title="Pending Orders"  value={s?.pending_orders ?? 0} icon={Clock}       color="amber" subtitle="Awaiting confirmation" />
        <StatsCard title="Low Stock"       value={s?.low_stock_products ?? 0} icon={AlertTriangle} color="red" subtitle="≤5 units remaining" />
      </div>

      {/* Revenue chart */}
      <RevenueChart data={revenue} grandTotal={grandTotal} />

      {/* Bottom row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {/* Recent orders */}
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 className="font-semibold text-gray-900 text-sm">Recent Orders</h2>
            <Link href="/admin/orders" className="text-xs text-orange-600 hover:text-orange-700 font-medium">
              View all
            </Link>
          </div>
          <div className="divide-y divide-gray-50">
            {(snapshot?.recent_orders ?? []).map((order) => (
              <Link
                key={order.id}
                href={`/admin/orders`}
                className="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors"
              >
                <div>
                  <p className="text-sm font-medium text-gray-900 font-mono">{order.order_number}</p>
                  <p className="text-xs text-gray-500">{order.customer}</p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-semibold text-gray-900">
                    ৳{Number(order.total_amount).toLocaleString()}
                  </p>
                  <OrderStatusBadge status={order.status as OrderStatus} />
                </div>
              </Link>
            ))}
          </div>
        </div>

        {/* Low stock alerts */}
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 className="font-semibold text-gray-900 text-sm">Low Stock Alerts</h2>
            <Link href="/admin/products" className="text-xs text-orange-600 hover:text-orange-700 font-medium">
              Manage
            </Link>
          </div>

          {(snapshot?.low_stock_alerts ?? []).length === 0 ? (
            <div className="px-5 py-8 text-center text-sm text-gray-400">
              All products well-stocked ✓
            </div>
          ) : (
            <div className="divide-y divide-gray-50">
              {(snapshot?.low_stock_alerts ?? []).map((p) => (
                <div key={p.id} className="flex items-center justify-between px-5 py-3">
                  <p className="text-sm text-gray-800 line-clamp-1 flex-1 mr-4">{p.name}</p>
                  <span
                    className={`text-xs font-bold px-2 py-0.5 rounded-full ${
                      p.stock_quantity === 0
                        ? 'bg-red-100 text-red-700'
                        : 'bg-amber-100 text-amber-700'
                    }`}
                  >
                    {p.stock_quantity} left
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
