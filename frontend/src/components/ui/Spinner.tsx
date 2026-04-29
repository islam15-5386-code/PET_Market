import { Loader2 } from 'lucide-react'
import { clsx } from 'clsx'

interface SpinnerProps {
  size?: 'sm' | 'md' | 'lg'
  className?: string
  label?: string
}

const sizes = { sm: 'h-4 w-4', md: 'h-6 w-6', lg: 'h-10 w-10' }

export function Spinner({ size = 'md', className, label = 'Loading...' }: SpinnerProps) {
  return (
    <div className={clsx('flex items-center justify-center', className)} role="status">
      <Loader2 className={clsx('animate-spin text-orange-500', sizes[size])} />
      <span className="sr-only">{label}</span>
    </div>
  )
}
