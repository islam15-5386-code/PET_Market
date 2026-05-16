'use client'

import React from 'react'
import { Search } from 'lucide-react'
import { clsx } from 'clsx'

interface SearchInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  onEnter?: (value: string) => void
}

export function SearchInput({ className, onEnter, ...props }: SearchInputProps) {
  return (
    <div className={clsx('group relative w-full', className)}>
      <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-amber-600" />
      <input
        {...props}
        onKeyDown={(e) => {
          props.onKeyDown?.(e)
          if (e.key === 'Enter' && onEnter) onEnter((e.target as HTMLInputElement).value)
        }}
        className={clsx(
          'h-12 w-full rounded-2xl border border-slate-200 bg-white/95 pl-10 pr-4 text-sm text-slate-800 shadow-sm',
          'placeholder:text-slate-400 focus:border-amber-300 focus:ring-2 focus:ring-amber-200',
          'transition-[border-color,box-shadow,background-color] duration-200',
        )}
      />
    </div>
  )
}
