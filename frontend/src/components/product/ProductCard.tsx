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
      className="group flex flex-col bg-white rounded-2xl border border-gray-200 hover:border-orange-200 hover:shadow-md transition-all duration-200 overflow-hidden"
    >
      {/* Image */}
      <div className="relative aspect-square bg-gray-100 overflow-hidden">
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
            <span className="bg-white text-gray-800 text-xs font-semibold px-3 py-1 rounded-full">
              Out of Stock
            </span>
          </div>
        )}

        {/* Category badge */}
        {product.category && (
          <span className="absolute top-2 left-2 bg-white/90 backdrop-blur-sm text-xs font-medium text-gray-700 px-2 py-0.5 rounded-full">
            {product.category.icon} {product.category.name}
          </span>
        )}
      </div>

      {/* Content */}
      <div className="flex flex-col flex-1 p-4 gap-2">
        <h3 className="text-sm font-semibold text-gray-900 line-clamp-2 leading-snug group-hover:text-orange-600 transition-colors">
          {product.name}
        </h3>

        {product.location && (
          <div className="flex items-center gap-1 text-xs text-gray-500">
            <MapPin className="h-3 w-3 shrink-0" />
            <span>{product.location}</span>
          </div>
        )}

        <div className="mt-auto flex items-center justify-between pt-2">
          <span className="text-base font-bold text-gray-900">
            {formatBDT(product.price)}
          </span>

          <button
            onClick={handleAddToCart}
            disabled={isOutOfStock || adding}
            className={clsx(
              'flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-xl transition-all',
              added
                ? 'bg-green-500 text-white'
                : isOutOfStock
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-orange-500 text-white hover:bg-orange-600 active:scale-95',
            )}
            aria-label="Add to cart"
          >
            <ShoppingCart className="h-3.5 w-3.5" />
            {added ? 'Added!' : adding ? '...' : 'Add'}
          </button>
        </div>
      </div>
    </Link>
  )
}
