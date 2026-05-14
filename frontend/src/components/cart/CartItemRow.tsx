'use client'

import React from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { Minus, Package, Plus, Trash2 } from 'lucide-react'
import type { CartItem } from '@/types'

interface CartItemRowProps {
  item: CartItem
  onUpdate: (productId: number, qty: number) => void
  onRemove: (productId: number) => void
  disabled?: boolean
}

export function CartItemRow({ item, onUpdate, onRemove, disabled }: CartItemRowProps) {
  const fallbackImage = '/placeholder-product.png'
  return (
    <div className="flex gap-4 py-5 border-b border-gray-100 last:border-0">
      {/* Image */}
      <div className="relative h-20 w-20 shrink-0 rounded-xl overflow-hidden bg-gray-100">
        {item.primary_image ? (
          <Image
            src={item.primary_image}
            alt={item.product_name}
            fill
            className="object-cover"
            sizes="80px"
            onError={(e) => {
              const target = e.currentTarget as HTMLImageElement
              target.src = fallbackImage
            }}
          />
        ) : (
          <div className="h-full flex items-center justify-center">
            <Package className="h-8 w-8 text-gray-300" />
          </div>
        )}
      </div>

      {/* Details */}
      <div className="flex flex-1 flex-col gap-2 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <Link
            href={`/products/${item.product_slug}`}
            className="text-sm font-semibold text-gray-900 hover:text-orange-600 transition-colors line-clamp-2 leading-snug"
          >
            {item.product_name}
          </Link>
          <button
            onClick={() => onRemove(item.product_id)}
            disabled={disabled}
            className="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0 disabled:opacity-40"
            aria-label="Remove item"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>

        <p className="text-sm font-bold text-gray-900">
          ৳{Number(item.unit_price).toLocaleString()}
        </p>

        <div className="flex items-center justify-between">
          {/* Qty stepper */}
          <div className="flex items-center border border-gray-200 rounded-xl overflow-hidden">
            <button
              onClick={() => onUpdate(item.product_id, item.quantity - 1)}
              disabled={disabled || item.quantity <= 1}
              className="px-2.5 py-1.5 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              aria-label="Decrease"
            >
              <Minus className="h-3.5 w-3.5 text-gray-600" />
            </button>
            <span className="w-9 text-center text-sm font-semibold text-gray-900">
              {item.quantity}
            </span>
            <button
              onClick={() => onUpdate(item.product_id, item.quantity + 1)}
              disabled={disabled || item.quantity >= item.stock_quantity}
              className="px-2.5 py-1.5 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              aria-label="Increase"
            >
              <Plus className="h-3.5 w-3.5 text-gray-600" />
            </button>
          </div>

          <p className="text-sm font-bold text-orange-600">
            ৳{Number(item.subtotal).toLocaleString()}
          </p>
        </div>
      </div>
    </div>
  )
}
