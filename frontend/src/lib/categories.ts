import api from './api'
import type { ApiResponse, Category } from '@/types'

export async function fetchCategories(): Promise<Category[]> {
  const { data } = await api.get<ApiResponse<{ categories: Category[] }>>(
    '/categories',
  )
  return data.data!.categories
}
