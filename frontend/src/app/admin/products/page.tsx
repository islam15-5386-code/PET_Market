'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { Package, Plus, Search, Pencil, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
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
      {/* Header */}
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Products</h1>
        <Link href="/admin/products/new">
          <Button size="sm">
            <Plus className="h-4 w-4" /> New Product
          </Button>
        </Link>
      </div>

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
          className="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-400"
        />
      </div>

      {/* Table */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Spinner />
          </div>
        ) : products.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-gray-400 gap-2">
            <Package className="h-10 w-10" />
            <p className="text-sm">No products found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50 text-left">
                  <th className="px-4 py-3 font-medium text-gray-500">Product</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Category</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Price</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Stock</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Status</th>
                  <th className="px-4 py-3 font-medium text-gray-500 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {products.map((p) => (
                  <tr key={p.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="relative h-10 w-10 rounded-lg overflow-hidden bg-gray-100 shrink-0">
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
                        <span className="font-medium text-gray-900 line-clamp-1 max-w-[200px]">
                          {p.name}
                        </span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-gray-500">
                      {p.category ? (
                        <span>{p.category.icon} {p.category.name}</span>
                      ) : '—'}
                    </td>
                    <td className="px-4 py-3 font-medium text-gray-900">
                      ৳{Number(p.price).toLocaleString()}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`font-semibold ${
                        p.stock_quantity === 0 ? 'text-red-600' :
                        p.stock_quantity <= 5 ? 'text-amber-600' : 'text-gray-900'
                      }`}>
                        {p.stock_quantity}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                        p.is_available
                          ? 'bg-green-100 text-green-700'
                          : 'bg-gray-100 text-gray-500'
                      }`}>
                        {p.is_available ? 'Available' : 'Hidden'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        <Link href={`/admin/products/${p.id}`}>
                          <button className="p-1.5 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-colors">
                            <Pencil className="h-4 w-4" />
                          </button>
                        </Link>
                        <button
                          onClick={() => handleDelete(p.id, p.name)}
                          disabled={deletingId === p.id}
                          className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-40"
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
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">
              {meta.total} products · Page {meta.current_page} of {meta.last_page}
            </p>
            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="px-3 py-1.5 text-xs border border-gray-300 rounded-lg disabled:opacity-40 hover:bg-gray-50"
              >
                Prev
              </button>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="px-3 py-1.5 text-xs border border-gray-300 rounded-lg disabled:opacity-40 hover:bg-gray-50"
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
