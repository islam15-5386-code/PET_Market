'use client'

import React, { useEffect } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { Spinner } from '@/components/ui/Spinner'
import { saveToken } from '@/lib/auth'

/**
 * Backend redirects here after a successful OAuth handshake:
 *   /social-callback?token=<jwt>&expires_in=<seconds>
 *
 * We save the token and redirect the user to the home page.
 */
export default function SocialCallbackPage() {
  const router = useRouter()
  const searchParams = useSearchParams()

  useEffect(() => {
    const token = searchParams.get('token')
    const error = searchParams.get('error')

    if (token) {
      saveToken(token)
      // Reload so AuthContext re-hydrates from the new cookie
      window.location.replace('/')
    } else {
      const msg = error ?? 'Social login failed. Please try again.'
      router.replace(`/login?error=${encodeURIComponent(msg)}`)
    }
  }, [searchParams, router])

  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4">
      <Spinner size="lg" />
      <p className="text-sm text-gray-500">Completing sign-in…</p>
    </div>
  )
}
