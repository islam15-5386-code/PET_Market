import React from 'react'

interface LoadingSkeletonProps {
  className?: string
}

export function LoadingSkeleton({ className = '' }: LoadingSkeletonProps) {
  return <div className={`animate-pulse rounded-xl bg-gray-200/75 ${className}`} />
}
