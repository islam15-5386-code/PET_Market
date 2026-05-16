'use client'

import React, { Suspense, useEffect } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { saveToken, apiGetMe } from '@/lib/auth'
import { Spinner } from '@/components/ui/Spinner'

function GoogleCallbackContent() {
  const router = useRouter()
  const searchParams = useSearchParams()

  useEffect(() => {
    let mounted = true

    async function completeGoogleLogin() {
      const token = searchParams.get('token')
      const error = searchParams.get('error')

      if (!token) {
        const message = error || 'Google sign-in failed. Please try again.'
        router.replace(`/login?error=${encodeURIComponent(message)}`)
        return
      }

      try {
        saveToken(token)
        await apiGetMe()
        if (!mounted) return
        window.location.replace('/')
      } catch {
        router.replace('/login?error=Unable to complete Google sign-in.')
      }
    }

    completeGoogleLogin()

    return () => {
      mounted = false
    }
  }, [searchParams, router])

  return <GoogleSigningFallback />
}

function GoogleSigningFallback() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center gap-4">
      <Spinner size="lg" />
      <p className="text-sm text-gray-500">Signing you in with Google...</p>
    </div>
  )
}

export default function GoogleCallbackPage() {
  return (
    <Suspense fallback={<GoogleSigningFallback />}>
      <GoogleCallbackContent />
    </Suspense>
  )
}
