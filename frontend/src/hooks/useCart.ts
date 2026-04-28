'use client'

import { useEffect, useState, useCallback } from 'react'
import { addToCart, fetchCart, removeFromCart, updateCartItem, clearCart } from '@/lib/cart'
import { useAuth } from '@/context/AuthContext'
import type { Cart } from '@/types'

export function useCart() {
  const { isAuthenticated } = useAuth()
  const [cart, setCart] = useState<Cart | null>(null)
  const [loading, setLoading] = useState(false)
  const [actionLoading, setActionLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const loadCart = useCallback(async () => {
    if (!isAuthenticated) return
    setLoading(true)
    try {
      const data = await fetchCart()
      setCart(data)
    } catch {
      setError('Failed to load cart.')
    } finally {
      setLoading(false)
    }
  }, [isAuthenticated])

  useEffect(() => {
    loadCart()
  }, [loadCart])

  const add = useCallback(async (productId: number, quantity: number) => {
    setActionLoading(true)
    setError(null)
    try {
      const updated = await addToCart(productId, quantity)
      setCart(updated)
    } catch (err: any) {
      const msg = err?.response?.data?.message ?? 'Failed to add item.'
      setError(msg)
      throw new Error(msg)
    } finally {
      setActionLoading(false)
    }
  }, [])

  const update = useCallback(async (productId: number, quantity: number) => {
    setActionLoading(true)
    setError(null)
    try {
      const updated = await updateCartItem(productId, quantity)
      setCart(updated)
    } catch (err: any) {
      const msg = err?.response?.data?.message ?? 'Failed to update cart.'
      setError(msg)
    } finally {
      setActionLoading(false)
    }
  }, [])

  const remove = useCallback(async (productId: number) => {
    setActionLoading(true)
    setError(null)
    try {
      const updated = await removeFromCart(productId)
      setCart(updated)
    } catch {
      setError('Failed to remove item.')
    } finally {
      setActionLoading(false)
    }
  }, [])

  const empty = useCallback(async () => {
    setActionLoading(true)
    try {
      await clearCart()
      setCart(null)
    } finally {
      setActionLoading(false)
    }
  }, [])

  return {
    cart,
    itemCount: cart?.summary.total_quantity ?? 0,
    loading,
    actionLoading,
    error,
    add,
    update,
    remove,
    empty,
    reload: loadCart,
  }
}
