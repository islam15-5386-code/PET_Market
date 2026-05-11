'use client'

import React, { useState } from 'react'
import { useRouter } from 'next/navigation'
import Image from 'next/image'
import { Package, ShieldCheck } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { useCart } from '@/hooks/useCart'
import { placeOrder, previewOrder, type OrderPreview } from '@/lib/orders'
import { getErrorMessage } from '@/lib/api'
import type { CheckoutInput } from '@/types'

const EMPTY_FORM: CheckoutInput = {
  shipping_name: '',
  shipping_phone: '',
  shipping_address: '',
  shipping_city: '',
  shipping_postal_code: '',
  notes: '',
}

export default function CheckoutPage() {
  const router = useRouter()
  const { cart, reload } = useCart()

  const [form, setForm] = useState<CheckoutInput>(EMPTY_FORM)
  const [preview, setPreview] = useState<OrderPreview | null>(null)
  const [errors, setErrors] = useState<Partial<Record<keyof CheckoutInput, string>>>({})
  const [globalError, setGlobalError] = useState('')
  const [previewing, setPreviewing] = useState(false)
  const [placing, setPlacing] = useState(false)

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) {
    const { name, value } = e.target
    setForm((f) => ({ ...f, [name]: value }))
    setErrors((e) => ({ ...e, [name]: '' }))
    setPreview(null) // reset preview when form changes
    setGlobalError('')
  }

  function validate(): boolean {
    const e: Partial<Record<keyof CheckoutInput, string>> = {}
    if (!form.shipping_name.trim()) e.shipping_name = 'Name is required.'
    if (!form.shipping_phone.trim()) e.shipping_phone = 'Phone is required.'
    if (!form.shipping_address.trim()) e.shipping_address = 'Address is required.'
    if (!form.shipping_city.trim()) e.shipping_city = 'City is required.'
    setErrors(e)
    return Object.keys(e).length === 0
  }

  async function handlePreview() {
    if (!validate()) return
    setPreviewing(true)
    setGlobalError('')
    try {
      const data = await previewOrder(form)
      setPreview(data)
    } catch (err) {
      setGlobalError(getErrorMessage(err))
    } finally {
      setPreviewing(false)
    }
  }

  async function handlePlaceOrder() {
    if (!validate()) return
    setPlacing(true)
    setGlobalError('')
    try {
      const order = await placeOrder(form)
      await reload() // refresh cart count in navbar
      router.push(`/checkout/confirmation?order=${order.order_number}`)
    } catch (err) {
      setGlobalError(getErrorMessage(err))
    } finally {
      setPlacing(false)
    }
  }

  const cartSummary = cart?.summary
  const cartItems = cart?.items ?? []

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-8">Checkout</h1>

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-8">

        {/* ── Shipping form ──────────────────────────────────────────────── */}
        <div className="lg:col-span-3">
          <div className="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 className="text-base font-semibold text-gray-900 mb-5">
              Shipping Information
            </h2>

            {globalError && (
              <Alert variant="error" message={globalError} className="mb-5" />
            )}

            <div className="flex flex-col gap-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="Full name"
                  name="shipping_name"
                  value={form.shipping_name}
                  onChange={handleChange}
                  placeholder="Redwan Ahmed"
                  error={errors.shipping_name}
                  required
                />
                <Input
                  label="Phone number"
                  name="shipping_phone"
                  value={form.shipping_phone}
                  onChange={handleChange}
                  placeholder="01700000000"
                  error={errors.shipping_phone}
                  required
                />
              </div>

              <Input
                label="Full address"
                name="shipping_address"
                value={form.shipping_address}
                onChange={handleChange}
                placeholder="House no, Road, Area"
                error={errors.shipping_address}
                required
              />

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="City"
                  name="shipping_city"
                  value={form.shipping_city}
                  onChange={handleChange}
                  placeholder="Dhaka"
                  error={errors.shipping_city}
                  required
                />
                <Input
                  label="Postal code"
                  name="shipping_postal_code"
                  value={form.shipping_postal_code ?? ''}
                  onChange={handleChange}
                  placeholder="1205"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label className="text-sm font-medium text-gray-700">
                  Order notes <span className="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                  name="notes"
                  value={form.notes ?? ''}
                  onChange={handleChange}
                  placeholder="Any special instructions for delivery..."
                  rows={3}
                  className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 resize-none"
                />
              </div>
            </div>

            <Button
              fullWidth
              variant="outline"
              size="lg"
              className="mt-6"
              loading={previewing}
              onClick={handlePreview}
            >
              Preview Bill
            </Button>
          </div>
        </div>

        {/* ── Order summary ──────────────────────────────────────────────── */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24">
            <h2 className="text-base font-semibold text-gray-900 mb-4">
              {preview ? 'Order Preview' : 'Your Cart'}
            </h2>

            {/* Items */}
            <div className="flex flex-col gap-3 max-h-60 overflow-y-auto pr-1">
              {(preview?.items ?? cartItems).map((item, i) => {
                const name = item.product_name
                const image = 'product_image' in item ? item.product_image : item.primary_image
                const qty = item.quantity
                const price = 'total_price' in item ? item.total_price : item.subtotal

                return (
                  <div key={i} className="flex items-center gap-3">
                    <div className="relative h-12 w-12 shrink-0 rounded-lg overflow-hidden bg-gray-100">
                      {image ? (
                        <Image src={image} alt={name} fill className="object-cover" sizes="48px" />
                      ) : (
                        <div className="h-full flex items-center justify-center">
                          <Package className="h-5 w-5 text-gray-300" />
                        </div>
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-medium text-gray-800 line-clamp-1">{name}</p>
                      <p className="text-xs text-gray-500">×{qty}</p>
                    </div>
                    <span className="text-xs font-semibold text-gray-900 shrink-0">
                      ৳{Number(price).toLocaleString()}
                    </span>
                  </div>
                )
              })}
            </div>

            {/* Totals */}
            <div className="border-t border-gray-100 mt-4 pt-4 flex flex-col gap-2 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>৳{Number(preview?.subtotal ?? cartSummary?.subtotal ?? 0).toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-gray-600">
                <span>Shipping</span>
                {(preview?.shipping_fee ?? cartSummary?.shipping_fee) === '0.00' ? (
                  <span className="text-green-600 font-medium">FREE</span>
                ) : (
                  <span>৳{Number(preview?.shipping_fee ?? cartSummary?.shipping_fee ?? 0).toLocaleString()}</span>
                )}
              </div>
              <div className="flex justify-between font-bold text-gray-900 text-base border-t border-gray-100 pt-2">
                <span>Total</span>
                <span>৳{Number(preview?.total_amount ?? cartSummary?.total ?? 0).toLocaleString()}</span>
              </div>
            </div>

            {/* Place order */}
            <Button
              fullWidth
              size="lg"
              className="mt-5"
              loading={placing}
              disabled={!preview}
              onClick={handlePlaceOrder}
            >
              {preview ? 'Confirm & Place Order' : 'Preview Bill First'}
            </Button>

            {!preview && (
              <p className="text-xs text-center text-gray-400 mt-2">
                Fill shipping details and click &quot;Preview Bill&quot; first
              </p>
            )}

            <div className="flex items-center justify-center gap-1.5 mt-4 text-xs text-gray-400">
              <ShieldCheck className="h-3.5 w-3.5" />
              Secure checkout
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
