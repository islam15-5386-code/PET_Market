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

// ── API calls ─────────────────────────────────────────────────────────────────

export async function fetchProducts(
  filters: ProductFilters = {},
): Promise<ProductListResponse> {
  const params = new URLSearchParams()

  if (filters.search) params.set('search', filters.search)
  if (filters.category_id) params.set('category_id', String(filters.category_id))
  if (filters.min_price != null) params.set('min_price', String(filters.min_price))
  if (filters.max_price != null) params.set('max_price', String(filters.max_price))
  if (filters.location) params.set('location', filters.location)
  if (filters.sort) params.set('sort', filters.sort)
  if (filters.per_page) params.set('per_page', String(filters.per_page))
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
