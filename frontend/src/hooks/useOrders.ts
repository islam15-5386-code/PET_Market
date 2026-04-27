'use client'

import { useCallback, useEffect, useState } from 'react'
import { fetchOrderHistory, fetchOrder, cancelOrder } from '@/lib/orders'
import type { Order, PaginatedMeta } from '@/types'

export function useOrderHistory(initialPage = 1) {
  const [orders, setOrders] = useState<Order[]>([])
  const [meta, setMeta] = useState<PaginatedMeta | null>(null)
  const [page, setPage] = useState(initialPage)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    setLoading(true)
    fetchOrderHistory(page)
      .then(({ orders: data, meta: m }) => {
        setOrders(data)
        setMeta(m)
      })
      .catch(() => setError('Failed to load orders.'))
      .finally(() => setLoading(false))
  }, [page])

  return { orders, meta, page, setPage, loading, error }
}

export function useOrder(orderNumber: string) {
  const [order, setOrder] = useState<Order | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [cancelling, setCancelling] = useState(false)

  useEffect(() => {
    if (!orderNumber) return
    setLoading(true)
    fetchOrder(orderNumber)
      .then(setOrder)
      .catch(() => setError('Order not found.'))
      .finally(() => setLoading(false))
  }, [orderNumber])

  const cancel = useCallback(async () => {
    if (!orderNumber) return
    setCancelling(true)
    try {
      const updated = await cancelOrder(orderNumber)
      setOrder(updated)
    } catch (err: any) {
      throw new Error(err?.response?.data?.message ?? 'Failed to cancel order.')
    } finally {
      setCancelling(false)
    }
  }, [orderNumber])

  return { order, loading, error, cancel, cancelling }
}
