'use client'

import React, { Suspense, useCallback, useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { SlidersHorizontal, ChevronLeft, ChevronRight } from 'lucide-react'
import { ProductGrid } from '@/components/product/ProductGrid'
import { ProductFiltersPanel } from '@/components/product/ProductFilters'
import { useProducts } from '@/hooks/useProducts'
import { fetchAIProducts, type AIProductSearchResponse } from '@/lib/products'
import { getErrorMessage } from '@/lib/api'
import { AIChip } from '@/components/ui/AIChip'
import { Badge } from '@/components/ui/Badge'
import { PageHeader } from '@/components/ui/PageHeader'
import type { ProductFilters } from '@/types'

const SORT_OPTIONS = [
  { value: 'newest', label: 'Newest First' },
  { value: 'oldest', label: 'Oldest First' },
  { value: 'price_asc', label: 'Price: Low -> High' },
  { value: 'price_desc', label: 'Price: High -> Low' },
  { value: 'rating', label: 'Top Rated' },
]

const AI_QUERY_SUGGESTIONS = [
  'kitten food under 1000 BDT',
  'grooming items for Persian cat',
  'puppy shampoo in Dhaka',
  'bird food in Mirpur',
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

  const aiExact = aiResult?.exact_results ?? aiResult?.products ?? []
  const aiFallback = aiResult?.fallback_results ?? aiResult?.similar_products ?? []
  const aiDisplayProducts = aiResult ? (aiExact.length ? aiExact : aiFallback) : []
  const displayProducts = aiResult ? aiDisplayProducts : products
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
    <div className="section-shell py-8">
      <PageHeader
        title="All Products"
        description={meta ? `${meta.total} products available in marketplace` : 'Browse the complete catalog'}
        actions={
          <div className="flex items-center gap-2">
            <select
              value={filters.sort ?? 'newest'}
              onChange={(e) => updateFilters({ sort: e.target.value as ProductFilters['sort'], page: 1 })}
              className="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
            >
              {SORT_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>

            <button onClick={() => setSidebarOpen(true)} className="lg:hidden inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium shadow-sm hover:bg-gray-50">
              <SlidersHorizontal className="h-4 w-4" /> Filters
            </button>
          </div>
        }
      />

      <div className="mb-4">
        <input
          type="search"
          placeholder="Search products by name, brand, location..."
          defaultValue={filters.search ?? ''}
          onKeyDown={(e) => {
            if (e.key === 'Enter') updateFilters({ search: (e.target as HTMLInputElement).value || undefined, page: 1 })
          }}
          className="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-orange-300 focus:ring-2 focus:ring-orange-200 sm:max-w-xl"
        />
      </div>

      <div className="mb-6 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-orange-50 p-4">
        <div className="mb-3 flex items-center justify-between"><AIChip label="AI Product Search" />{aiResult && <Badge variant="ai">Result Mode: {aiResult.ai_filters.semantic_applied ? 'Semantic + Filter' : 'Filter + Keyword'}</Badge>}</div>
        <div className="flex flex-col gap-3 sm:flex-row">
          <input
            type="text"
            value={aiQuery}
            onChange={(e) => setAiQuery(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter') runAISearch() }}
            placeholder="Try: kitten food under 1000 BDT"
            className="w-full rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm focus:border-indigo-300 focus:ring-2 focus:ring-indigo-200"
          />
          <button onClick={runAISearch} disabled={aiLoading || !aiQuery.trim()} className="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
            {aiLoading ? 'Searching...' : 'Run AI Search'}
          </button>
          {aiResult && <button onClick={clearAISearch} className="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</button>}
        </div>

        {aiResult && (
          <div className="mt-3 flex flex-wrap gap-2 text-xs">
            <Badge variant="brand">Category: {aiResult.ai_filters.category ?? 'N/A'}</Badge>
            <Badge variant="brand">Pet: {aiResult.ai_filters.pet_type ?? 'N/A'}</Badge>
            <Badge variant="brand">Age: {aiResult.ai_filters.age_group ?? 'N/A'}</Badge>
            <Badge variant="brand">Budget: {aiResult.ai_filters.price_max ? `<= ${aiResult.ai_filters.price_max} BDT` : 'N/A'}</Badge>
            <Badge variant="brand">Location: {aiResult.ai_filters.location ?? 'N/A'}</Badge>
          </div>
        )}
        {aiResult?.ai_filters?.location && (
          <p className="mt-2 text-xs text-gray-600">Showing products in {aiResult.ai_filters.location}</p>
        )}
        {aiResult?.result_mode === 'fallback' && (
          <div className="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            {aiResult.message || 'No exact location match found. Showing similar products from other locations.'}
          </div>
        )}

        <div className="mt-3 flex flex-wrap gap-2">
          {AI_QUERY_SUGGESTIONS.map((q) => (
            <button key={q} type="button" onClick={() => runAISearchWithQuery(q)} className="rounded-full border border-indigo-200 bg-white px-3 py-1.5 text-xs text-indigo-700 hover:bg-indigo-50">
              {q}
            </button>
          ))}
        </div>
      </div>

      <div className="flex gap-8">
        <aside className="hidden w-64 shrink-0 lg:block">
          <div className="surface-card sticky top-24 p-5">
            <ProductFiltersPanel filters={filters} onChange={updateFilters} onReset={resetFilters} />
          </div>
        </aside>

        {sidebarOpen && (
          <div className="fixed inset-0 z-40 flex lg:hidden">
            <div className="fixed inset-0 bg-black/40" onClick={() => setSidebarOpen(false)} />
            <div className="relative z-50 ml-auto h-full w-80 max-w-full overflow-y-auto bg-white p-5 shadow-xl">
              <ProductFiltersPanel
                filters={filters}
                onChange={(f) => { updateFilters(f); setSidebarOpen(false) }}
                onReset={() => { resetFilters(); setSidebarOpen(false) }}
                onClose={() => setSidebarOpen(false)}
              />
            </div>
          </div>
        )}

        <div className="min-w-0 flex-1">
          {aiResult && aiExact.length > 0 && (
            <p className="mb-3 text-xs font-medium text-emerald-700">Exact matches</p>
          )}
          {aiResult && aiExact.length === 0 && aiFallback.length > 0 && (
            <p className="mb-3 text-xs font-medium text-amber-700">Similar products from other locations</p>
          )}
          <ProductGrid products={displayProducts} loading={displayLoading} error={displayError} />

          {!aiResult && !displayLoading && totalPages > 1 && (
            <div className="mt-10 flex items-center justify-center gap-2">
              <button disabled={currentPage <= 1} onClick={() => updateFilters({ page: currentPage - 1 })} className="rounded-xl border border-gray-300 p-2 hover:bg-gray-50 disabled:opacity-40">
                <ChevronLeft className="h-4 w-4" />
              </button>

              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .filter((p) => p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1)
                .reduce<(number | '...')[]>((acc, p, i, arr) => { if (i > 0 && (arr[i - 1] as number) + 1 < p) acc.push('...'); acc.push(p); return acc }, [])
                .map((item, i) =>
                  item === '...' ? (
                    <span key={`ellipsis-${i}`} className="px-2 text-gray-400">...</span>
                  ) : (
                    <button key={item} onClick={() => updateFilters({ page: item as number })} className={`min-w-[36px] h-9 rounded-xl text-sm font-medium border ${currentPage === item ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-300 hover:bg-gray-50'}`}>
                      {item}
                    </button>
                  ),
                )}

              <button disabled={currentPage >= totalPages} onClick={() => updateFilters({ page: currentPage + 1 })} className="rounded-xl border border-gray-300 p-2 hover:bg-gray-50 disabled:opacity-40">
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
    <Suspense fallback={<div className="section-shell py-8" />}>
      <ProductsPageContent />
    </Suspense>
  )
}
