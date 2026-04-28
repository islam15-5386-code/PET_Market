import { clsx } from 'clsx'
import type { LucideIcon } from 'lucide-react'

interface StatsCardProps {
  title: string
  value: string | number
  icon: LucideIcon
  color?: 'orange' | 'blue' | 'green' | 'purple' | 'red' | 'amber'
  subtitle?: string
}

const colorMap = {
  orange: 'bg-orange-100 text-orange-600',
  blue:   'bg-blue-100 text-blue-600',
  green:  'bg-green-100 text-green-600',
  purple: 'bg-purple-100 text-purple-600',
  red:    'bg-red-100 text-red-600',
  amber:  'bg-amber-100 text-amber-600',
}

export function StatsCard({
  title,
  value,
  icon: Icon,
  color = 'orange',
  subtitle,
}: StatsCardProps) {
  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 flex items-start gap-4">
      <div className={clsx('p-3 rounded-xl shrink-0', colorMap[color])}>
        <Icon className="h-5 w-5" />
      </div>
      <div className="min-w-0">
        <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{title}</p>
        <p className="text-2xl font-bold text-gray-900 mt-0.5 truncate">{value}</p>
        {subtitle && <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>}
      </div>
    </div>
  )
}
