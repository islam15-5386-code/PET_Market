'use client'

import React, { useEffect, useRef, useState } from 'react'
import Image from 'next/image'
import { Camera, User } from 'lucide-react'

interface AvatarUploadProps {
  currentUrl: string | null
  name: string
  onUpload: (file: File) => Promise<void>
  disabled?: boolean
}

export function AvatarUpload({ currentUrl, name, onUpload, disabled }: AvatarUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [uploading, setUploading] = useState(false)

  useEffect(() => {
    if (currentUrl) {
      setPreview(null)
    }
  }, [currentUrl])

  async function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return

    // Local preview
    const reader = new FileReader()
    reader.onload = (ev) => setPreview(ev.target?.result as string)
    reader.readAsDataURL(file)

    setUploading(true)
    try {
      await onUpload(file)
    } finally {
      setUploading(false)
    }
  }

  const displayUrl = preview ?? currentUrl
  const initials = name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)

  return (
    <div className="relative w-24 h-24 group">
      {/* Avatar circle */}
      <div className="w-24 h-24 rounded-full overflow-hidden bg-orange-100 flex items-center justify-center ring-4 ring-white shadow-md">
        {displayUrl ? (
          <Image
            src={displayUrl}
            alt={name}
            width={96}
            height={96}
            className="object-cover w-full h-full"
            unoptimized={!!preview} // preview is a data URL
          />
        ) : (
          <span className="text-2xl font-bold text-orange-600">{initials}</span>
        )}
      </div>

      {/* Upload overlay */}
      {!disabled && (
        <button
          onClick={() => inputRef.current?.click()}
          disabled={uploading}
          className="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
          aria-label="Change avatar"
        >
          <Camera className="h-6 w-6 text-white" />
        </button>
      )}

      {/* Spinner overlay while uploading */}
      {uploading && (
        <div className="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center">
          <div className="h-5 w-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
        </div>
      )}

      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/jpg,image/png,image/webp"
        className="hidden"
        onChange={handleChange}
        aria-label="Upload avatar"
      />
    </div>
  )
}
