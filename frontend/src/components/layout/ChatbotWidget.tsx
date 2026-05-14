'use client'

import React, { useEffect, useMemo, useRef, useState } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import {
  AlertTriangle,
  Bot,
  MessageCircle,
  PawPrint,
  Send,
  ShieldAlert,
  Sparkles,
  X,
} from 'lucide-react'
import { sendChatbotMessage } from '@/lib/chatbot'
import { getErrorMessage } from '@/lib/api'
import { formatBDT } from '@/lib/currency'
import { Badge } from '@/components/ui/Badge'
import { AIChip } from '@/components/ui/AIChip'

type ProductRec = {
  id: number
  name?: string
  title?: string
  price?: number
  price_bdt?: number
  image?: string | null
  image_url?: string | null
  thumbnail_url?: string | null
  rating?: number
  category?: string | null
  pet_type?: string | null
  stock?: number | null
  slug?: string
}

type Msg = {
  id: string
  sender: 'user' | 'ai'
  text: string
  safety?: string
  vetWarning?: string | null
  intent?: string | null
  petType?: string | null
  category?: string | null
  ageGroup?: string | null
  products?: ProductRec[]
  isError?: boolean
  createdAt: number
}

const FALLBACK_IMAGE = '/placeholder-product.png'

const QUICK_QUESTIONS = [
  { label: 'Best food for my puppy', group: 'Food' },
  { label: 'Grooming tips for Persian cat', group: 'Grooming' },
  { label: 'My cat is not eating', group: 'Health' },
  { label: 'Dog toy under 500 BDT', group: 'Budget' },
  { label: 'Cat food under 1000 BDT', group: 'Budget' },
  { label: 'Safe products for kittens', group: 'Care' },
]

function makeMsgId(): string {
  return `msg-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
}

function isEmergencyMessage(safety?: string, vetWarning?: string | null, text?: string): boolean {
  const low = `${safety ?? ''} ${vetWarning ?? ''} ${text ?? ''}`.toLowerCase()
  return (
    low.includes('emergency') ||
    low.includes('serious') ||
    low.includes('immediately') ||
    low.includes('veterinarian') ||
    low.includes('vet') ||
    low.includes('bleeding') ||
    low.includes('poison') ||
    low.includes('cannot breathe')
  )
}

function getProductName(p: ProductRec): string {
  return p.name || p.title || 'Recommended product'
}

function getProductPrice(p: ProductRec): number {
  const v = p.price_bdt ?? p.price ?? 0
  return Number.isFinite(Number(v)) ? Number(v) : 0
}

function getProductImage(p: ProductRec): string {
  return p.image || p.image_url || p.thumbnail_url || FALLBACK_IMAGE
}

function getProductHref(p: ProductRec): string {
  return p.slug ? `/products/${p.slug}` : '/products'
}

function TypingIndicator() {
  return (
    <div className="flex items-start gap-2">
      <div className="mt-0.5 grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-500 text-white shadow-sm">
        <Bot className="h-4 w-4" />
      </div>
      <div className="rounded-2xl rounded-tl-sm border border-gray-200 bg-white px-3 py-2 shadow-sm">
        <p className="text-xs font-medium text-gray-500">PetCare AI is thinking...</p>
        <div className="mt-1 flex items-center gap-1">
          <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-indigo-400 [animation-delay:-0.3s]" />
          <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-indigo-400 [animation-delay:-0.15s]" />
          <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-indigo-400" />
        </div>
      </div>
    </div>
  )
}

function EmergencyWarningCard({ message }: { message: string }) {
  return (
    <div className="mt-2 rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-amber-50 p-3">
      <div className="flex items-start gap-2">
        <ShieldAlert className="mt-0.5 h-4 w-4 text-rose-600" />
        <div>
          <p className="text-xs font-semibold text-rose-700">Veterinary attention recommended</p>
          <p className="mt-1 text-xs text-rose-700/90">{message}</p>
          <p className="mt-1 text-[11px] text-rose-600/80">
            PetCare AI provides general information, not medical diagnosis.
          </p>
        </div>
      </div>
    </div>
  )
}

function ProductRecommendationCard({ product }: { product: ProductRec }) {
  const [img, setImg] = useState(getProductImage(product))
  const name = getProductName(product)
  const price = getProductPrice(product)
  const href = getProductHref(product)
  const rating = Number(product.rating ?? 0)

  return (
    <div className="rounded-xl border border-gray-200 bg-white p-2.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      <div className="flex gap-2.5">
        <div className="relative h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-gray-100">
          <Image
            src={img}
            alt={name}
            fill
            className="object-cover"
            sizes="56px"
            onError={() => setImg(FALLBACK_IMAGE)}
          />
        </div>
        <div className="min-w-0 flex-1">
          <p className="line-clamp-2 text-sm font-semibold text-gray-800">{name}</p>
          <div className="mt-1 flex items-center gap-2 text-xs text-gray-500">
            <span>{formatBDT(price)}</span>
            <span>⭐ {rating.toFixed(1)}</span>
          </div>
          <div className="mt-1 flex flex-wrap gap-1">
            {product.category && <Badge variant="neutral">{product.category}</Badge>}
            {product.pet_type && <Badge variant="brand">{product.pet_type}</Badge>}
          </div>
          <p className="mt-1 text-[11px] text-gray-500">Recommended for your query</p>
        </div>
      </div>

      <div className="mt-2 flex items-center gap-2 text-xs">
        <Link href={href} className="rounded-md bg-orange-50 px-2 py-1 font-semibold text-orange-700 hover:bg-orange-100">
          View details
        </Link>
        <Link href={href} className="rounded-md bg-blue-50 px-2 py-1 font-semibold text-blue-700 hover:bg-blue-100">
          Add to cart
        </Link>
      </div>
    </div>
  )
}

export function ChatbotWidget() {
  const [open, setOpen] = useState(false)
  const [input, setInput] = useState('')
  const [loading, setLoading] = useState(false)
  const [sessionId, setSessionId] = useState<string>('')
  const [messages, setMessages] = useState<Msg[]>([
    {
      id: makeMsgId(),
      sender: 'ai',
      text: "Hi! I'm your PetCare AI assistant. Ask me about food, grooming, care tips, or product suggestions.",
      createdAt: Date.now(),
    },
  ])

  const listRef = useRef<HTMLDivElement | null>(null)

  useEffect(() => {
    const key = 'pm_chatbot_session_id'
    let sid = localStorage.getItem(key)
    if (!sid) {
      sid = crypto.randomUUID()
      localStorage.setItem(key, sid)
    }
    setSessionId(sid)
  }, [])

  useEffect(() => {
    if (!listRef.current) return
    listRef.current.scrollTo({ top: listRef.current.scrollHeight, behavior: 'smooth' })
  }, [messages, loading, open])

  const canSend = useMemo(() => input.trim().length > 1 && !loading, [input, loading])

  async function onSend(messageText?: string) {
    const text = (messageText ?? input).trim()
    if (!text || loading) return

    setMessages((prev) => [
      ...prev,
      { id: makeMsgId(), sender: 'user', text, createdAt: Date.now() },
    ])
    setInput('')
    setLoading(true)

    try {
      const data = await sendChatbotMessage({ message: text, session_id: sessionId || undefined })
      if (data.session_id && data.session_id !== sessionId) {
        localStorage.setItem('pm_chatbot_session_id', data.session_id)
        setSessionId(data.session_id)
      }

      setMessages((prev) => [
        ...prev,
        {
          id: makeMsgId(),
          sender: 'ai',
          text: data.reply,
          safety: data.safety_level,
          vetWarning: data.vet_warning,
          intent: data.intent,
          petType: data.pet_type,
          category: data.category,
          ageGroup: data.age_group,
          products: data.recommended_products,
          createdAt: Date.now(),
        },
      ])
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        {
          id: makeMsgId(),
          sender: 'ai',
          text:
            'Sorry, I am having trouble connecting right now. You can still browse recommended pet products or try again.',
          isError: true,
          createdAt: Date.now(),
        },
      ])
      console.error(getErrorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      {!open && (
        <div className="fixed bottom-5 right-5 z-50">
          <button
            onClick={() => setOpen(true)}
            className="group relative flex items-center gap-2 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-white shadow-2xl transition-all hover:scale-[1.03] hover:shadow-orange-200"
            aria-label="Open PetCare AI Assistant"
          >
            <span className="absolute -inset-1 -z-10 animate-pulse rounded-full bg-orange-400/35 blur-md" />
            <MessageCircle className="h-5 w-5" />
            <span className="hidden text-sm font-semibold sm:inline">Ask PetCare AI</span>
          </button>
        </div>
      )}

      {open && (
        <div className="fixed inset-0 z-50 flex items-end justify-end sm:bottom-5 sm:right-5 sm:inset-auto">
          <div className="absolute inset-0 bg-black/20 sm:hidden" onClick={() => setOpen(false)} />

          <div className="relative z-10 flex h-[88dvh] w-full flex-col overflow-hidden rounded-t-3xl border border-gray-200 bg-white/95 shadow-2xl backdrop-blur sm:h-[700px] sm:max-h-[86vh] sm:w-[420px] sm:rounded-3xl">
            <div className="border-b border-gray-100 bg-gradient-to-r from-orange-50 via-white to-indigo-50 px-4 py-3">
              <div className="flex items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-2">
                  <div className="relative grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-500 text-white shadow-sm">
                    <PawPrint className="h-5 w-5" />
                    <span className="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500" />
                  </div>
                  <div className="min-w-0">
                    <p className="truncate text-sm font-bold text-gray-900">PetCare AI Assistant</p>
                    <p className="truncate text-xs text-gray-500">Care tips, product help & safe guidance</p>
                  </div>
                </div>
                <button
                  onClick={() => setOpen(false)}
                  className="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                  aria-label="Close chatbot"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
              <div className="mt-2">
                <AIChip label="PetCare AI can share general guidance, not veterinary diagnosis." className="text-[11px]" />
              </div>
            </div>

            <div ref={listRef} className="flex-1 space-y-3 overflow-y-auto px-3 py-3 sm:px-4">
              {messages.map((m) => {
                const emergency = isEmergencyMessage(m.safety, m.vetWarning, m.text)
                const isAI = m.sender === 'ai'

                return (
                  <div key={m.id} className={`flex ${isAI ? 'justify-start' : 'justify-end'}`}>
                    <div className={`max-w-[88%] ${isAI ? '' : 'items-end'}`}>
                      {isAI && (
                        <div className="mb-1 flex items-center gap-1.5 pl-1 text-[11px] text-gray-400">
                          <Sparkles className="h-3 w-3" /> PetCare AI
                        </div>
                      )}

                      <div
                        className={
                          isAI
                            ? `rounded-2xl rounded-tl-sm border px-3 py-2 text-sm leading-6 shadow-sm ${m.isError ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-gray-200 bg-white text-gray-800'}`
                            : 'rounded-2xl rounded-tr-sm bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-2 text-sm leading-6 text-white shadow-sm'
                        }
                      >
                        {m.text}
                      </div>

                      {isAI && (m.intent || m.petType || m.category || m.ageGroup) && (
                        <div className="mt-1 flex flex-wrap gap-1">
                          {m.intent && <Badge variant="ai">{m.intent}</Badge>}
                          {m.petType && <Badge variant="brand">{m.petType}</Badge>}
                          {m.category && <Badge variant="neutral">{m.category}</Badge>}
                          {m.ageGroup && <Badge variant="neutral">{m.ageGroup}</Badge>}
                        </div>
                      )}

                      {emergency && (
                        <EmergencyWarningCard
                          message={
                            m.vetWarning ||
                            'This may be serious. Please contact a veterinarian as soon as possible.'
                          }
                        />
                      )}

                      {(m.products?.length ?? 0) > 0 && (
                        <div className="mt-2 space-y-2">
                          {m.products!.map((p, idx) => (
                            <ProductRecommendationCard key={`${m.id}-${p.id ?? idx}`} product={p} />
                          ))}
                        </div>
                      )}
                    </div>
                  </div>
                )
              })}

              {loading && <TypingIndicator />}
            </div>

            <div className="border-t border-gray-100 bg-white px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-2 sm:px-4">
              <div className="mb-2 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                {QUICK_QUESTIONS.map((q) => (
                  <button
                    key={q.label}
                    type="button"
                    onClick={() => onSend(q.label)}
                    className="shrink-0 rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-medium text-orange-700 transition hover:-translate-y-px hover:bg-orange-100"
                  >
                    <span className="mr-1 text-[10px] text-orange-500">{q.group}</span>
                    {q.label}
                  </button>
                ))}
              </div>

              <div className="flex items-end gap-2">
                <textarea
                  value={input}
                  onChange={(e) => setInput(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                      e.preventDefault()
                      onSend()
                    }
                  }}
                  placeholder="Ask about food, grooming, care, or products..."
                  rows={1}
                  aria-label="Chat message input"
                  className="max-h-28 min-h-[42px] w-full resize-none rounded-2xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-orange-300 focus:bg-white focus:ring-2 focus:ring-orange-200"
                />
                <button
                  type="button"
                  onClick={() => onSend()}
                  disabled={!canSend}
                  aria-label="Send message"
                  className="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-sm transition hover:scale-[1.03] disabled:cursor-not-allowed disabled:opacity-55"
                >
                  {loading ? <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" /> : <Send className="h-4 w-4" />}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
