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
        <label htmlFor={inputId} className="text-sm font-medium text-gray-700">
          {label}
          {props.required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}

      <input
        id={inputId}
        className={clsx(
          'w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm',
          'placeholder:text-gray-400 transition-all duration-200',
          'focus:border-orange-300 focus:ring-2 focus:ring-orange-200',
          'disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500',
          error ? 'border-red-300 focus:border-red-300 focus:ring-red-200' : 'border-gray-200 hover:border-gray-300',
          className,
        )}
        {...props}
      />

      {error && (
        <p className="text-xs text-red-600" role="alert">
          {error}
        </p>
      )}
      {!error && helperText && <p className="text-xs text-gray-500">{helperText}</p>}
    </div>
  )
}
