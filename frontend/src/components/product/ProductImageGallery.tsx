'use client'

import React, { useState } from 'react'
import Image from 'next/image'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { clsx } from 'clsx'

interface ProductImageGalleryProps {
  images: string[]
  productName: string
}

export function ProductImageGallery({ images, productName }: ProductImageGalleryProps) {
  const normalized = images.length ? images : ['/placeholder-product.png']
  const [activeIndex, setActiveIndex] = useState(0)
  const [broken, setBroken] = useState<Record<number, boolean>>({})

  const prev = () => setActiveIndex((i) => (i === 0 ? normalized.length - 1 : i - 1))
  const next = () => setActiveIndex((i) => (i === normalized.length - 1 ? 0 : i + 1))

  return (
    <div className="flex flex-col gap-3">
      {/* Main image */}
      <div className="relative aspect-square w-full rounded-2xl overflow-hidden bg-gray-100 group">
        <Image
          src={broken[activeIndex] ? '/placeholder-product.png' : normalized[activeIndex]}
          alt={`${productName} — image ${activeIndex + 1}`}
          fill
          className="object-cover"
          sizes="(max-width: 768px) 100vw, 50vw"
          priority
          onError={() => setBroken((b) => ({ ...b, [activeIndex]: true }))}
        />

        {normalized.length > 1 && (
          <>
            <button
              onClick={prev}
              className="absolute left-3 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/80 hover:bg-white shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
              aria-label="Previous image"
            >
              <ChevronLeft className="h-4 w-4 text-gray-700" />
            </button>
            <button
              onClick={next}
              className="absolute right-3 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/80 hover:bg-white shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
              aria-label="Next image"
            >
              <ChevronRight className="h-4 w-4 text-gray-700" />
            </button>

            {/* Dots */}
            <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
              {normalized.map((_, i) => (
                <button
                  key={i}
                  onClick={() => setActiveIndex(i)}
                  className={clsx(
                    'h-1.5 rounded-full transition-all',
                    i === activeIndex ? 'w-4 bg-white' : 'w-1.5 bg-white/60',
                  )}
                  aria-label={`Go to image ${i + 1}`}
                />
              ))}
            </div>
          </>
        )}
      </div>

      {/* Thumbnail strip */}
      {normalized.length > 1 && (
        <div className="flex gap-2 overflow-x-auto pb-1">
          {normalized.map((src, i) => (
            <button
              key={i}
              onClick={() => setActiveIndex(i)}
              className={clsx(
                'relative h-16 w-16 shrink-0 rounded-xl overflow-hidden border-2 transition-all',
                i === activeIndex
                  ? 'border-orange-500 opacity-100'
                  : 'border-transparent opacity-60 hover:opacity-90',
              )}
            >
              <Image
                src={broken[i] ? '/placeholder-product.png' : src}
                alt={`Thumbnail ${i + 1}`}
                fill
                className="object-cover"
                sizes="64px"
                onError={() => setBroken((b) => ({ ...b, [i]: true }))}
              />
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
