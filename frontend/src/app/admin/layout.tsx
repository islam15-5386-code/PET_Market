'use client'

import React, { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Search } from 'lucide-react'
import { AdminSidebar } from '@/components/admin/AdminSidebar'
import { Spinner } from '@/components/ui/Spinner'
import { useAuth } from '@/context/AuthContext'

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isAdmin, isLoading, user } = useAuth()
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
      <div className="flex min-h-screen items-center justify-center bg-gray-950">
        <Spinner size="lg" />
      </div>
    )
  }

  return (
    <div className="flex min-h-screen bg-slate-100/80">
      <AdminSidebar />
      <div className="flex-1 overflow-auto">
        <div className="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
          <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
            <div className="hidden items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 md:flex">
              <Search className="h-4 w-4" />
              Search dashboard...
            </div>
            <div className="ml-auto text-right">
              <p className="text-xs text-gray-500">Signed in as</p>
              <p className="text-sm font-semibold text-gray-800">{user?.name}</p>
            </div>
          </div>
        </div>

        <div className="mx-auto max-w-7xl px-6 py-6">{children}</div>
      </div>
    </div>
  )
}
