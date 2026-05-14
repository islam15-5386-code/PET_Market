import React from 'react'
import { Sparkles } from 'lucide-react'
import { clsx } from 'clsx'

interface AIChipProps {
  label?: string
  className?: string
}

export function AIChip({ label = 'AI Powered', className }: AIChipProps) {
  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-gradient-to-r from-indigo-50 to-blue-50 px-3 py-1 text-xs font-semibold text-indigo-700',
        className,
      )}
    >
      <Sparkles className="h-3.5 w-3.5" />
      {label}
    </span>
  )
}
