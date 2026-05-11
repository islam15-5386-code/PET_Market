'use client'

import React from 'react'
import { clsx } from 'clsx'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { useCategories } from '@/hooks/useCategories'
import type { ProductFilters } from '@/types'

interface ProductFiltersProps {
  filters: ProductFilters
  onChange: (updated: Partial<ProductFilters>) => void
  onReset: () => void
  onClose?: () => void
}

export function ProductFiltersPanel({
  filters,
  onChange,
  onReset,
  onClose,
}: ProductFiltersProps) {
  const { categories } = useCategories()

  const hasActiveFilters =
    filters.category ||
    filters.category_id ||
    filters.min_price != null ||
    filters.max_price != null ||
    filters.location

  return (
    <div className="flex flex-col gap-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="font-semibold text-gray-900">Filters</h2>
        <div className="flex items-center gap-2">
          {hasActiveFilters && (
            <button
              onClick={onReset}
              className="text-xs text-orange-600 hover:text-orange-700 font-medium"
            >
              Clear all
            </button>
          )}
          {onClose && (
            <button onClick={onClose} className="p-1 rounded-lg hover:bg-gray-100 md:hidden">
              <X className="h-4 w-4 text-gray-500" />
            </button>
          )}
        </div>
      </div>

      {/* Category */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Category</h3>
        <div className="flex flex-col gap-1.5">
          <button
            onClick={() => onChange({ category: null, category_id: null, page: 1 })}
            className={clsx(
              'text-left text-sm px-3 py-2 rounded-xl transition-colors',
              !filters.category && !filters.category_id
                ? 'bg-orange-50 text-orange-700 font-semibold'
                : 'text-gray-600 hover:bg-gray-50',
            )}
          >
            All Categories
          </button>
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => onChange({ category: cat.slug, category_id: cat.id, page: 1 })}
              className={clsx(
                'text-left text-sm px-3 py-2 rounded-xl transition-colors flex items-center justify-between',
                filters.category === cat.slug || filters.category_id === cat.id
                  ? 'bg-orange-50 text-orange-700 font-semibold'
                  : 'text-gray-600 hover:bg-gray-50',
              )}
            >
              <span>
                {cat.icon} {cat.name}
              </span>
              {cat.products_count != null && (
                <span className="text-xs text-gray-400">{cat.products_count}</span>
              )}
            </button>
          ))}
        </div>
      </div>

      {/* Price range */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Price (৳)</h3>
        <div className="flex items-center gap-2">
          <input
            type="number"
            placeholder="Min"
            min={0}
            value={filters.min_price ?? ''}
            onChange={(e) =>
              onChange({
                min_price: e.target.value ? Number(e.target.value) : null,
                page: 1,
              })
            }
            className="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
          <span className="text-gray-400 shrink-0">–</span>
          <input
            type="number"
            placeholder="Max"
            min={0}
            value={filters.max_price ?? ''}
            onChange={(e) =>
              onChange({
                max_price: e.target.value ? Number(e.target.value) : null,
                page: 1,
              })
            }
            className="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
        </div>
      </div>

      {/* Location */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Location</h3>
        <input
          type="text"
          placeholder="e.g. Dhaka, Chittagong"
          value={filters.location ?? ''}
          onChange={(e) =>
            onChange({ location: e.target.value || undefined, page: 1 })
          }
          className="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
        />
      </div>

      {/* Apply on mobile */}
      {onClose && (
        <Button fullWidth onClick={onClose} className="md:hidden">
          Show Results
        </Button>
      )}
    </div>
  )
}
