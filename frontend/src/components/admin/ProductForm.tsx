'use client'

import React, { useEffect, useMemo, useState } from 'react'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { useCategories } from '@/hooks/useCategories'
import type {
  AIDescriptionGenerateInput,
  AIGeneratedDescription,
  ProductFormInput,
} from '@/lib/admin/products'

interface ProductFormProps {
  initialValues?: Partial<ProductFormInput>
  onSubmit: (data: ProductFormInput) => Promise<void>
  onGenerateDescription?: (data: AIDescriptionGenerateInput) => Promise<AIGeneratedDescription>
  submitting: boolean
  generating?: boolean
  generated?: AIGeneratedDescription | null
  generateError?: string
  submitLabel?: string
  productId?: number
}

export function ProductForm({
  initialValues,
  onSubmit,
  onGenerateDescription,
  submitting,
  generating = false,
  generated = null,
  generateError = '',
  submitLabel = 'Save Product',
  productId,
}: ProductFormProps) {
  const { categories } = useCategories()
  const [form, setForm] = useState<ProductFormInput>({
    category_id: initialValues?.category_id ?? 0,
    name: initialValues?.name ?? '',
    description: initialValues?.description ?? '',
    price: initialValues?.price ?? 0,
    stock_quantity: initialValues?.stock_quantity ?? 0,
    location: initialValues?.location ?? '',
    brand: initialValues?.brand ?? '',
    is_available: initialValues?.is_available ?? true,
    ai_generated_title: initialValues?.ai_generated_title ?? '',
    ai_generated_short_description: initialValues?.ai_generated_short_description ?? '',
    ai_generated_long_description: initialValues?.ai_generated_long_description ?? '',
    ai_seo_keywords: initialValues?.ai_seo_keywords ?? [],
    ai_meta_title: initialValues?.ai_meta_title ?? '',
    ai_meta_description: initialValues?.ai_meta_description ?? '',
    ai_generated_tags: initialValues?.ai_generated_tags ?? [],
  })

  const [aiInput, setAiInput] = useState({
    pet_type: 'Cat',
    age_group: 'Adult',
    weight_or_size: '',
    ingredients_or_materials: '',
    key_features: '',
    usage_instruction: '',
    safety_note: '',
    target_customer: '',
    language: 'English' as 'English' | 'Bangla' | 'Bangla-English mixed',
    tone: 'professional' as 'professional' | 'friendly' | 'SEO optimized',
  })

  const [errors, setErrors] = useState<Partial<Record<keyof ProductFormInput, string>>>({})

  useEffect(() => {
    if (initialValues) {
      setForm((f) => ({
        ...f,
        category_id: initialValues.category_id ?? 0,
        name: initialValues.name ?? '',
        description: initialValues.description ?? '',
        price: initialValues.price ?? 0,
        stock_quantity: initialValues.stock_quantity ?? 0,
        location: initialValues.location ?? '',
        brand: initialValues.brand ?? '',
        is_available: initialValues.is_available ?? true,
        ai_generated_title: initialValues.ai_generated_title ?? '',
        ai_generated_short_description: initialValues.ai_generated_short_description ?? '',
        ai_generated_long_description: initialValues.ai_generated_long_description ?? '',
        ai_seo_keywords: initialValues.ai_seo_keywords ?? [],
        ai_meta_title: initialValues.ai_meta_title ?? '',
        ai_meta_description: initialValues.ai_meta_description ?? '',
        ai_generated_tags: initialValues.ai_generated_tags ?? [],
      }))
    }
  }, [JSON.stringify(initialValues)]) // eslint-disable-line

  useEffect(() => {
    if (!generated) return
    setForm((f) => ({
      ...f,
      ai_generated_title: generated.professional_product_title,
      ai_generated_short_description: generated.short_description,
      ai_generated_long_description: generated.long_description,
      ai_seo_keywords: generated.seo_keywords,
      ai_meta_title: generated.meta_title,
      ai_meta_description: generated.meta_description,
      ai_generated_tags: generated.suggested_tags,
    }))
  }, [generated])

  const selectedCategory = useMemo(
    () => categories.find((c) => c.id === Number(form.category_id)),
    [categories, form.category_id],
  )

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    const { name, value, type } = e.target
    const checked = (e.target as HTMLInputElement).checked
    setForm((f) => ({
      ...f,
      [name]: type === 'checkbox' ? checked : type === 'number' ? Number(value) : value,
    }))
    setErrors((er) => ({ ...er, [name]: '' }))
  }

  function handleAIInputChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    const { name, value } = e.target
    setAiInput((p) => ({ ...p, [name]: value }))
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
    await onSubmit({
      ...form,
      ai_seo_keywords: form.ai_seo_keywords ?? [],
      ai_generated_tags: form.ai_generated_tags ?? [],
    })
  }

  async function handleGenerate() {
    if (!onGenerateDescription) return
    if (!validate()) return

    const payload: AIDescriptionGenerateInput = {
      product_id: productId,
      product_name: form.name,
      category: selectedCategory?.name || 'General',
      pet_type: aiInput.pet_type,
      age_group: aiInput.age_group,
      brand: form.brand,
      price: form.price,
      weight_or_size: aiInput.weight_or_size,
      ingredients_or_materials: aiInput.ingredients_or_materials,
      key_features: aiInput.key_features,
      usage_instruction: aiInput.usage_instruction,
      safety_note: aiInput.safety_note,
      target_customer: aiInput.target_customer,
      language: aiInput.language,
      tone: aiInput.tone,
    }

    await onGenerateDescription(payload)
  }

  function applyToProductDescription() {
    setForm((f) => ({
      ...f,
      name: f.ai_generated_title || f.name,
      description: f.ai_generated_long_description || f.ai_generated_short_description || f.description,
    }))
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-5">
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">Category <span className="text-red-500">*</span></label>
        <select
          name="category_id"
          value={form.category_id}
          onChange={handleChange}
          className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-400"
        >
          <option value={0}>Select a category…</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>{c.icon} {c.name}</option>
          ))}
        </select>
        {errors.category_id && <p className="text-xs text-red-600">{errors.category_id}</p>}
      </div>

      <Input label="Product Name" name="name" value={form.name} onChange={handleChange} required />
      <Input label="Brand" name="brand" value={form.brand ?? ''} onChange={handleChange} placeholder="e.g. MeowCare" />

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input label="Price (৳)" type="number" name="price" value={form.price} onChange={handleChange} min={0} step={0.01} error={errors.price as string} required />
        <Input label="Stock Quantity" type="number" name="stock_quantity" value={form.stock_quantity} onChange={handleChange} min={0} error={errors.stock_quantity as string} required />
      </div>

      <Input label="Location" name="location" value={form.location ?? ''} onChange={handleChange} placeholder="e.g. Dhaka" />

      <div className="rounded-xl border border-gray-200 p-4 bg-gray-50">
        <p className="text-sm font-semibold text-gray-800 mb-3">AI Generation Inputs</p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <select name="pet_type" value={aiInput.pet_type} onChange={handleAIInputChange} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm">
            <option>Cat</option><option>Dog</option><option>Bird</option><option>Fish</option><option>Rabbit</option>
          </select>
          <select name="age_group" value={aiInput.age_group} onChange={handleAIInputChange} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm">
            <option>Kitten</option><option>Puppy</option><option>Adult</option><option>Senior</option>
          </select>
          <Input label="Weight/Size" name="weight_or_size" value={aiInput.weight_or_size} onChange={handleAIInputChange} />
          <select name="language" value={aiInput.language} onChange={handleAIInputChange} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm">
            <option>English</option><option>Bangla</option><option>Bangla-English mixed</option>
          </select>
          <select name="tone" value={aiInput.tone} onChange={handleAIInputChange} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm sm:col-span-2">
            <option>professional</option><option>friendly</option><option>SEO optimized</option>
          </select>
          <textarea name="ingredients_or_materials" value={aiInput.ingredients_or_materials} onChange={handleAIInputChange} rows={2} placeholder="ingredients/materials (comma separated)" className="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
          <textarea name="key_features" value={aiInput.key_features} onChange={handleAIInputChange} rows={2} placeholder="key features (comma separated)" className="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
          <textarea name="usage_instruction" value={aiInput.usage_instruction} onChange={handleAIInputChange} rows={2} placeholder="usage instruction" className="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
          <textarea name="safety_note" value={aiInput.safety_note} onChange={handleAIInputChange} rows={2} placeholder="safety note" className="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
          <Input label="Target Customer" name="target_customer" value={aiInput.target_customer} onChange={handleAIInputChange} />
        </div>

        <div className="mt-3 flex flex-wrap gap-2">
          {onGenerateDescription && (
            <Button type="button" size="sm" variant="outline" onClick={handleGenerate} loading={generating}>Generate AI Description</Button>
          )}
          <Button type="button" size="sm" variant="outline" onClick={applyToProductDescription}>Apply to Product</Button>
          {onGenerateDescription && (
            <Button type="button" size="sm" variant="outline" onClick={handleGenerate}>Regenerate</Button>
          )}
        </div>

        {generateError && <p className="text-xs text-red-600 mt-2">{generateError}</p>}
        {generated && (
          <div className="mt-3 text-xs text-gray-600">
            Provider: {generated.provider_name} | Model: {generated.model_name} | Tokens: {generated.token_usage.total_tokens}
          </div>
        )}
      </div>

      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" value={form.description ?? ''} onChange={handleChange} rows={4} className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm" />
      </div>

      <Input label="AI Title" name="ai_generated_title" value={form.ai_generated_title ?? ''} onChange={handleChange} />
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">AI Short Description</label>
        <textarea name="ai_generated_short_description" value={form.ai_generated_short_description ?? ''} onChange={handleChange} rows={3} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
      </div>
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">AI Long Description</label>
        <textarea name="ai_generated_long_description" value={form.ai_generated_long_description ?? ''} onChange={handleChange} rows={5} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
      </div>
      <Input
        label="AI SEO Keywords (comma separated)"
        name="ai_seo_keywords"
        value={(form.ai_seo_keywords ?? []).join(', ')}
        onChange={(e) => setForm((f) => ({ ...f, ai_seo_keywords: e.target.value.split(',').map((x) => x.trim()).filter(Boolean) }))}
      />
      <Input label="AI Meta Title" name="ai_meta_title" value={form.ai_meta_title ?? ''} onChange={handleChange} />
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-gray-700">AI Meta Description</label>
        <textarea name="ai_meta_description" value={form.ai_meta_description ?? ''} onChange={handleChange} rows={3} className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm" />
      </div>
      <Input
        label="AI Tags (comma separated)"
        name="ai_generated_tags"
        value={(form.ai_generated_tags ?? []).join(', ')}
        onChange={(e) => setForm((f) => ({ ...f, ai_generated_tags: e.target.value.split(',').map((x) => x.trim()).filter(Boolean) }))}
      />

      <label className="flex items-center gap-3 cursor-pointer">
        <div className="relative">
          <input type="checkbox" name="is_available" checked={form.is_available ?? true} onChange={handleChange} className="sr-only peer" />
          <div className="w-10 h-6 bg-gray-200 rounded-full peer peer-checked:bg-orange-500 transition-colors" />
          <div className="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4" />
        </div>
        <span className="text-sm font-medium text-gray-700">Available for purchase</span>
      </label>

      <Button type="submit" loading={submitting} size="lg">{submitLabel}</Button>
    </form>
  )
}
