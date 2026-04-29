import React from 'react'
import Link from 'next/link'

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 via-white to-amber-50 flex flex-col items-center justify-center px-4 py-12">
      {/* Brand header */}
      <Link href="/" className="mb-8 flex items-center gap-2 group">
        <span className="text-3xl">🐾</span>
        <span className="text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
          Pet Marketplace
        </span>
      </Link>

      {/* Page content (the auth card) */}
      {children}

      {/* Footer */}
      <p className="mt-8 text-xs text-gray-400">
        &copy; {new Date().getFullYear()} Pet Marketplace by Betopia Limited
      </p>
    </div>
  )
}
