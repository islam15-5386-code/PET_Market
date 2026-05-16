'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { Package, Plus, Search, Pencil, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { EmptyState } from '@/components/ui/EmptyState'
import { PageHeader } from '@/components/ui/PageHeader'
import { useAdminProducts } from '@/hooks/admin/useAdmin'

export default function AdminProductsPage() {
  const fallbackImage = '/placeholder-product.png'
  const { products, meta, search, page, setPage, loading, handleSearch, remove } =
    useAdminProducts()
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [searchInput, setSearchInput] = useState(search)

  async function handleDelete(id: number, name: string) {
    if (!confirm(`Delete "${name}"? This action cannot be undone.`)) return
    setDeletingId(id)
    try {
      await remove(id)
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Products"
        description="Manage catalog items, stock, visibility, and pricing."
        actions={(
          <Link href="/admin/products/new">
            <Button size="sm">
              <Plus className="h-4 w-4" /> New Product
            </Button>
          </Link>
        )}
      />

      {/* Search */}
      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') handleSearch(searchInput)
          }}
          placeholder="Search products..."
          className="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
        />
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_34px_-28px_rgba(15,23,42,.7)]">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Spinner />
          </div>
        ) : products.length === 0 ? (
          <div className="p-4">
            <EmptyState title="No products found" description="Try another search term or create a new product." icon={<Package className="h-7 w-7" />} />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50/80 text-left">
                  <th className="px-4 py-3 font-medium text-slate-500">Product</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Category</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Price</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Stock</th>
                  <th className="px-4 py-3 font-medium text-slate-500">Status</th>
                  <th className="px-4 py-3 text-right font-medium text-slate-500">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-50">
                {products.map((p) => (
                  <tr key={p.id} className="transition-colors hover:bg-slate-50">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="relative h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                          {p.primary_image ? (
                            <Image
                              src={p.primary_image}
                              alt={p.name}
                              fill
                              className="object-cover"
                              sizes="40px"
                              onError={(e) => {
                                const target = e.currentTarget as HTMLImageElement
                                target.src = fallbackImage
                              }}
                            />
                          ) : (
                            <div className="h-full flex items-center justify-center">
                              <Package className="h-4 w-4 text-gray-300" />
                            </div>
                          )}
                        </div>
                        <span className="max-w-[200px] line-clamp-1 font-medium text-slate-900">
                          {p.name}
                        </span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-slate-500">
                      {p.category ? (
                        <span>{p.category.icon} {p.category.name}</span>
                      ) : '—'}
                    </td>
                    <td className="px-4 py-3 font-medium text-slate-900">
                      ৳{Number(p.price).toLocaleString()}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`font-semibold ${
                        p.stock_quantity === 0 ? 'text-red-600' :
                        p.stock_quantity <= 5 ? 'text-amber-600' : 'text-slate-900'
                      }`}>
                        {p.stock_quantity}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                        p.is_available
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-slate-100 text-slate-500'
                      }`}>
                        {p.is_available ? 'Available' : 'Hidden'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        <Link href={`/admin/products/${p.id}`}>
                          <button className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-amber-50 hover:text-amber-700">
                            <Pencil className="h-4 w-4" />
                          </button>
                        </Link>
                        <button
                          onClick={() => handleDelete(p.id, p.name)}
                          disabled={deletingId === p.id}
                          className="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <p className="text-xs text-slate-500">
              {meta.total} products · Page {meta.current_page} of {meta.last_page}
            </p>
            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-slate-50"
              >
                Prev
              </button>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="rounded-lg border border-slate-300 px-3 py-1.5 text-xs disabled:opacity-40 hover:bg-slate-50"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
