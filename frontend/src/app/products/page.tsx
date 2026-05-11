'use client'

import React, { Suspense, useCallback, useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { SlidersHorizontal, ChevronLeft, ChevronRight } from 'lucide-react'
import { ProductGrid } from '@/components/product/ProductGrid'
import { ProductFiltersPanel } from '@/components/product/ProductFilters'
import { useProducts } from '@/hooks/useProducts'
import { fetchAIProducts, type AIProductSearchResponse } from '@/lib/products'
import { getErrorMessage } from '@/lib/api'
import type { ProductFilters } from '@/types'

const SORT_OPTIONS = [
  { value: 'newest', label: 'Newest First' },
  { value: 'oldest', label: 'Oldest First' },
  { value: 'price_asc', label: 'Price: Low -> High' },
  { value: 'price_desc', label: 'Price: High -> Low' },
]

const AI_QUERY_SUGGESTIONS = [
  'I need good food for my kitten under 1000 BDT',
  'toy for my puppy under 500',
  'medicine for cat in Dhaka',
  'fish aquarium product below 1500',
  'dog collar under 800',
  'pet bed for small dog',
  'bird food in Mirpur',
  'cat grooming product under 1200',
]

function readFiltersFromURL(searchParams: URLSearchParams): ProductFilters {
  return {
    search: searchParams.get('search') || undefined,
    category: searchParams.get('category') || null,
    category_id: searchParams.get('category_id') ? Number(searchParams.get('category_id')) : null,
    min_price: searchParams.get('min_price') ? Number(searchParams.get('min_price')) : null,
    max_price: searchParams.get('max_price') ? Number(searchParams.get('max_price')) : null,
    location: searchParams.get('location') || undefined,
    sort: (searchParams.get('sort') as ProductFilters['sort']) || 'newest',
    page: searchParams.get('page') ? Number(searchParams.get('page')) : 1,
    per_page: 20,
  }
}

function ProductsPageContent() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [aiQuery, setAiQuery] = useState('')
  const [aiLoading, setAiLoading] = useState(false)
  const [aiError, setAiError] = useState<string | null>(null)
  const [aiResult, setAiResult] = useState<AIProductSearchResponse | null>(null)

  const filters = readFiltersFromURL(searchParams)
  const { products, meta, loading, error } = useProducts(filters)

  const updateFilters = useCallback(
    (updated: Partial<ProductFilters>) => {
      const next = { ...filters, ...updated }
      const params = new URLSearchParams()

      if (next.search) params.set('search', next.search)
      if (next.category) params.set('category', String(next.category))
      if (next.category_id) params.set('category_id', String(next.category_id))
      if (next.min_price != null) params.set('min_price', String(next.min_price))
      if (next.max_price != null) params.set('max_price', String(next.max_price))
      if (next.location) params.set('location', next.location)
      if (next.sort && next.sort !== 'newest') params.set('sort', next.sort)
      if (next.page && next.page > 1) params.set('page', String(next.page))

      router.push(`/products?${params.toString()}`, { scroll: false })
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [searchParams],
  )

  const resetFilters = useCallback(() => {
    router.push('/products')
  }, [router])

  const totalPages = meta?.last_page ?? 1
  const currentPage = meta?.current_page ?? 1

  const displayProducts = aiResult ? aiResult.products : products
  const displayLoading = aiLoading || loading
  const displayError = aiError ?? error

  async function runAISearch() {
    if (!aiQuery.trim()) return
    setAiLoading(true)
    setAiError(null)
    try {
      const res = await fetchAIProducts(aiQuery.trim())
      setAiResult(res)
    } catch (err) {
      setAiError(getErrorMessage(err))
      setAiResult(null)
    } finally {
      setAiLoading(false)
    }
  }

  async function runAISearchWithQuery(query: string) {
    setAiQuery(query)
    setAiLoading(true)
    setAiError(null)
    try {
      const res = await fetchAIProducts(query)
      setAiResult(res)
    } catch (err) {
      setAiError(getErrorMessage(err))
      setAiResult(null)
    } finally {
      setAiLoading(false)
    }
  }

  function clearAISearch() {
    setAiResult(null)
    setAiError(null)
    setAiQuery('')
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">All Products</h1>
          {meta && (
            <p className="text-sm text-gray-500 mt-0.5">
              {meta.total} product{meta.total !== 1 ? 's' : ''} found
            </p>
          )}
        </div>

        <div className="flex items-center gap-3">
          <select
            value={filters.sort ?? 'newest'}
            onChange={(e) => updateFilters({ sort: e.target.value as ProductFilters['sort'], page: 1 })}
            className="text-sm border border-gray-300 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400"
          >
            {SORT_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>
                {o.label}
              </option>
            ))}
          </select>

          <button
            onClick={() => setSidebarOpen(true)}
            className="lg:hidden flex items-center gap-2 text-sm font-medium border border-gray-300 rounded-xl px-3 py-2 hover:bg-gray-50"
          >
            <SlidersHorizontal className="h-4 w-4" />
            Filters
          </button>
        </div>
      </div>

      <div className="mb-4">
        <input
          type="search"
          placeholder="Search products..."
          defaultValue={filters.search ?? ''}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              updateFilters({ search: (e.target as HTMLInputElement).value || undefined, page: 1 })
            }
          }}
          className="w-full sm:max-w-sm rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
        />
      </div>

      <div className="mb-6 rounded-2xl border border-orange-200 bg-orange-50/50 p-4">
        <div className="flex flex-col gap-3 sm:flex-row">
          <input
            type="text"
            value={aiQuery}
            onChange={(e) => setAiQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') runAISearch()
            }}
            placeholder="Ask AI: I need food for my kitten under 1000 BDT"
            className="w-full rounded-xl border border-orange-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
          <button
            onClick={runAISearch}
            disabled={aiLoading || !aiQuery.trim()}
            className="rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-60"
          >
            {aiLoading ? 'Searching...' : 'AI Search'}
          </button>
          {aiResult && (
            <button
              onClick={clearAISearch}
              className="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
              Clear AI
            </button>
          )}
        </div>

        {aiResult && (
          <div className="mt-3 text-sm text-gray-700">
            <p>
              <span className="font-semibold">Category:</span> {aiResult.ai_filters.category ?? 'N/A'}
              <span className="ml-3 font-semibold">Pet:</span> {aiResult.ai_filters.pet_type ?? 'N/A'}
              <span className="ml-3 font-semibold">Budget:</span>{' '}
              {aiResult.ai_filters.max_price ? `Under ${aiResult.ai_filters.max_price} BDT` : 'N/A'}
            </p>
          </div>
        )}

        <div className="mt-3 flex flex-wrap gap-2">
          {AI_QUERY_SUGGESTIONS.map((q) => (
            <button
              key={q}
              type="button"
              onClick={() => runAISearchWithQuery(q)}
              className="rounded-full border border-orange-300 bg-white px-3 py-1.5 text-xs text-orange-700 hover:bg-orange-100"
            >
              {q}
            </button>
          ))}
        </div>
      </div>

      <div className="flex gap-8">
        <aside className="hidden lg:block w-60 shrink-0">
          <div className="sticky top-24 bg-white rounded-2xl border border-gray-200 p-5">
            <ProductFiltersPanel filters={filters} onChange={updateFilters} onReset={resetFilters} />
          </div>
        </aside>

        {sidebarOpen && (
          <div className="lg:hidden fixed inset-0 z-40 flex">
            <div className="fixed inset-0 bg-black/40" onClick={() => setSidebarOpen(false)} />
            <div className="relative z-50 w-72 max-w-full bg-white h-full overflow-y-auto p-5 shadow-xl ml-auto">
              <ProductFiltersPanel
                filters={filters}
                onChange={(f) => {
                  updateFilters(f)
                  setSidebarOpen(false)
                }}
                onReset={() => {
                  resetFilters()
                  setSidebarOpen(false)
                }}
                onClose={() => setSidebarOpen(false)}
              />
            </div>
          </div>
        )}

        <div className="flex-1 min-w-0">
          <ProductGrid products={displayProducts} loading={displayLoading} error={displayError} />

          {!aiResult && !displayLoading && totalPages > 1 && (
            <div className="flex items-center justify-center gap-2 mt-10">
              <button
                disabled={currentPage <= 1}
                onClick={() => updateFilters({ page: currentPage - 1 })}
                className="p-2 rounded-xl border border-gray-300 disabled:opacity-40 hover:bg-gray-50 disabled:cursor-not-allowed"
              >
                <ChevronLeft className="h-4 w-4" />
              </button>

              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .filter((p) => p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1)
                .reduce<(number | '...')[]>((acc, p, i, arr) => {
                  if (i > 0 && (arr[i - 1] as number) + 1 < p) acc.push('...')
                  acc.push(p)
                  return acc
                }, [])
                .map((item, i) =>
                  item === '...' ? (
                    <span key={`ellipsis-${i}`} className="px-2 text-gray-400">...</span>
                  ) : (
                    <button
                      key={item}
                      onClick={() => updateFilters({ page: item as number })}
                      className={`min-w-[36px] h-9 rounded-xl text-sm font-medium border transition-colors ${
                        currentPage === item
                          ? 'bg-orange-500 text-white border-orange-500'
                          : 'border-gray-300 hover:bg-gray-50'
                      }`}
                    >
                      {item}
                    </button>
                  ),
                )}

              <button
                disabled={currentPage >= totalPages}
                onClick={() => updateFilters({ page: currentPage + 1 })}
                className="p-2 rounded-xl border border-gray-300 disabled:opacity-40 hover:bg-gray-50 disabled:cursor-not-allowed"
              >
                <ChevronRight className="h-4 w-4" />
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default function ProductsPage() {
  return (
    <Suspense fallback={<div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" />}>
      <ProductsPageContent />
    </Suspense>
  )
}
