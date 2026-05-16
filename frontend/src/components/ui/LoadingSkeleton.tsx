import React from 'react'

interface LoadingSkeletonProps {
  className?: string
}

export function LoadingSkeleton({ className = '' }: LoadingSkeletonProps) {
  return <div className={`shimmer rounded-xl ${className}`} />
}
