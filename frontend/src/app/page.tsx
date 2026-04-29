'use client'

import React from 'react'
import Link from 'next/link'
import { ArrowRight, ShieldCheck, Truck, HeartHandshake } from 'lucide-react'
import { CategoryCard } from '@/components/product/CategoryCard'
import { ProductCard } from '@/components/product/ProductCard'
import { Spinner } from '@/components/ui/Spinner'
import { useCategories } from '@/hooks/useCategories'
import { useProducts } from '@/hooks/useProducts'

export default function HomePage() {
  const { categories, loading: catLoading } = useCategories()
  const { products: featured, loading: prodLoading } = useProducts({
    sort: 'newest',
    per_page: 8,
  })

  return (
    <div className="flex flex-col gap-16 pb-16">

      {/* ── Hero ──────────────────────────────────────────────────────────── */}
      <section className="bg-gradient-to-br from-orange-500 to-amber-500 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex flex-col items-center text-center gap-6">
          <span className="text-6xl animate-bounce">🐾</span>
          <h1 className="text-4xl sm:text-5xl font-extrabold leading-tight text-balance">
            Everything Your Pet Loves
          </h1>
          <p className="max-w-xl text-lg text-orange-100">
            Bangladesh&apos;s trusted marketplace for pet food, accessories, and health products. 
            Quality brands, delivered fast.
          </p>
          <div className="flex flex-wrap gap-3 justify-center">
            <Link
              href="/products"
              className="inline-flex items-center gap-2 bg-white text-orange-600 font-bold px-6 py-3 rounded-xl hover:bg-orange-50 transition-colors"
            >
              Shop Now <ArrowRight className="h-4 w-4" />
            </Link>
            <Link
              href="/register"
              className="inline-flex items-center gap-2 bg-orange-600/30 text-white font-semibold px-6 py-3 rounded-xl hover:bg-orange-600/50 transition-colors border border-white/30"
            >
              Create Account
            </Link>
          </div>
        </div>
      </section>

      {/* ── Trust badges ───────────────────────────────────────────────── */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {[
            { icon: Truck, title: 'Fast Delivery', desc: 'Free shipping on orders over ৳2000' },
            { icon: ShieldCheck, title: 'Quality Assured', desc: 'All products verified & authentic' },
            { icon: HeartHandshake, title: 'Pet-First Service', desc: 'Expert advice for your furry friends' },
          ].map(({ icon: Icon, title, desc }) => (
            <div key={title} className="flex items-center gap-4 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
              <div className="h-12 w-12 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                <Icon className="h-6 w-6 text-orange-600" />
              </div>
              <div>
                <p className="font-semibold text-gray-900 text-sm">{title}</p>
                <p className="text-xs text-gray-500 mt-0.5">{desc}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ── Shop by Category ──────────────────────────────────────────── */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Shop by Category</h2>
          <Link href="/products" className="text-sm font-medium text-orange-600 hover:text-orange-700 flex items-center gap-1">
            View all <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        {catLoading ? (
          <Spinner className="py-12" />
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {categories.map((cat) => (
              <CategoryCard
                key={cat.id}
                id={cat.id}
                name={cat.name}
                icon={cat.icon}
                productsCount={cat.products_count}
              />
            ))}
          </div>
        )}
      </section>

      {/* ── Featured Products ─────────────────────────────────────────── */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">New Arrivals</h2>
          <Link href="/products?sort=newest" className="text-sm font-medium text-orange-600 hover:text-orange-700 flex items-center gap-1">
            See all <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        {prodLoading ? (
          <Spinner className="py-12" />
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {featured.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </section>

    </div>
  )
}
