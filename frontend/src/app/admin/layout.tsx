'use client'

import React, { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { AdminSidebar } from '@/components/admin/AdminSidebar'
import { Spinner } from '@/components/ui/Spinner'
import { useAuth } from '@/context/AuthContext'

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isAdmin, isLoading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (isLoading) return
    if (!isAuthenticated) {
      router.replace('/login?redirect=/admin/dashboard')
      return
    }
    if (!isAdmin) {
      router.replace('/')
    }
  }, [isLoading, isAuthenticated, isAdmin, router])

  if (isLoading || !isAuthenticated || !isAdmin) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-950">
        <Spinner size="lg" />
      </div>
    )
  }

  return (
    <div className="flex min-h-screen bg-gray-50">
      <AdminSidebar />
      <div className="flex-1 overflow-auto">
        <div className="max-w-7xl mx-auto px-6 py-6">{children}</div>
      </div>
    </div>
  )
}
