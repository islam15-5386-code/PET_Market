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
    'bg-orange-600 text-white hover:bg-orange-700 active:bg-orange-800 disabled:bg-orange-300',
  secondary:
    'bg-gray-800 text-white hover:bg-gray-900 active:bg-gray-950 disabled:bg-gray-400',
  outline:
    'border-2 border-orange-600 text-orange-600 hover:bg-orange-50 active:bg-orange-100 disabled:border-orange-300 disabled:text-orange-300',
  ghost:
    'text-gray-700 hover:bg-gray-100 active:bg-gray-200 disabled:text-gray-400',
  danger:
    'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 disabled:bg-red-300',
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
        'transition-colors duration-150 focus:outline-none focus-visible:ring-2',
        'focus-visible:ring-orange-500 focus-visible:ring-offset-2',
        'disabled:cursor-not-allowed',
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
