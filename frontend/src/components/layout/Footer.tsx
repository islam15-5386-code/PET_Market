import Link from 'next/link'
import { AIChip } from '@/components/ui/AIChip'

export function Footer() {
  return (
    <footer className="mt-auto border-t border-gray-200 bg-[#111827] text-gray-300">
      <div className="section-shell py-12">
        <div className="grid grid-cols-1 gap-8 lg:grid-cols-4">
          <div className="lg:col-span-2">
            <div className="mb-3 flex items-center gap-2">
              <span className="text-2xl">🐾</span>
              <span className="text-lg font-bold text-white">Pet Marketplace</span>
            </div>
            <p className="max-w-xl text-sm leading-relaxed text-gray-400">
              AI-powered pet commerce platform for smarter product discovery, safer pet-care support, and faster seller workflows.
            </p>
            <div className="mt-4"><AIChip label="AI Smart Search · AI Chatbot · AI Description" /></div>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold text-white">Marketplace</h3>
            <ul className="space-y-2 text-sm">
              <li><Link href="/products" className="hover:text-white transition-colors">All Products</Link></li>
              <li><Link href="/products?sort=newest" className="hover:text-white transition-colors">New Arrivals</Link></li>
              <li><Link href="/products" className="hover:text-white transition-colors">AI Product Search</Link></li>
            </ul>
          </div>

          <div>
            <h3 className="mb-3 text-sm font-semibold text-white">Account</h3>
            <ul className="space-y-2 text-sm">
              <li><Link href="/login" className="hover:text-white transition-colors">Sign In</Link></li>
              <li><Link href="/register" className="hover:text-white transition-colors">Create Account</Link></li>
              <li><Link href="/orders" className="hover:text-white transition-colors">My Orders</Link></li>
              <li><Link href="/profile" className="hover:text-white transition-colors">Profile</Link></li>
            </ul>
          </div>
        </div>

        <div className="mt-8 flex flex-col gap-2 border-t border-gray-800 pt-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
          <p>&copy; {new Date().getFullYear()} Pet Marketplace by Betopia Limited. All rights reserved.</p>
          <p>Built for modern pet care in Bangladesh</p>
        </div>
      </div>
    </footer>
  )
}
