'use client'

import { useEffect, useState } from 'react'
import { fetchProducts, type ProductListResponse } from '@/lib/products'
import type { ProductFilters } from '@/types'

export function useProducts(filters: ProductFilters = {}) {
  const [data, setData] = useState<ProductListResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // Serialize filters to a stable string so useEffect re-runs on change
  const filtersKey = JSON.stringify(filters)

  useEffect(() => {
    setLoading(true)
    setError(null)

    fetchProducts(filters)
      .then(setData)
      .catch(() => setError('Failed to load products.'))
      .finally(() => setLoading(false))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filtersKey])

  return {
    products: data?.products ?? [],
    meta: data?.meta ?? null,
    activeFilters: data?.filters ?? filters,
    loading,
    error,
  }
}
