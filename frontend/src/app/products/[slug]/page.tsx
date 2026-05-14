'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import { useParams, useRouter } from 'next/navigation'
import {
  ChevronRight,
  MapPin,
  Minus,
  Package,
  Plus,
  ShoppingCart,
} from 'lucide-react'
import { ProductImageGallery } from '@/components/product/ProductImageGallery'
import { ProductCard } from '@/components/product/ProductCard'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Spinner } from '@/components/ui/Spinner'
import { useProduct } from '@/hooks/useProduct'
import { useProducts } from '@/hooks/useProducts'
import { useAuth } from '@/context/AuthContext'
import { useCart } from '@/hooks/useCart'
import { formatBDT } from '@/lib/currency'

export default function ProductDetailPage() {
  const params = useParams()
  const router = useRouter()
  const slug = params.slug as string

  const { product, loading, error } = useProduct(slug)
  const { isAuthenticated } = useAuth()
  const { add } = useCart()

  const [quantity, setQuantity] = useState(1)
  const [adding, setAdding] = useState(false)
  const [added, setAdded] = useState(false)
  const [cartError, setCartError] = useState('')

  // Related products — same category, exclude current
  const { products: related } = useProducts({
    category_id: product?.category?.id ?? null,
    per_page: 4,
  })
  const relatedFiltered = related.filter((p) => p.slug !== slug).slice(0, 4)

  async function handleAddToCart() {
    if (!isAuthenticated) {
      router.push(`/login?redirect=/products/${slug}`)
      return
    }

    setAdding(true)
    setCartError('')

    try {
      await add(product!.id, quantity)
      setAdded(true)
      setTimeout(() => setAdded(false), 2500)
    } catch (err: any) {
      setCartError(err.message ?? 'Failed to add to cart.')
    } finally {
      setAdding(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  if (error || !product) {
    return (
      <div className="max-w-7xl mx-auto px-4 py-16 text-center">
        <Package className="h-16 w-16 text-gray-300 mx-auto mb-4" />
        <h1 className="text-xl font-bold text-gray-800">Product not found</h1>
        <p className="text-gray-500 mt-2">
          This product may have been removed or is no longer available.
        </p>
        <Link href="/products" className="mt-6 inline-block link-brand">
          Browse all products
        </Link>
      </div>
    )
  }

  const isOutOfStock = !product.is_available || product.stock_quantity === 0
  const maxQty = product.stock_quantity

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      {/* Breadcrumb */}
      <nav className="flex items-center gap-1.5 text-sm text-gray-500 mb-6">
        <Link href="/" className="hover:text-gray-700">Home</Link>
        <ChevronRight className="h-3.5 w-3.5" />
        <Link href="/products" className="hover:text-gray-700">Products</Link>
        {product.category && (
          <>
            <ChevronRight className="h-3.5 w-3.5" />
            <Link
              href={`/products?category_id=${product.category.id}`}
              className="hover:text-gray-700"
            >
              {product.category.name}
            </Link>
          </>
        )}
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="text-gray-900 font-medium truncate max-w-[200px]">
          {product.name}
        </span>
      </nav>

      {/* Main content */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">

        {/* Gallery */}
        <ProductImageGallery images={product.images} productName={product.name} />

        {/* Details */}
        <div className="flex flex-col gap-5">
          {/* Category */}
          {product.category && (
            <Link
              href={`/products?category_id=${product.category.id}`}
              className="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 hover:text-orange-700 w-fit"
            >
              <span>{product.category.icon}</span>
              {product.category.name}
            </Link>
          )}

          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug">
            {product.name}
          </h1>

          <div className="flex items-baseline gap-3">
            <span className="text-3xl font-extrabold text-gray-900">
              {formatBDT(product.price)}
            </span>
          </div>

          {product.location && (
            <div className="flex items-center gap-1.5 text-sm text-gray-500">
              <MapPin className="h-4 w-4" />
              {product.location}
            </div>
          )}

          {/* Stock */}
          <div>
            {isOutOfStock ? (
              <span className="inline-flex items-center gap-1.5 text-sm font-medium text-red-600 bg-red-50 px-3 py-1.5 rounded-full">
                Out of Stock
              </span>
            ) : (
              <span className="inline-flex items-center gap-1.5 text-sm font-medium text-green-700 bg-green-50 px-3 py-1.5 rounded-full">
                In Stock — {product.stock_quantity} available
              </span>
            )}
          </div>

          {/* Description */}
          {product.description && (
            <p className="text-gray-600 leading-relaxed text-sm">
              {product.description}
            </p>
          )}

          {cartError && <Alert variant="error" message={cartError} />}

          {/* Quantity + Add to cart */}
          {!isOutOfStock && (
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-2">
              {/* Quantity selector */}
              <div className="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                <button
                  onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                  className="px-3 py-2.5 hover:bg-gray-100 transition-colors"
                  aria-label="Decrease quantity"
                >
                  <Minus className="h-4 w-4 text-gray-600" />
                </button>
                <span className="w-12 text-center text-sm font-semibold text-gray-900">
                  {quantity}
                </span>
                <button
                  onClick={() => setQuantity((q) => Math.min(maxQty, q + 1))}
                  className="px-3 py-2.5 hover:bg-gray-100 transition-colors"
                  aria-label="Increase quantity"
                >
                  <Plus className="h-4 w-4 text-gray-600" />
                </button>
              </div>

              <Button
                size="lg"
                onClick={handleAddToCart}
                loading={adding}
                className="flex-1 sm:flex-none"
                variant={added ? 'secondary' : 'primary'}
              >
                <ShoppingCart className="h-4 w-4" />
                {added ? 'Added to Cart!' : 'Add to Cart'}
              </Button>
            </div>
          )}
        </div>
      </div>

      {/* Related products */}
      {relatedFiltered.length > 0 && (
        <section className="mt-16">
          <h2 className="text-xl font-bold text-gray-900 mb-6">
            More from {product.category?.name}
          </h2>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {relatedFiltered.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </section>
      )}
    </div>
  )
}
