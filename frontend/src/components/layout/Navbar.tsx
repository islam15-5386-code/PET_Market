'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { Menu, ShoppingCart, User, X, LogOut, LayoutDashboard, Sparkles } from 'lucide-react'
import { clsx } from 'clsx'
import { useAuth } from '@/context/AuthContext'
import { useCart } from '@/hooks/useCart'
import { AIChip } from '@/components/ui/AIChip'

export function Navbar() {
  const pathname = usePathname()
  const router = useRouter()
  const { isAuthenticated, isAdmin, user, logout } = useAuth()
  const { itemCount } = useCart()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)

  const navLinks = [
    { href: '/products', label: 'Shop' },
    { href: '/products', label: 'Categories' },
  ]

  async function handleLogout() {
    await logout()
    router.push('/login')
  }

  return (
    <header className="sticky top-0 z-50 border-b border-gray-200/70 bg-white/90 shadow-sm backdrop-blur">
      <div className="section-shell">
        <div className="flex h-16 items-center justify-between">
          <Link href="/" className="flex items-center gap-2 shrink-0">
            <span className="text-2xl">🐾</span>
            <div className="hidden sm:block">
              <span className="block text-lg font-extrabold tracking-tight text-gray-900">Pet Marketplace</span>
            </div>
          </Link>

          <nav className="hidden md:flex items-center gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-sm">
            {navLinks.map((link) => (
              <Link
                key={link.label}
                href={link.href}
                className={clsx(
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                  pathname.startsWith(link.href)
                    ? 'bg-orange-100 text-orange-700'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100',
                )}
              >
                {link.label}
              </Link>
            ))}
          </nav>

          <div className="hidden xl:block"><AIChip label="AI Marketplace" /></div>

          <div className="flex items-center gap-2">
            <Link
              href="/cart"
              className="relative p-2 rounded-xl text-gray-600 hover:text-orange-600 hover:bg-orange-50 transition-colors"
              aria-label="Cart"
            >
              <ShoppingCart className="h-5 w-5" />
              {itemCount > 0 && (
                <span className="absolute -top-0.5 -right-0.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold text-white">
                  {itemCount > 99 ? '99+' : itemCount}
                </span>
              )}
            </Link>

            {isAuthenticated ? (
              <div className="relative">
                <button
                  onClick={() => setUserMenuOpen((v) => !v)}
                  className="flex items-center gap-2 rounded-xl p-1.5 hover:bg-gray-100 transition-colors"
                >
                  <div className="grid h-8 w-8 place-items-center rounded-full bg-orange-100 text-orange-700 font-semibold text-sm">
                    {user?.name.charAt(0).toUpperCase()}
                  </div>
                </button>

                {userMenuOpen && (
                  <>
                    <div className="fixed inset-0 z-10" onClick={() => setUserMenuOpen(false)} />
                    <div className="absolute right-0 top-full mt-1 w-56 rounded-2xl border border-gray-100 bg-white py-1 shadow-xl z-20">
                      <div className="px-4 py-2 border-b border-gray-100">
                        <p className="text-sm font-semibold text-gray-900 truncate">{user?.name}</p>
                        <p className="text-xs text-gray-500 truncate">{user?.email}</p>
                      </div>

                      <Link href="/profile" onClick={() => setUserMenuOpen(false)} className="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        <User className="h-4 w-4" /> My Profile
                      </Link>
                      <Link href="/orders" onClick={() => setUserMenuOpen(false)} className="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        <ShoppingCart className="h-4 w-4" /> My Orders
                      </Link>

                      {isAdmin && (
                        <Link href="/admin" onClick={() => setUserMenuOpen(false)} className="flex items-center gap-2 px-4 py-2.5 text-sm text-indigo-700 hover:bg-indigo-50">
                          <LayoutDashboard className="h-4 w-4" /> Admin Panel
                        </Link>
                      )}

                      <div className="border-t border-gray-100 mt-1">
                        <button onClick={handleLogout} className="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                          <LogOut className="h-4 w-4" /> Sign Out
                        </button>
                      </div>
                    </div>
                  </>
                )}
              </div>
            ) : (
              <div className="hidden md:flex items-center gap-2">
                <Link href="/login" className="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-xl hover:bg-gray-100 transition-colors">
                  Sign In
                </Link>
                <Link href="/register" className="inline-flex items-center gap-1 text-sm font-semibold text-white bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 rounded-xl transition hover:from-orange-600 hover:to-amber-600">
                  <Sparkles className="h-3.5 w-3.5" /> Sign Up
                </Link>
              </div>
            )}

            <button
              onClick={() => setMobileOpen((v) => !v)}
              className="md:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100"
              aria-label="Toggle menu"
            >
              {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
          </div>
        </div>
      </div>

      {mobileOpen && (
        <div className="md:hidden border-t border-gray-100 bg-white px-4 py-3 flex flex-col gap-2">
          {navLinks.map((link) => (
            <Link key={link.label} href={link.href} onClick={() => setMobileOpen(false)} className="py-2 text-sm font-medium text-gray-700 hover:text-orange-600">
              {link.label}
            </Link>
          ))}
          {!isAuthenticated && (
            <>
              <Link href="/login" onClick={() => setMobileOpen(false)} className="py-2 text-sm font-medium text-gray-700">Sign In</Link>
              <Link href="/register" onClick={() => setMobileOpen(false)} className="py-2 text-sm font-medium text-orange-600">Create Account</Link>
            </>
          )}
        </div>
      )}
    </header>
  )
}
