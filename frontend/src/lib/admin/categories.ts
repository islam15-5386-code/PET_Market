import api from '../api'
import type { ApiResponse, Category } from '@/types'

export async function fetchAdminCategories(): Promise<Category[]> {
  const { data } = await api.get<ApiResponse<{ categories: Category[] }>>('/categories')
  return data.data!.categories
}

export async function createCategory(payload: {
  name: string
  description?: string
  icon?: string
}): Promise<Category> {
  const { data } = await api.post<ApiResponse<{ category: Category }>>(
    '/admin/categories',
    payload,
  )
  return data.data!.category
}

export async function updateCategory(
  id: number,
  payload: { name?: string; description?: string; icon?: string },
): Promise<Category> {
  const { data } = await api.put<ApiResponse<{ category: Category }>>(
    `/admin/categories/${id}`,
    payload,
  )
  return data.data!.category
}

export async function deleteCategory(id: number): Promise<void> {
  await api.delete(`/admin/categories/${id}`)
}
