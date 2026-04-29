import { clsx } from 'clsx'
import type { OrderStatus } from '@/types'

const CONFIG: Record<OrderStatus, { label: string; classes: string }> = {
  pending:    { label: 'Pending',    classes: 'bg-amber-100 text-amber-800' },
  confirmed:  { label: 'Confirmed',  classes: 'bg-blue-100 text-blue-800' },
  processing: { label: 'Processing', classes: 'bg-purple-100 text-purple-800' },
  shipped:    { label: 'Shipped',    classes: 'bg-indigo-100 text-indigo-800' },
  delivered:  { label: 'Delivered',  classes: 'bg-green-100 text-green-800' },
  cancelled:  { label: 'Cancelled',  classes: 'bg-red-100 text-red-800' },
}

export function OrderStatusBadge({ status }: { status: OrderStatus }) {
  const cfg = CONFIG[status] ?? { label: status, classes: 'bg-gray-100 text-gray-700' }
  return (
    <span
      className={clsx(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
        cfg.classes,
      )}
    >
      {cfg.label}
    </span>
  )
}
