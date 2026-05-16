'use client'

import React from 'react'
import { clsx } from 'clsx'
import { AlertCircle, CheckCircle2, Info, XCircle } from 'lucide-react'

type AlertVariant = 'success' | 'error' | 'info' | 'warning'

interface AlertProps {
  variant?: AlertVariant
  title?: string
  message: string
  className?: string
  onDismiss?: () => void
}

const config: Record<
  AlertVariant,
  { icon: React.ElementType; classes: string }
> = {
  success: {
    icon: CheckCircle2,
    classes: 'bg-green-50 border-green-200 text-green-800',
  },
  error: {
    icon: XCircle,
    classes: 'bg-red-50 border-red-200 text-red-800',
  },
  info: {
    icon: Info,
    classes: 'bg-blue-50 border-blue-200 text-blue-800',
  },
  warning: {
    icon: AlertCircle,
    classes: 'bg-amber-50 border-amber-200 text-amber-800',
  },
}

export function Alert({
  variant = 'info',
  title,
  message,
  className,
  onDismiss,
}: AlertProps) {
  const { icon: Icon, classes } = config[variant]

  return (
    <div
      role="alert"
      className={clsx(
        'motion-fade-up flex gap-3 rounded-xl border p-4 shadow-[0_12px_24px_-26px_rgba(15,23,42,.7)]',
        classes,
        className,
      )}
    >
      <Icon className="mt-0.5 h-5 w-5 shrink-0" />
      <div className="text-sm flex-1">
        {title && <p className="font-semibold">{title}</p>}
        <p className={title ? 'mt-0.5 opacity-90' : ''}>{message}</p>
      </div>
      {onDismiss && (
        <button
          type="button"
          onClick={onDismiss}
          aria-label="Dismiss alert"
          className="text-current/70 hover:text-current transition"
        >
          ×
        </button>
      )}
    </div>
  )
}
