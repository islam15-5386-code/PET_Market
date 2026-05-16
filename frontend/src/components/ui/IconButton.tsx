'use client'

import React from 'react'
import { clsx } from 'clsx'

interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  label: string
  size?: 'sm' | 'md'
}

export function IconButton({ label, size = 'md', className, ...props }: IconButtonProps) {
  return (
    <button
      aria-label={label}
      className={clsx(
        'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600',
        'transform-gpu transition-[color,background-color,border-color,box-shadow,transform] duration-200 ease-out',
        'hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 hover:shadow-sm',
        'active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        size === 'sm' ? 'h-8 w-8' : 'h-10 w-10',
        className,
      )}
      {...props}
    />
  )
}

