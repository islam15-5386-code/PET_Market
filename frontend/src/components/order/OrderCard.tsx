import Link from 'next/link'
import { ChevronRight, Package } from 'lucide-react'
import { OrderStatusBadge } from './OrderStatusBadge'
import type { Order } from '@/types'

export function OrderCard({ order }: { order: Order }) {
  const date = new Date(order.created_at).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })

  return (
    <Link
      href={`/orders/${order.order_number}`}
      className="flex items-center justify-between gap-4 bg-white rounded-2xl border border-gray-200 p-5 hover:border-orange-200 hover:shadow-sm transition-all group"
    >
      <div className="flex items-center gap-4">
        <div className="h-11 w-11 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
          <Package className="h-5 w-5 text-orange-500" />
        </div>
        <div>
          <p className="text-sm font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">
            {order.order_number}
          </p>
          <p className="text-xs text-gray-500 mt-0.5">
            {date} &middot; {order.items_count ?? '—'} item{(order.items_count ?? 0) !== 1 ? 's' : ''}
          </p>
        </div>
      </div>

      <div className="flex items-center gap-3">
        <div className="text-right hidden sm:block">
          <p className="text-sm font-bold text-gray-900">
            ৳{Number(order.total_amount).toLocaleString()}
          </p>
          <OrderStatusBadge status={order.status} />
        </div>
        <ChevronRight className="h-4 w-4 text-gray-400 group-hover:text-orange-500 transition-colors" />
      </div>
    </Link>
  )
}
