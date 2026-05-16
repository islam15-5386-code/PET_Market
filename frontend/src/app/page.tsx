'use client'

import React from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { ArrowRight, Bot, BrainCircuit, ShieldCheck, Sparkles, Store, Truck } from 'lucide-react'
import { CategoryCard } from '@/components/product/CategoryCard'
import { ProductCard } from '@/components/product/ProductCard'
import { Spinner } from '@/components/ui/Spinner'
import { Badge } from '@/components/ui/Badge'
import { AIChip } from '@/components/ui/AIChip'
import { useCategories } from '@/hooks/useCategories'
import { useProducts } from '@/hooks/useProducts'

export default function HomePage() {
  const { categories, loading: catLoading } = useCategories()
  const { products: featured, loading: prodLoading } = useProducts({ sort: 'newest', per_page: 8 })
  const heroImage =
    'https://images.pexels.com/photos/6568501/pexels-photo-6568501.jpeg?auto=compress&cs=tinysrgb&w=1600'
  const heroFallback = '/placeholder-product.png'

  return (
    <div className="pb-16">
      <section className="section-shell pt-12 sm:pt-16">
        <div className="motion-fade-up relative overflow-hidden rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-600 via-orange-500 to-orange-400 px-6 py-12 text-white shadow-2xl sm:px-10 sm:py-16">
          <Image
            src={heroImage}
            alt="Happy pets with premium pet care and shopping experience"
            fill
            priority
            className="object-cover opacity-25"
            onError={(e) => {
              e.currentTarget.src = heroFallback
            }}
          />
          <div className="absolute inset-0 bg-gradient-to-r from-orange-900/58 via-orange-700/45 to-amber-500/45" />

          <div className="relative z-10 grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
              <Badge variant="ai" className="mb-4 border-white/30 bg-white/20 text-white">AI-Enabled Marketplace</Badge>
              <h1 className="text-balance text-4xl font-extrabold leading-tight sm:text-5xl">
                AI-powered pet marketplace for smarter pet care and shopping.
              </h1>
              <p className="mt-4 max-w-xl text-base text-orange-50 sm:text-lg">
                Discover relevant products faster with AI search, get safe pet-care guidance from AI chatbot, and help sellers generate optimized product descriptions in seconds.
              </p>

              <div className="mt-6 flex flex-wrap gap-3">
                <Link href="/products" className="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-amber-700 transition hover:bg-amber-50">
                  Explore Products <ArrowRight className="h-4 w-4" />
                </Link>
                <Link href="/products" className="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                  Try AI Search
                </Link>
              </div>
              <div className="mt-4"><AIChip label="Try: kitten food under 1000 BDT" className="bg-white/90" /></div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              {[
                { icon: BrainCircuit, title: 'AI Smart Search', desc: 'Intent-aware filters + semantic relevance' },
                { icon: Bot, title: 'AI Pet Care Chatbot', desc: 'Safe advice + product recommendations' },
                { icon: Store, title: 'Seller Productivity', desc: 'AI-generated product descriptions' },
                { icon: ShieldCheck, title: 'Safety Focused', desc: 'Vet warning for health-risk queries' },
              ].map(({ icon: Icon, title, desc }) => (
                <div key={title} className="motion-fade-up rounded-2xl border border-white/30 bg-white/15 p-4 backdrop-blur-md">
                  <Icon className="h-5 w-5 text-white" />
                  <p className="mt-2 text-sm font-semibold text-white">{title}</p>
                  <p className="mt-1 text-xs text-orange-100">{desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="section-shell mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          { icon: Truck, title: 'Fast Delivery', desc: 'Quick delivery across Bangladesh' },
          { icon: ShieldCheck, title: 'Trusted Products', desc: 'Category-wise curated inventory' },
          { icon: Sparkles, title: 'AI-first Experience', desc: 'Search, chatbot, and content AI' },
          { icon: Store, title: 'Seller Friendly', desc: 'Admin tools for productivity' },
        ].map(({ icon: Icon, title, desc }) => (
          <div key={title} className="motion-fade-up dynamic-surface surface-card flex items-center gap-3 p-4 shadow-[0_12px_28px_-22px_rgba(15,23,42,.55)]">
            <div className="grid h-11 w-11 place-items-center rounded-xl bg-amber-100 text-amber-700"><Icon className="h-5 w-5" /></div>
            <div>
              <p className="text-sm font-semibold text-gray-900">{title}</p>
              <p className="text-xs text-gray-500">{desc}</p>
            </div>
          </div>
        ))}
      </section>

      <section className="section-shell mt-14">
        <div className="mb-5 flex items-center justify-between">
          <h2 className="text-2xl font-bold text-slate-900">Shop by Category</h2>
          <Link href="/products" className="text-sm font-medium text-amber-700 hover:text-amber-800">View all</Link>
        </div>
        {catLoading ? (
          <Spinner className="py-10" />
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            {categories.map((cat) => (
              <CategoryCard key={cat.id} slug={cat.slug} name={cat.name} icon={cat.icon} imageUrl={cat.image_url} productsCount={cat.products_count} />
            ))}
          </div>
        )}
      </section>

      <section className="section-shell mt-14">
        <div className="mb-5 flex items-center justify-between">
          <h2 className="text-2xl font-bold text-slate-900">Featured Products</h2>
          <Link href="/products?sort=newest" className="text-sm font-medium text-amber-700 hover:text-amber-800">See all</Link>
        </div>
        {prodLoading ? (
          <Spinner className="py-10" />
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {featured.map((product) => <ProductCard key={product.id} product={product} />)}
          </div>
        )}
      </section>

      <section className="section-shell mt-14">
        <div className="rounded-3xl border border-slate-200 bg-gradient-to-r from-sky-50 via-white to-amber-50 p-7 sm:p-9">
          <p className="text-sm font-semibold text-sky-700">Why choose Pet Marketplace</p>
          <h3 className="mt-2 text-2xl font-bold text-slate-900">Enterprise-grade marketplace experience with AI at every layer.</h3>
          <p className="mt-2 max-w-3xl text-sm text-slate-600">From product discovery to safe pet-care guidance and seller content generation, everything is designed to be fast, helpful, and production-ready.</p>
          <div className="mt-5 flex flex-wrap gap-3">
            <Link href="/products" className="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">Start Shopping</Link>
            <Link href="/register" className="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Create Account</Link>
          </div>
        </div>
      </section>
    </div>
  )
}
