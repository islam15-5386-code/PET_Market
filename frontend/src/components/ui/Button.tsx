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
    'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-sm hover:shadow-md hover:from-orange-600 hover:to-amber-600 active:scale-[0.99] disabled:from-orange-300 disabled:to-amber-300',
  secondary:
    'bg-gray-900 text-white shadow-sm hover:bg-gray-800 active:scale-[0.99] disabled:bg-gray-400',
  outline:
    'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-400 active:scale-[0.99] disabled:text-gray-400',
  ghost:
    'text-gray-700 hover:bg-gray-100 active:bg-gray-200 disabled:text-gray-400',
  danger:
    'bg-red-600 text-white shadow-sm hover:bg-red-700 active:scale-[0.99] disabled:bg-red-300',
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
        'transition-all duration-200 focus:outline-none focus-visible:ring-2',
        'focus-visible:ring-orange-400 focus-visible:ring-offset-2',
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
