import React from 'react'
import { Package } from 'lucide-react'

interface EmptyStateProps {
  title: string
  description?: string
  icon?: React.ReactNode
}

export function EmptyState({ title, description, icon }: EmptyStateProps) {
  return (
    <div className="surface-card motion-fade-up dynamic-surface flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
      <div className="motion-pulse-soft grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
        {icon ?? <Package className="h-7 w-7" />}
      </div>
      <p className="text-base font-semibold text-slate-800">{title}</p>
      {description && <p className="max-w-md text-sm text-slate-500">{description}</p>}
    </div>
  )
}
