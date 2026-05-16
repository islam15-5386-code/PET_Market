'use client'

import React from 'react'
import { clsx } from 'clsx'
import { Loader2 } from 'lucide-react'

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  fullWidth?: boolean
}

const variantClasses: Record<string, string> = {
  primary:
    'bg-gradient-to-r from-amber-600 to-orange-500 text-white shadow-sm hover:shadow-md hover:from-amber-700 hover:to-orange-600 active:scale-[0.98] disabled:from-amber-300 disabled:to-orange-300',
  secondary:
    'bg-slate-900 text-white shadow-sm hover:bg-slate-800 active:scale-[0.98] disabled:bg-slate-400',
  outline:
    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-400 active:scale-[0.98] disabled:text-slate-400',
  ghost:
    'text-slate-700 hover:bg-slate-100 active:bg-slate-200 active:scale-[0.98] disabled:text-slate-400',
  danger:
    'bg-red-600 text-white shadow-sm hover:bg-red-700 active:scale-[0.98] disabled:bg-red-300',
}

const sizeClasses: Record<string, string> = {
  sm: 'h-8 px-3 text-sm rounded-lg',
  md: 'h-10 px-4 text-sm rounded-xl',
  lg: 'h-12 px-6 text-base rounded-xl',
}

export function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  fullWidth = false,
  disabled,
  className,
  children,
  ...props
}: ButtonProps) {
  return (
    <button
      disabled={disabled || loading}
      className={clsx(
        'inline-flex items-center justify-center gap-2 font-semibold',
        'transform-gpu transition-[background-color,color,border-color,box-shadow,transform] duration-200 ease-out',
        'motion-safe:hover:shadow-md focus:outline-none focus-visible:ring-2',
        'focus-visible:ring-amber-400 focus-visible:ring-offset-2',
        'disabled:cursor-not-allowed disabled:shadow-none',
        variantClasses[variant],
        sizeClasses[size],
        fullWidth && 'w-full',
        className,
      )}
      {...props}
    >
      {loading && <Loader2 className="h-4 w-4 animate-spin" />}
      {children}
    </button>
  )
}
