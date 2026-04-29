'use client'

import React, { useEffect, useState } from 'react'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { useCategories } from '@/hooks/useCategories'
import type { ProductFormInput } from '@/lib/admin/products'

interface ProductFormProps {
  initialValues?: Partial<ProductFormInput>
  onSubmit: (data: ProductFormInput) => Promise<void>
  submitting: boolean
  submitLabel?: string
}

export function ProductForm({
  initialValues,
  onSubmit,
  submitting,
  submitLabel = 'Save Product',
}: ProductFormProps) {
  const { categories } = useCategories()
  const [form, setForm] = useState<ProductFormInput>({
    category_id: initialValues?.category_id ?? 0,
    name: initialValues?.name ?? '',
    description: initialValues?.description ?? '',
    price: initialValues?.price ?? 0,
    stock_quantity: initialValues?.stock_quantity ?? 0,
    location: initialValues?.location ?? '',
    is_available: initialValues?.is_available ?? true,
  })
  const [errors, setErrors] = useState<Partial<Record<keyof ProductFormInput, string>>>({})

  // Re-sync when initialValues load (edit mode)
  useEffect(() => {
    if (initialValues) {
      setForm({
        category_id: initialValues.category_id ?? 0,
        name: initialValues.name ?? '',
        description: initialValues.description ?? '',
        price: initialValues.price ?? 0,
        stock_quantity: initialValues.stock_quantity ?? 0,
        location: initialValues.location ?? '',
        is_available: initialValues.is_available ?? true,
      })
    }
  }, [JSON.stringify(initialValues)]) // eslint-disable-line

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    const { name, value, type } = e.target
    const checked = (e.target as HTMLInputElement).checked
    setForm((f) => ({
      ...f,
      [name]: type === 'checkbox' ? checked : type === 'number' ? Number(value) : value,
    }))
    setErrors((er) => ({ ...er, [name]: '' }))
  }

  function validate(): boolean {
    const e: Partial<Record<keyof ProductFormInput, string>> = {}
    if (!form.category_id) e.category_id = 'Category is required.'
    if (!form.name.trim()) e.name = 'Name is required.'
    if (form.price <= 0) e.price = 'Price must be greater than 0.'
    if (form.stock_quantity < 0) e.stock_quantity = 'Stock cannot be negative.'
    setErrors(e)
    return Object.keys(e).length === 0
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!validate()) return
    await onSubmit(form)
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-5">
      {/* Category */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">
          Category <span className="text-red-500">*</span>
        </label>
        <select
          name="category_id"
          value={form.category_id}
          onChange={handleChange}
          className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-400"
        >
          <option value={0}>Select a category…</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.icon} {c.name}
            </option>
          ))}
        </select>
        {errors.category_id && (
          <p className="text-xs text-red-600">{errors.category_id}</p>
        )}
      </div>

      {/* Name */}
      <Input
        label="Product Name"
        name="name"
        value={form.name}
        onChange={handleChange}
        placeholder="e.g. Royal Canin Medium Adult 4kg"
        error={errors.name as string}
        required
      />

      {/* Description */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">Description</label>
        <textarea
          name="description"
          value={form.description ?? ''}
          onChange={handleChange}
          rows={4}
          placeholder="Describe the product..."
          className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"
        />
      </div>

      {/* Price + Stock */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input
          label="Price (৳)"
          type="number"
          name="price"
          value={form.price}
          onChange={handleChange}
          min={0}
          step={0.01}
          error={errors.price as string}
          required
        />
        <Input
          label="Stock Quantity"
          type="number"
          name="stock_quantity"
          value={form.stock_quantity}
          onChange={handleChange}
          min={0}
          error={errors.stock_quantity as string}
          required
        />
      </div>

      {/* Location */}
      <Input
        label="Location"
        name="location"
        value={form.location ?? ''}
        onChange={handleChange}
        placeholder="e.g. Dhaka"
      />

      {/* Availability */}
      <label className="flex items-center gap-3 cursor-pointer">
        <div className="relative">
          <input
            type="checkbox"
            name="is_available"
            checked={form.is_available ?? true}
            onChange={handleChange}
            className="sr-only peer"
          />
          <div className="w-10 h-6 bg-gray-200 rounded-full peer peer-checked:bg-orange-500 transition-colors" />
          <div className="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4" />
        </div>
        <span className="text-sm font-medium text-gray-700">
          Available for purchase
        </span>
      </label>

      <Button type="submit" loading={submitting} size="lg">
        {submitLabel}
      </Button>
    </form>
  )
}
