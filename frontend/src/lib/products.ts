import api from './api'
import type {
  ApiResponse,
  PaginatedMeta,
  Product,
  ProductDetail,
  ProductFilters,
} from '@/types'

// ── Response types ────────────────────────────────────────────────────────────

export interface ProductListResponse {
  products: Product[]
  meta: PaginatedMeta
  filters: ProductFilters
}

export interface AIProductSearchResponse {
  query: string
  ai_filters: {
    pet_type?: string | null
    age_group?: string | null
    category?: string | null
    price_max?: number | null
    price_min?: number | null
    brand?: string | null
    location?: string | null
    breed?: string | null
    keywords?: string[]
    confidence?: number
    semantic_applied?: boolean
    semantic_weight?: number
  }
  products: Product[]
  exact_results?: Product[]
  fallback_results?: Product[]
  similar_products?: Product[]
  result_mode?: 'exact' | 'fallback' | 'mixed'
  message?: string
}

export interface AISuggestionsResponse {
  suggestions: string[]
}

// ── API calls ─────────────────────────────────────────────────────────────────

export async function fetchProducts(
  filters: ProductFilters = {},
): Promise<ProductListResponse> {
  const params = new URLSearchParams()

  if (filters.search) params.set('search', filters.search)
  if (filters.category) params.set('category', filters.category)
  if (filters.category_id) params.set('category_id', String(filters.category_id))
  if (filters.min_price != null) params.set('min_price', String(filters.min_price))
  if (filters.max_price != null) params.set('max_price', String(filters.max_price))
  if (filters.location) params.set('location', filters.location)
  if (filters.pet_type) params.set('pet_type', String(filters.pet_type))
  if (filters.age_group) params.set('age_group', String(filters.age_group))
  if (filters.sort) params.set('sort', filters.sort)
  if (filters.per_page) {
    params.set('per_page', String(filters.per_page))
    params.set('limit', String(filters.per_page))
  }
  if (filters.page && filters.page > 1) params.set('page', String(filters.page))

  const { data } = await api.get<ApiResponse<ProductListResponse>>(
    `/products?${params.toString()}`,
  )
  return data.data!
}

export async function fetchProductBySlug(slug: string): Promise<ProductDetail> {
  const { data } = await api.get<ApiResponse<{ product: ProductDetail }>>(
    `/products/${slug}`,
  )
  return data.data!.product
}

export async function fetchAIProducts(query: string): Promise<AIProductSearchResponse> {
  const { data } = await api.post<ApiResponse<AIProductSearchResponse>>(
    '/ai-search',
    { query },
  )
  return data.data!
}

export async function fetchAISuggestions(): Promise<string[]> {
  const { data } = await api.get<ApiResponse<AISuggestionsResponse>>('/ai/suggestions')
  return data.data?.suggestions ?? []
}
