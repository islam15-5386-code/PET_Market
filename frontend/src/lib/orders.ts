import api from './api'
import type { ApiResponse, CheckoutInput, Order, PaginatedMeta } from '@/types'

export interface OrderPreview {
  items: {
    product_id: number
    product_name: string
    product_image: string | null
    quantity: number
    unit_price: string
    total_price: string
  }[]
  subtotal: string
  shipping_fee: string
  total_amount: string
  shipping: CheckoutInput
}

export interface OrderHistoryResponse {
  orders: Order[]
  meta: PaginatedMeta
}

export async function previewOrder(input: CheckoutInput): Promise<OrderPreview> {
  const { data } = await api.post<ApiResponse<{ preview: OrderPreview }>>(
    '/orders/preview',
    input,
  )
  return data.data!.preview
}

export async function placeOrder(input: CheckoutInput): Promise<Order> {
  const { data } = await api.post<ApiResponse<{ order: Order }>>('/orders', input)
  return data.data!.order
}

export async function fetchOrderHistory(page = 1): Promise<OrderHistoryResponse> {
  const { data } = await api.get<
    ApiResponse<{ orders: Order[]; meta: PaginatedMeta }>
  >(`/user/orders?page=${page}&per_page=10`)
  return { orders: data.data!.orders, meta: data.data!.meta }
}

export async function fetchOrder(orderNumber: string): Promise<Order> {
  const { data } = await api.get<ApiResponse<{ order: Order }>>(
    `/orders/${orderNumber}`,
  )
  return data.data!.order
}

export async function cancelOrder(orderNumber: string): Promise<Order> {
  const { data } = await api.post<ApiResponse<{ order: Order }>>(
    `/orders/${orderNumber}/cancel`,
  )
  return data.data!.order
}
