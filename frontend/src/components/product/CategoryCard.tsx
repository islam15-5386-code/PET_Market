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
      className="flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-gray-200 hover:border-orange-300 hover:shadow-md transition-all group"
    >
      <div className="relative h-16 w-16 overflow-hidden rounded-2xl bg-orange-50">
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
        <p className="text-sm font-semibold text-gray-800 group-hover:text-orange-600 transition-colors">
          {name}
        </p>
        {productsCount != null && (
          <p className="text-xs text-gray-400 mt-0.5">{productsCount} items</p>
        )}
      </div>
    </Link>
  )
}
