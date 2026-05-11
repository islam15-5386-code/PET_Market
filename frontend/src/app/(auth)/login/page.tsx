'use client'

import React, { Suspense, useState } from 'react'
import Link from 'next/link'
import { useSearchParams } from 'next/navigation'
import { Eye, EyeOff } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import { getErrorMessage } from '@/lib/api'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { SocialAuthButtons } from '@/components/auth/SocialAuthButtons'

function LoginPageContent() {
  const searchParams = useSearchParams()
  const { login } = useAuth()

  const [form, setForm] = useState({ email: '', password: '' })
  const [rememberMe, setRememberMe] = useState(true)
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const redirectTo = searchParams.get('redirect') ?? '/'

  React.useEffect(() => {
    const savedEmail = window.localStorage.getItem('pm_remember_email')
    if (savedEmail) {
      setForm((prev) => ({ ...prev, email: savedEmail }))
      setRememberMe(true)
    }
  }, [])

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))
    setError('')
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true)
    setError('')

    try {
      const user = await login({ email: form.email, password: form.password })

      if (rememberMe) {
        window.localStorage.setItem('pm_remember_email', form.email.trim())
      } else {
        window.localStorage.removeItem('pm_remember_email')
      }

      // Use a hard navigation (window.location) so the browser sends the
      // freshly-set cookie on the next request and middleware sees it correctly.
      if (redirectTo && redirectTo !== '/') {
        window.location.href = redirectTo
      } else if (user.role === 'admin') {
        window.location.href = '/admin/dashboard'
      } else {
        window.location.href = '/'
      }
    } catch (err) {
      setError(getErrorMessage(err))
      setLoading(false)
    }
  }

  return (
    <div className="auth-card">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Welcome back</h1>
        <p className="mt-1 text-sm text-gray-500">
          Sign in to your Pet Marketplace account
        </p>
      </div>

      {error && (
        <Alert variant="error" message={error} className="mb-5" />
      )}

      <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate>
        <Input
          label="Email address"
          type="email"
          name="email"
          value={form.email}
          onChange={handleChange}
          placeholder="you@example.com"
          autoComplete="email"
          required
        />

        <div className="relative">
          <Input
            label="Password"
            type={showPassword ? 'text' : 'password'}
            name="password"
            value={form.password}
            onChange={handleChange}
            placeholder="••••••••"
            autoComplete="current-password"
            required
          />
          <button
            type="button"
            onClick={() => setShowPassword((v) => !v)}
            className="absolute right-3 top-[38px] text-gray-400 hover:text-gray-600"
            aria-label={showPassword ? 'Hide password' : 'Show password'}
          >
            {showPassword ? (
              <EyeOff className="h-4 w-4" />
            ) : (
              <Eye className="h-4 w-4" />
            )}
          </button>
        </div>

        <div className="flex justify-end">
          <div className="flex w-full items-center justify-between">
            <label className="flex items-center gap-2 text-sm text-gray-600">
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                className="h-4 w-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400"
              />
              Remember email
            </label>
            <Link href="/forgot-password" className="link-brand text-sm">
              Forgot password?
            </Link>
          </div>
        </div>

        <Button type="submit" fullWidth loading={loading} size="lg">
          Sign In
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-gray-600">
        Don&apos;t have an account?{' '}
        <Link href="/register" className="link-brand">
          Create one
        </Link>
      </p>
    </div>
  )
}

export default function LoginPage() {
  return (
    <Suspense fallback={<div className="auth-card" />}>
      <LoginPageContent />
    </Suspense>
  )
}
