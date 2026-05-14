import React from 'react'
import Link from 'next/link'
import { AIChip } from '@/components/ui/AIChip'

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 via-white to-indigo-50">
      <div className="section-shell grid min-h-screen items-center gap-10 py-12 lg:grid-cols-2">
        <div className="hidden lg:block">
          <Link href="/" className="mb-6 inline-flex items-center gap-2">
            <span className="text-3xl">🐾</span>
            <span className="text-2xl font-bold tracking-tight text-gray-900">Pet Marketplace</span>
          </Link>
          <h1 className="text-4xl font-extrabold leading-tight text-gray-900">Welcome to a smarter pet marketplace.</h1>
          <p className="mt-4 max-w-lg text-gray-600">Shop faster with AI search, get pet-care help instantly, and manage your orders with confidence.</p>
          <div className="mt-5"><AIChip label="AI Search · AI Chatbot · AI Assistant" /></div>
        </div>

        <div>
          <Link href="/" className="mb-6 flex items-center justify-center gap-2 lg:hidden">
            <span className="text-3xl">🐾</span>
            <span className="text-2xl font-bold text-gray-900">Pet Marketplace</span>
          </Link>
          {children}
          <p className="mt-6 text-center text-xs text-gray-400">&copy; {new Date().getFullYear()} Pet Marketplace by Betopia Limited</p>
        </div>
      </div>
    </div>
  )
}
