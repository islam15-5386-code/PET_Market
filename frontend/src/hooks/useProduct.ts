'use client'

import { useEffect, useState } from 'react'
import { fetchProductBySlug } from '@/lib/products'
import type { ProductDetail } from '@/types'

export function useProduct(slug: string) {
  const [product, setProduct] = useState<ProductDetail | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!slug) return

    setLoading(true)
    setError(null)

    fetchProductBySlug(slug)
      .then(setProduct)
      .catch(() => setError('Product not found.'))
      .finally(() => setLoading(false))
  }, [slug])

  return { product, loading, error }
}
