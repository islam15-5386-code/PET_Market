'use client'

import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import {
  LayoutDashboard,
  Package,
  Tag,
  Users,
  ShoppingBag,
  LogOut,
  ChevronRight,
} from 'lucide-react'
import { clsx } from 'clsx'
import { useAuth } from '@/context/AuthContext'

const NAV = [
  { href: '/admin/dashboard',  icon: LayoutDashboard, label: 'Dashboard' },
  { href: '/admin/products',   icon: Package,         label: 'Products' },
  { href: '/admin/categories', icon: Tag,             label: 'Categories' },
  { href: '/admin/users',      icon: Users,           label: 'Users' },
  { href: '/admin/orders',     icon: ShoppingBag,     label: 'Orders' },
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
    <aside className="flex flex-col w-60 min-h-screen bg-gray-900 text-gray-300 shrink-0">
      {/* Brand */}
      <div className="flex items-center gap-2 px-5 py-5 border-b border-gray-800">
        <span className="text-2xl">🐾</span>
        <div>
          <p className="text-sm font-bold text-white leading-none">Pet Marketplace</p>
          <p className="text-xs text-orange-400 mt-0.5">Admin Panel</p>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 px-3 py-4 flex flex-col gap-1">
        {NAV.map(({ href, icon: Icon, label }) => {
          const active = pathname.startsWith(href)
          return (
            <Link
              key={href}
              href={href}
              className={clsx(
                'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                active
                  ? 'bg-orange-500 text-white'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white',
              )}
            >
              <span className="flex items-center gap-3">
                <Icon className="h-4 w-4" />
                {label}
              </span>
              {active && <ChevronRight className="h-3.5 w-3.5" />}
            </Link>
          )
        })}
      </nav>

      {/* User + logout */}
      <div className="px-3 py-4 border-t border-gray-800">
        <div className="flex items-center gap-3 px-3 py-2 mb-1">
          <div className="h-8 w-8 rounded-full bg-orange-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
            {user?.name.charAt(0).toUpperCase()}
          </div>
          <div className="min-w-0">
            <p className="text-sm font-medium text-white truncate">{user?.name}</p>
            <p className="text-xs text-gray-500 truncate">{user?.email}</p>
          </div>
        </div>
        <button
          onClick={handleLogout}
          className="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-900/30 hover:text-red-300 transition-colors"
        >
          <LogOut className="h-4 w-4" />
          Sign Out
        </button>
      </div>
    </aside>
  )
}
