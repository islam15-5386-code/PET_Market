import React from 'react'
import Link from 'next/link'
import Image from 'next/image'
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
          <div className="relative mb-8 h-[340px] overflow-hidden rounded-3xl border border-orange-100 shadow-xl">
            <Image
              src="https://images.pexels.com/photos/13430758/pexels-photo-13430758.jpeg?auto=compress&cs=tinysrgb&w=1600"
              alt="Pet care at marketplace"
              fill
              priority
              className="object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-gray-900/35 via-gray-900/10 to-transparent" />
            <div className="absolute bottom-4 left-4 rounded-xl bg-white/85 px-4 py-2 backdrop-blur">
              <p className="text-sm font-semibold text-gray-900">Smart Care, Happy Pets</p>
              <p className="text-xs text-gray-600">Shop, consult, and manage in one place.</p>
            </div>
          </div>
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
