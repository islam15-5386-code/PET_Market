import api from '../api'
import type { ApiResponse, PaginatedMeta } from '@/types'

// ── Users ─────────────────────────────────────────────────────────────────────

export interface AdminUser {
  id: number
  name: string
  email: string
  role: string
  is_active: boolean
  phone: string | null
  avatar_url: string | null
  city: string | null
  orders_count: number
  created_at: string
}

export async function fetchAdminUsers(params: {
  search?: string
  role?: string
  status?: string
  per_page?: number
  page?: number
}): Promise<{ users: AdminUser[]; meta: PaginatedMeta }> {
  const q = new URLSearchParams()
  if (params.search) q.set('search', params.search)
  if (params.role) q.set('role', params.role)
  if (params.status) q.set('status', params.status)
  if (params.per_page) q.set('per_page', String(params.per_page))
  if (params.page && params.page > 1) q.set('page', String(params.page))

  const { data } = await api.get<ApiResponse<{ users: AdminUser[]; meta: PaginatedMeta }>>(
    `/admin/users?${q.toString()}`,
  )
  return data.data!
}

export async function blockUser(id: number): Promise<AdminUser> {
  const { data } = await api.post<ApiResponse<{ user: AdminUser }>>(
    `/admin/users/${id}/block`,
  )
  return data.data!.user
}

export async function unblockUser(id: number): Promise<AdminUser> {
  const { data } = await api.post<ApiResponse<{ user: AdminUser }>>(
    `/admin/users/${id}/unblock`,
  )
  return data.data!.user
}

// ── Orders ────────────────────────────────────────────────────────────────────

export interface AdminOrder {
  id: number
  order_number: string
  status: string
  subtotal: string
  shipping_fee: string
  total_amount: string
  items_count: number
  customer: { id: number; name: string; email: string } | null
  shipping: {
    name: string
    phone: string
    address: string
    city: string
    postal_code: string | null
  }
  created_at: string
}

export async function fetchAdminOrders(params: {
  status?: string
  search?: string
  date_from?: string
  date_to?: string
  per_page?: number
  page?: number
}): Promise<{ orders: AdminOrder[]; meta: PaginatedMeta }> {
  const q = new URLSearchParams()
  if (params.status) q.set('status', params.status)
  if (params.search) q.set('search', params.search)
  if (params.date_from) q.set('date_from', params.date_from)
  if (params.date_to) q.set('date_to', params.date_to)
  if (params.per_page) q.set('per_page', String(params.per_page))
  if (params.page && params.page > 1) q.set('page', String(params.page))

  const { data } = await api.get<
    ApiResponse<{ orders: AdminOrder[]; meta: PaginatedMeta }>
  >(`/admin/orders?${q.toString()}`)
  return data.data!
}

export async function updateOrderStatus(
  orderNumber: string,
  status: string,
): Promise<AdminOrder> {
  const { data } = await api.put<ApiResponse<{ order: AdminOrder }>>(
    `/admin/orders/${orderNumber}/status`,
    { status },
  )
  return data.data!.order
}
