'use client'

import React from 'react'
import { clsx } from 'clsx'

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  helperText?: string
}

export function Input({
  label,
  error,
  helperText,
  id,
  className,
  ...props
}: InputProps) {
  const inputId = id ?? label?.toLowerCase().replace(/\s+/g, '-')

  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label htmlFor={inputId} className="text-sm font-semibold text-slate-700">
          {label}
          {props.required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}

      <input
        id={inputId}
        aria-invalid={!!error}
        className={clsx(
          'w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm',
          'placeholder:text-slate-400 transition-all duration-200 motion-safe:hover:-translate-y-[1px]',
          'focus:border-amber-300 focus:ring-2 focus:ring-amber-200',
          'disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500',
          error ? 'border-red-300 focus:border-red-300 focus:ring-red-200' : 'border-slate-200 hover:border-slate-300',
          className,
        )}
        {...props}
      />

      {error && (
        <p className="text-xs text-red-600" role="alert">
          {error}
        </p>
      )}
      {!error && helperText && <p className="text-xs text-slate-500">{helperText}</p>}
    </div>
  )
}
