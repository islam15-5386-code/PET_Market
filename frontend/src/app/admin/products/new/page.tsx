'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { ArrowLeft } from 'lucide-react'
import { ProductForm } from '@/components/admin/ProductForm'
import { Alert } from '@/components/ui/Alert'
import {
  createProduct,
  generateProductDescription,
  type AIDescriptionGenerateInput,
  type AIGeneratedDescription,
  type ProductFormInput,
} from '@/lib/admin/products'
import { getErrorMessage } from '@/lib/api'

export default function NewProductPage() {
  const router = useRouter()
  const [submitting, setSubmitting] = useState(false)
  const [generating, setGenerating] = useState(false)
  const [error, setError] = useState('')
  const [generated, setGenerated] = useState<AIGeneratedDescription | null>(null)
  const [generateError, setGenerateError] = useState('')

  async function handleSubmit(data: ProductFormInput) {
    setSubmitting(true)
    setError('')
    try {
      const product = await createProduct(data)
      router.push(`/admin/products/${product.id}`)
    } catch (err) {
      setError(getErrorMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  async function handleGenerateDescription(input: AIDescriptionGenerateInput) {
    setGenerating(true)
    setGenerateError('')
    try {
      const ai = await generateProductDescription(input)
      setGenerated(ai)
      return ai
    } catch (err) {
      setGenerateError(getErrorMessage(err))
      throw err
    } finally {
      setGenerating(false)
    }
  }

  return (
    <div className="max-w-2xl">
      <Link
        href="/admin/products"
        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6"
      >
        <ArrowLeft className="h-4 w-4" /> Back to Products
      </Link>

      <h1 className="text-2xl font-bold text-gray-900 mb-6">New Product</h1>

      {error && <Alert variant="error" message={error} className="mb-5" />}

      <div className="bg-white rounded-2xl border border-gray-200 p-6">
        <ProductForm
          onSubmit={handleSubmit}
          onGenerateDescription={handleGenerateDescription}
          submitting={submitting}
          generating={generating}
          generated={generated}
          generateError={generateError}
          submitLabel="Create Product"
        />
      </div>
    </div>
  )
}
