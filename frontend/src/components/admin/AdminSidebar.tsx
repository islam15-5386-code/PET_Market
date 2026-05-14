'use client'

import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { LayoutDashboard, Package, Tag, Users, ShoppingBag, LogOut, ChevronRight, Sparkles } from 'lucide-react'
import { clsx } from 'clsx'
import { useAuth } from '@/context/AuthContext'

const NAV = [
  { href: '/admin/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
  { href: '/admin/products', icon: Package, label: 'Products' },
  { href: '/admin/categories', icon: Tag, label: 'Categories' },
  { href: '/admin/users', icon: Users, label: 'Users' },
  { href: '/admin/orders', icon: ShoppingBag, label: 'Orders' },
]

export function AdminSidebar() {
  const pathname = usePathname()
  const router = useRouter()
  const { user, logout } = useAuth()

  async function handleLogout() {
    await logout()
    router.push('/login')
  }

  return (
    <aside className="flex min-h-screen w-64 shrink-0 flex-col bg-[#111827] text-gray-300">
      <div className="border-b border-gray-800 px-5 py-5">
        <div className="flex items-center gap-2">
          <span className="text-2xl">🐾</span>
          <div>
            <p className="text-sm font-bold text-white leading-none">Pet Marketplace</p>
            <p className="mt-1 text-xs text-orange-400">Admin Workspace</p>
          </div>
        </div>
        <div className="mt-3 inline-flex items-center gap-1 rounded-full border border-indigo-400/30 bg-indigo-400/10 px-2.5 py-1 text-[11px] font-semibold text-indigo-200">
          <Sparkles className="h-3 w-3" /> AI-enabled Panel
        </div>
      </div>

      <nav className="flex-1 px-3 py-4 flex flex-col gap-1">
        {NAV.map(({ href, icon: Icon, label }) => {
          const active = pathname.startsWith(href)
          return (
            <Link
              key={href}
              href={href}
              className={clsx(
                'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                active ? 'bg-orange-500 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white',
              )}
            >
              <span className="flex items-center gap-3"><Icon className="h-4 w-4" />{label}</span>
              {active && <ChevronRight className="h-3.5 w-3.5" />}
            </Link>
          )
        })}
      </nav>

      <div className="border-t border-gray-800 px-3 py-4">
        <div className="mb-1 flex items-center gap-3 px-3 py-2">
          <div className="grid h-8 w-8 place-items-center rounded-full bg-orange-500 text-sm font-bold text-white">
            {user?.name.charAt(0).toUpperCase()}
          </div>
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-white">{user?.name}</p>
            <p className="truncate text-xs text-gray-500">{user?.email}</p>
          </div>
        </div>
        <button onClick={handleLogout} className="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-red-400 transition-colors hover:bg-red-900/30 hover:text-red-300">
          <LogOut className="h-4 w-4" /> Sign Out
        </button>
      </div>
    </aside>
  )
}
