'use client'

import React, { useEffect, useRef, useState } from 'react'
import Image from 'next/image'
import { Camera, Check, RotateCcw, UploadCloud } from 'lucide-react'
import { Button } from '@/components/ui/Button'

interface AvatarUploadProps {
  currentUrl: string | null
  name: string
  onUpload: (file: File) => Promise<void>
  disabled?: boolean
}

export function AvatarUpload({ currentUrl, name, onUpload, disabled }: AvatarUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [uploading, setUploading] = useState(false)

  useEffect(() => {
    if (currentUrl) {
      setPreview(null)
      setSelectedFile(null)
    }
  }, [currentUrl])

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return

    setSelectedFile(file)

    // Local preview
    const reader = new FileReader()
    reader.onload = (ev) => setPreview(ev.target?.result as string)
    reader.readAsDataURL(file)
  }

  async function handleUpload() {
    if (!selectedFile) return
    setUploading(true)
    try {
      await onUpload(selectedFile)
      setSelectedFile(null)
      setPreview(null)
    } finally {
      setUploading(false)
    }
  }

  function handleResetSelection() {
    setSelectedFile(null)
    setPreview(null)
    if (inputRef.current) inputRef.current.value = ''
  }

  const displayUrl = preview ?? currentUrl
  const initials = name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)

  return (
    <div className="flex flex-col gap-3">
      <div className="relative h-28 w-28 group">
        {/* Avatar circle */}
        <div className="h-28 w-28 rounded-full overflow-hidden bg-orange-100 flex items-center justify-center ring-4 ring-white shadow-lg shadow-slate-500/20">
        {displayUrl ? (
          <Image
            src={displayUrl}
            alt={name}
            width={112}
            height={112}
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
      </div>

      {!disabled && (
        <div className="flex flex-wrap items-center gap-2">
          <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
          >
            <UploadCloud className="h-3.5 w-3.5" />
            Choose Photo
          </Button>
          {selectedFile && (
            <>
              <Button
                type="button"
                size="sm"
                onClick={handleUpload}
                loading={uploading}
                disabled={uploading}
              >
                <Check className="h-3.5 w-3.5" />
                Save Photo
              </Button>
              <Button
                type="button"
                size="sm"
                variant="ghost"
                onClick={handleResetSelection}
                disabled={uploading}
              >
                <RotateCcw className="h-3.5 w-3.5" />
                Cancel
              </Button>
            </>
          )}
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
