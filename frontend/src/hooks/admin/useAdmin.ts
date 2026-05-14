'use client'

import { useCallback, useEffect, useState } from 'react'
import {
  fetchDashboard,
  fetchRevenueChart,
  type DashboardSnapshot,
  type RevenuePoint,
} from '@/lib/admin/dashboard'
import {
  fetchAdminProducts,
  fetchAdminProduct,
  deleteProduct,
  uploadProductImages,
  deleteProductImage,
  type AdminProduct,
} from '@/lib/admin/products'
import {
  fetchAdminUsers,
  blockUser,
  unblockUser,
  fetchAdminOrders,
  updateOrderStatus,
  type AdminUser,
  type AdminOrder,
} from '@/lib/admin/users-orders'
import type { PaginatedMeta } from '@/types'

// ── Dashboard ──────────────────────────────────────────────────────────────────

export function useDashboard() {
  const [snapshot, setSnapshot] = useState<DashboardSnapshot | null>(null)
  const [revenue, setRevenue] = useState<RevenuePoint[]>([])
  const [grandTotal, setGrandTotal] = useState('0.00')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const load = () =>
      Promise.all([fetchDashboard(), fetchRevenueChart()])
        .then(([snap, rev]) => {
          setSnapshot(snap)
          setRevenue(rev.revenue)
          setGrandTotal(rev.grand_total)
        })
        .finally(() => setLoading(false))

    load()
    const interval = window.setInterval(load, 30000)
    return () => window.clearInterval(interval)
  }, [])

  return { snapshot, revenue, grandTotal, loading }
}

// ── Admin Products ─────────────────────────────────────────────────────────────

export function useAdminProducts() {
  const [products, setProducts] = useState<AdminProduct[]>([])
  const [meta, setMeta] = useState<PaginatedMeta | null>(null)
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)

  const load = useCallback(
    (p = page, s = search) => {
      setLoading(true)
      fetchAdminProducts({ search: s, page: p, per_page: 15 })
        .then(({ products: data, meta: m }) => {
          setProducts(data)
          setMeta(m)
        })
        .finally(() => setLoading(false))
    },
    [page, search],
  )

  useEffect(() => { load(page, search) }, [page, search]) // eslint-disable-line

  function handleSearch(s: string) { setSearch(s); setPage(1) }

  const remove = useCallback(async (id: number) => {
    await deleteProduct(id)
    load(page, search)
  }, [load, page, search])

  return { products, meta, search, page, setPage, loading, handleSearch, remove, reload: load }
}

export function useAdminProduct(id: number | null) {
  const [product, setProduct] = useState<AdminProduct | null>(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (!id) return
    setLoading(true)
    fetchAdminProduct(id).then(setProduct).finally(() => setLoading(false))
  }, [id])

  const addImages = useCallback(async (files: File[]) => {
    if (!id) return
    const updated = await uploadProductImages(id, files)
    setProduct(updated)
  }, [id])

  const removeImage = useCallback(async (index: number) => {
    if (!id) return
    const updated = await deleteProductImage(id, index)
    setProduct(updated)
  }, [id])

  return { product, setProduct, loading, addImages, removeImage }
}

// ── Admin Users ────────────────────────────────────────────────────────────────

export function useAdminUsers() {
  const [users, setUsers] = useState<AdminUser[]>([])
  const [meta, setMeta] = useState<PaginatedMeta | null>(null)
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [actionLoading, setActionLoading] = useState<number | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    fetchAdminUsers({ search, status, page, per_page: 15 })
      .then(({ users: data, meta: m }) => { setUsers(data); setMeta(m) })
      .finally(() => setLoading(false))
  }, [search, status, page])

  useEffect(() => { load() }, [load])

  const toggleBlock = useCallback(async (user: AdminUser) => {
    setActionLoading(user.id)
    try {
      const updated = user.is_active
        ? await blockUser(user.id)
        : await unblockUser(user.id)
      setUsers((prev) => prev.map((u) => (u.id === updated.id ? updated : u)))
    } finally {
      setActionLoading(null)
    }
  }, [])

  return {
    users, meta, search, setSearch, status, setStatus,
    page, setPage, loading, actionLoading, toggleBlock,
  }
}

// ── Admin Orders ───────────────────────────────────────────────────────────────

export function useAdminOrders() {
  const [orders, setOrders] = useState<AdminOrder[]>([])
  const [meta, setMeta] = useState<PaginatedMeta | null>(null)
  const [filterStatus, setFilterStatus] = useState('')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)
  const [updatingId, setUpdatingId] = useState<string | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    fetchAdminOrders({ status: filterStatus, search, page, per_page: 15 })
      .then(({ orders: data, meta: m }) => { setOrders(data); setMeta(m) })
      .finally(() => setLoading(false))
  }, [filterStatus, search, page])

  useEffect(() => { load() }, [load])

  const changeStatus = useCallback(async (orderNumber: string, newStatus: string) => {
    setUpdatingId(orderNumber)
    try {
      const updated = await updateOrderStatus(orderNumber, newStatus)
      setOrders((prev) =>
        prev.map((o) => (o.order_number === updated.order_number ? updated : o)),
      )
    } finally {
      setUpdatingId(null)
    }
  }, [])

  return {
    orders, meta, filterStatus, setFilterStatus,
    search, setSearch, page, setPage, loading, updatingId, changeStatus,
  }
}
