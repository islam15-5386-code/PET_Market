import React from 'react'
import { clsx } from 'clsx'

interface CardProps {
  children: React.ReactNode
  className?: string
}

export function Card({ children, className }: CardProps) {
  return (
    <div className={clsx('surface-card transition-all duration-200 ease-out motion-safe:hover:shadow-[0_20px_40px_-34px_rgba(15,23,42,.7)]', className)}>
      {children}
    </div>
  )
}
