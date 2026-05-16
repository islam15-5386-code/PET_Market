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
  orange: 'bg-orange-100 text-orange-700',
  blue:   'bg-sky-100 text-sky-700',
  green:  'bg-emerald-100 text-emerald-700',
  purple: 'bg-violet-100 text-violet-700',
  red:    'bg-rose-100 text-rose-700',
  amber:  'bg-amber-100 text-amber-700',
}

export function StatsCard({
  title,
  value,
  icon: Icon,
  color = 'orange',
  subtitle,
}: StatsCardProps) {
  return (
    <div className="dynamic-surface motion-fade-up rounded-2xl border border-slate-200 bg-white/95 p-5 shadow-[0_16px_34px_-28px_rgba(15,23,42,.7)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_22px_40px_-28px_rgba(15,23,42,.7)]">
      <div className="flex items-start gap-4">
      <div className={clsx('p-3 rounded-xl shrink-0', colorMap[color])}>
        <Icon className="h-5 w-5" />
      </div>
      <div className="min-w-0">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{title}</p>
        <p className="mt-0.5 truncate text-2xl font-bold text-slate-900">{value}</p>
        {subtitle && <p className="mt-0.5 text-xs text-slate-400">{subtitle}</p>}
      </div>
      </div>
    </div>
  )
}
