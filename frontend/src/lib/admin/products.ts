import api from '../api'
import type { ApiResponse, PaginatedMeta } from '@/types'

export interface AdminProduct {
  id: number
  name: string
  slug: string
  description: string | null
  ai_generated_title?: string | null
  ai_generated_short_description?: string | null
  ai_generated_long_description?: string | null
  ai_seo_keywords?: string[]
  ai_meta_title?: string | null
  ai_meta_description?: string | null
  ai_generated_tags?: string[]
  ai_content_generated_at?: string | null
  price: string
  stock_quantity: number
  is_available: boolean
  images: string[]
  primary_image: string | null
  image_url?: string | null
  thumbnail_url?: string | null
  location: string | null
  brand: string | null
  pet_type?: string | null
  sub_category?: string | null
  category_type?: string | null
  sku?: string | null
  rating?: number
  review_count?: number
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
  brand?: string
  ai_generated_title?: string
  ai_generated_short_description?: string
  ai_generated_long_description?: string
  ai_seo_keywords?: string[]
  ai_meta_title?: string
  ai_meta_description?: string
  ai_generated_tags?: string[]
}

export interface AIDescriptionGenerateInput {
  product_id?: number
  product_name: string
  category: string
  pet_type: string
  age_group?: string
  brand?: string
  price?: number
  weight_or_size?: string
  ingredients_or_materials?: string[] | string
  key_features?: string[] | string
  usage_instruction?: string
  safety_note?: string
  target_customer?: string
  language: 'English' | 'Bangla' | 'Bangla-English mixed'
  tone: 'professional' | 'friendly' | 'SEO optimized'
}

export interface AIGeneratedDescription {
  professional_product_title: string
  short_description: string
  long_description: string
  seo_keywords: string[]
  benefits: string[]
  care_instruction: string
  usage_instruction: string
  safety_warning: string
  meta_title: string
  meta_description: string
  suggested_tags: string[]
  provider_name: string
  model_name: string
  token_usage: {
    prompt_tokens: number
    completion_tokens: number
    total_tokens: number
  }
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

export async function generateProductDescription(
  payload: AIDescriptionGenerateInput,
): Promise<AIGeneratedDescription> {
  const { data } = await api.post<
    ApiResponse<AIGeneratedDescription>
  >('/ai/product-description/generate', payload)
  return data.data!
}
