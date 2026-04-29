import api from '../api'
import type { ApiResponse, PaginatedMeta } from '@/types'

export interface AdminProduct {
  id: number
  name: string
  slug: string
  description: string | null
  price: string
  stock_quantity: number
  is_available: boolean
  images: string[]
  primary_image: string | null
  location: string | null
  category: { id: number; name: string; icon: string | null } | null
  deleted_at: string | null
  created_at: string
  updated_at: string
}

export interface AdminProductListResponse {
  products: AdminProduct[]
  meta: PaginatedMeta
}

export interface ProductFormInput {
  category_id: number
  name: string
  description?: string
  price: number
  stock_quantity: number
  location?: string
  is_available?: boolean
}

export async function fetchAdminProducts(params: {
  search?: string
  category_id?: number | null
  is_available?: boolean | null
  per_page?: number
  page?: number
}): Promise<AdminProductListResponse> {
  const q = new URLSearchParams()
  if (params.search) q.set('search', params.search)
  if (params.category_id) q.set('category_id', String(params.category_id))
  if (params.is_available != null) q.set('is_available', String(params.is_available))
  if (params.per_page) q.set('per_page', String(params.per_page))
  if (params.page && params.page > 1) q.set('page', String(params.page))

  const { data } = await api.get<ApiResponse<AdminProductListResponse>>(
    `/admin/products?${q.toString()}`,
  )
  return data.data!
}

export async function fetchAdminProduct(id: number): Promise<AdminProduct> {
  const { data } = await api.get<ApiResponse<{ product: AdminProduct }>>(
    `/admin/products/${id}`,
  )
  return data.data!.product
}

export async function createProduct(input: ProductFormInput): Promise<AdminProduct> {
  const { data } = await api.post<ApiResponse<{ product: AdminProduct }>>(
    '/admin/products',
    input,
  )
  return data.data!.product
}

export async function updateProduct(
  id: number,
  input: Partial<ProductFormInput>,
): Promise<AdminProduct> {
  const { data } = await api.put<ApiResponse<{ product: AdminProduct }>>(
    `/admin/products/${id}`,
    input,
  )
  return data.data!.product
}

export async function deleteProduct(id: number): Promise<void> {
  await api.delete(`/admin/products/${id}`)
}

export async function uploadProductImages(
  id: number,
  files: File[],
): Promise<AdminProduct> {
  const form = new FormData()
  files.forEach((f) => form.append('images[]', f))
  const { data } = await api.post<ApiResponse<{ product: AdminProduct }>>(
    `/admin/products/${id}/images`,
    form,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  )
  return data.data!.product
}

export async function deleteProductImage(
  id: number,
  index: number,
): Promise<AdminProduct> {
  const { data } = await api.delete<ApiResponse<{ product: AdminProduct }>>(
    `/admin/products/${id}/images/${index}`,
  )
  return data.data!.product
}
