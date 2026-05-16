'use client'

import Link from 'next/link'
import { useMemo, useState } from 'react'

interface CategoryCardProps {
  slug: string
  name: string
  icon: string | null
  imageUrl?: string | null
  productsCount?: number
}

export function CategoryCard({ slug, name, icon, imageUrl, productsCount }: CategoryCardProps) {
  const fallbackSrc = '/placeholder-product.png'
  const initialSrc = useMemo(() => imageUrl || fallbackSrc, [imageUrl])
  const [src, setSrc] = useState(initialSrc)
  const [showIconFallback, setShowIconFallback] = useState(false)

  return (
    <Link
      href={`/products?category=${slug}`}
      className="group flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_12px_24px_-24px_rgba(15,23,42,.8)] transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-[0_20px_38px_-24px_rgba(15,23,42,.6)]"
    >
      <div className="relative h-16 w-16 overflow-hidden rounded-2xl bg-amber-50">
        {showIconFallback ? (
          <span className="grid h-full place-items-center text-3xl">{icon ?? '🐾'}</span>
        ) : (
          <img
            src={src}
            alt={name}
            loading="lazy"
            className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200"
            onError={() => {
              if (src !== fallbackSrc) {
                setSrc(fallbackSrc)
                return
              }
              setShowIconFallback(true)
            }}
          />
        )}
      </div>
      <div className="text-center">
        <p className="text-sm font-semibold text-slate-800 transition-colors group-hover:text-amber-700">
          {name}
        </p>
        {productsCount != null && <p className="mt-0.5 text-xs text-slate-400">{productsCount} items</p>}
      </div>
    </Link>
  )
}
