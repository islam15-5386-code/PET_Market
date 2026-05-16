'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { ShoppingCart, MapPin, Package } from 'lucide-react'
import { clsx } from 'clsx'
import { useAuth } from '@/context/AuthContext'
import { useCart } from '@/hooks/useCart'
import { formatBDT } from '@/lib/currency'
import type { Product } from '@/types'

interface ProductCardProps {
  product: Product
}

const FALLBACK_IMAGE = '/placeholder-product.png'

export function ProductCard({ product }: ProductCardProps) {
  const { isAuthenticated } = useAuth()
  const { add } = useCart()
  const [adding, setAdding] = useState(false)
  const [added, setAdded] = useState(false)
  const firstImageFromArray = Array.isArray(product.images) ? product.images[0] : null
  const categoryFallback = product.category?.image_url || null
  const [imgSrc, setImgSrc] = useState<string | null>(
    product.image_url || product.primary_image || firstImageFromArray || categoryFallback || FALLBACK_IMAGE,
  )
  const [triedCategoryFallback, setTriedCategoryFallback] = useState(false)

  async function handleAddToCart(e: React.MouseEvent) {
    e.preventDefault() // don't navigate to product page

    if (!isAuthenticated) {
      window.location.href = '/login?redirect=/products'
      return
    }

    setAdding(true)
    try {
      await add(product.id, 1)
      setAdded(true)
      setTimeout(() => setAdded(false), 2000)
    } catch {
      // error handled in hook
    } finally {
      setAdding(false)
    }
  }

  const isOutOfStock = !product.is_available || product.stock_quantity === 0

  return (
    <Link
      href={`/products/${product.slug}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_12px_24px_-24px_rgba(15,23,42,.8)] transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-[0_20px_38px_-24px_rgba(15,23,42,.6)]"
    >
      {/* Image */}
      <div className="relative aspect-square overflow-hidden bg-slate-100">
        {imgSrc ? (
          <Image
            src={imgSrc}
            alt={product.name}
            fill
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
            onError={() => {
              // 1) Try category image once, 2) then show icon fallback
              if (
                !triedCategoryFallback &&
                categoryFallback &&
                imgSrc !== categoryFallback
              ) {
                setTriedCategoryFallback(true)
                setImgSrc(categoryFallback)
                return
              }
              setImgSrc(FALLBACK_IMAGE)
            }}
          />
        ) : (
          <div className="h-full flex items-center justify-center text-gray-300">
            <Package className="h-16 w-16" />
          </div>
        )}

        {isOutOfStock && (
          <div className="absolute inset-0 bg-gray-900/50 flex items-center justify-center">
            <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800">
              Out of Stock
            </span>
          </div>
        )}

        {/* Category badge */}
        {product.category && (
          <span className="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-xs font-medium text-slate-700 backdrop-blur-sm">
            {product.category.icon} {product.category.name}
          </span>
        )}
      </div>

      {/* Content */}
      <div className="flex flex-col flex-1 p-4 gap-2">
        <h3 className="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 transition-colors group-hover:text-amber-700">
          {product.name}
        </h3>

        {product.location && (
          <div className="flex items-center gap-1 text-xs text-slate-500">
            <MapPin className="h-3 w-3 shrink-0" />
            <span>{product.location}</span>
          </div>
        )}

        <div className="mt-auto flex items-center justify-between pt-2">
          <span className="text-base font-bold text-slate-900">
            {formatBDT(product.price)}
          </span>

          <button
            onClick={handleAddToCart}
            disabled={isOutOfStock || adding}
            aria-busy={adding}
            className={clsx(
              'flex h-8 items-center gap-1.5 rounded-xl px-3 text-xs font-semibold',
              'transform-gpu transition-[background-color,box-shadow,transform] duration-200 ease-out',
              added
                ? 'bg-green-500 text-white'
                : isOutOfStock
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-amber-600 text-white hover:bg-amber-700 hover:shadow-sm active:scale-[0.98]',
            )}
            aria-label="Add to cart"
          >
            <ShoppingCart className="h-3.5 w-3.5" />
            {added ? 'Added!' : adding ? 'Adding' : 'Add'}
          </button>
        </div>
      </div>
    </Link>
  )
}
