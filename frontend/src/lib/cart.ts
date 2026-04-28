import api from './api'
import type { ApiResponse, Cart } from '@/types'

export async function fetchCart(): Promise<Cart> {
  const { data } = await api.get<ApiResponse<{ cart: Cart }>>('/cart')
  return data.data!.cart
}

export async function addToCart(
  productId: number,
  quantity: number,
): Promise<Cart> {
  const { data } = await api.post<ApiResponse<{ cart: Cart }>>('/cart', {
    product_id: productId,
    quantity,
  })
  return data.data!.cart
}

export async function updateCartItem(
  productId: number,
  quantity: number,
): Promise<Cart> {
  const { data } = await api.put<ApiResponse<{ cart: Cart }>>(
    `/cart/${productId}`,
    { quantity },
  )
  return data.data!.cart
}

export async function removeFromCart(productId: number): Promise<Cart> {
  const { data } = await api.delete<ApiResponse<{ cart: Cart }>>(
    `/cart/${productId}`,
  )
  return data.data!.cart
}

export async function clearCart(): Promise<void> {
  await api.delete('/cart')
}
