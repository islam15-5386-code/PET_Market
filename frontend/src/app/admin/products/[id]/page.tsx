'use client'

import React, { useRef, useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { useParams, useRouter } from 'next/navigation'
import { ArrowLeft, ImagePlus, Trash2, Package } from 'lucide-react'
import { ProductForm } from '@/components/admin/ProductForm'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { useAdminProduct } from '@/hooks/admin/useAdmin'
import {
  generateProductDescription,
  type AIDescriptionGenerateInput,
  type AIGeneratedDescription,
  updateProduct,
  type ProductFormInput,
} from '@/lib/admin/products'
import { getErrorMessage } from '@/lib/api'

export default function EditProductPage() {
  const params = useParams()
  const router = useRouter()
  const id = Number(params.id)

  const { product, setProduct, loading, addImages, removeImage } = useAdminProduct(id)

  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [removingIdx, setRemovingIdx] = useState<number | null>(null)
  const [generating, setGenerating] = useState(false)
  const [generated, setGenerated] = useState<AIGeneratedDescription | null>(null)
  const [generateError, setGenerateError] = useState('')
  const fileInputRef = useRef<HTMLInputElement>(null)

  async function handleSubmit(data: ProductFormInput) {
    setSubmitting(true)
    setError('')
    setSuccess('')
    try {
      const updated = await updateProduct(id, data)
      setProduct(updated)
      setSuccess('Product updated successfully.')
    } catch (err) {
      setError(getErrorMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  async function handleImageUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? [])
    if (!files.length) return
    setError('')
    try {
      await addImages(files)
    } catch (err) {
      setError(getErrorMessage(err))
    }
    e.target.value = ''
  }

  async function handleGenerateDescription(input: AIDescriptionGenerateInput) {
    setGenerating(true)
    setGenerateError('')
    try {
      const ai = await generateProductDescription({ ...input, product_id: id })
      setGenerated(ai)
      return ai
    } catch (err) {
      setGenerateError(getErrorMessage(err))
      throw err
    } finally {
      setGenerating(false)
    }
  }

  async function handleRemoveImage(index: number) {
    if (!confirm('Remove this image?')) return
    setRemovingIdx(index)
    try {
      await removeImage(index)
    } catch (err) {
      setError(getErrorMessage(err))
    } finally {
      setRemovingIdx(null)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  if (!product) {
    return (
      <div className="text-center py-16 text-gray-500">
        Product not found.{' '}
        <Link href="/admin/products" className="link-brand">Go back</Link>
      </div>
    )
  }

  return (
    <div className="max-w-2xl">
      <Link
        href="/admin/products"
        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6"
      >
        <ArrowLeft className="h-4 w-4" /> Back to Products
      </Link>

      <h1 className="text-2xl font-bold text-gray-900 mb-6">Edit Product</h1>

      {error && <Alert variant="error" message={error} className="mb-4" />}
      {success && <Alert variant="success" message={success} className="mb-4" />}

      {/* Product form */}
      <div className="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <ProductForm
          initialValues={{
            category_id: product.category?.id,
            name: product.name,
            description: product.description ?? '',
            price: Number(product.price),
            stock_quantity: product.stock_quantity,
            location: product.location ?? '',
            brand: product.brand ?? '',
            is_available: product.is_available,
            ai_generated_title: product.ai_generated_title ?? '',
            ai_generated_short_description: product.ai_generated_short_description ?? '',
            ai_generated_long_description: product.ai_generated_long_description ?? '',
            ai_seo_keywords: product.ai_seo_keywords ?? [],
            ai_meta_title: product.ai_meta_title ?? '',
            ai_meta_description: product.ai_meta_description ?? '',
            ai_generated_tags: product.ai_generated_tags ?? [],
          }}
          onSubmit={handleSubmit}
          onGenerateDescription={handleGenerateDescription}
          submitting={submitting}
          generating={generating}
          generated={generated}
          generateError={generateError}
          submitLabel="Update Product"
          productId={id}
        />
      </div>

      {/* Images */}
      <div className="bg-white rounded-2xl border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-semibold text-gray-900">
            Product Images
            <span className="ml-2 text-sm font-normal text-gray-400">
              ({product.images.length}/10)
            </span>
          </h2>
          {product.images.length < 10 && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => fileInputRef.current?.click()}
            >
              <ImagePlus className="h-4 w-4" /> Upload
            </Button>
          )}
        </div>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/jpg,image/png,image/webp"
          multiple
          className="hidden"
          onChange={handleImageUpload}
        />

        {product.images.length === 0 ? (
          <div
            onClick={() => fileInputRef.current?.click()}
            className="flex flex-col items-center justify-center h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-orange-400 hover:bg-orange-50 transition-colors"
          >
            <Package className="h-8 w-8 text-gray-300 mb-2" />
            <p className="text-sm text-gray-400">Click to upload images</p>
          </div>
        ) : (
          <div className="grid grid-cols-3 sm:grid-cols-4 gap-3">
            {product.images.map((img, i) => (
              <div key={i} className="relative group aspect-square rounded-xl overflow-hidden bg-gray-100">
                <Image src={img} alt={`Image ${i + 1}`} fill className="object-cover" sizes="120px" />
                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  {removingIdx === i ? (
                    <div className="h-5 w-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  ) : (
                    <button
                      onClick={() => handleRemoveImage(i)}
                      className="p-2 rounded-full bg-red-500 text-white hover:bg-red-600"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  )}
                </div>
                {i === 0 && (
                  <span className="absolute top-1.5 left-1.5 bg-orange-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                    Main
                  </span>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
