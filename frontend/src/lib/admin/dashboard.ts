import api from '../api'
import type { ApiResponse } from '@/types'

export interface DashboardStats {
  total_users: number
  total_products: number
  total_orders: number
  total_revenue: string
  orders_today: number
  revenue_today: string
  pending_orders: number
  low_stock_products: number
}

export interface RevenuePoint {
  date: string
  total: string
  orders: number
}

export interface DashboardSnapshot {
  stats: DashboardStats
  order_status_breakdown: Record<string, number>
  recent_orders: {
    id: number
    order_number: string
    status: string
    total_amount: string
    items_count: number
    customer: string
    created_at: string
  }[]
  recent_users: {
    id: number
    name: string
    email: string
    is_active: boolean
    created_at: string
  }[]
  low_stock_alerts: {
    id: number
    name: string
    stock_quantity: number
    is_available: boolean
  }[]
}

export async function fetchDashboard(): Promise<DashboardSnapshot> {
  const { data } = await api.get<ApiResponse<DashboardSnapshot>>('/admin/dashboard')
  return data.data!
}

export async function fetchRevenueChart(): Promise<{
  revenue: RevenuePoint[]
  grand_total: string
}> {
  const { data } = await api.get<
    ApiResponse<{ revenue: RevenuePoint[]; grand_total: string }>
  >('/admin/dashboard/revenue')
  return data.data!
}
