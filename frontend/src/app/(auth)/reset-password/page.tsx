'use client'

import React, { Suspense, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { CheckCircle2, Eye, EyeOff } from 'lucide-react'
import { apiResetPassword } from '@/lib/auth'
import { getErrorMessage } from '@/lib/api'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'

function ResetPasswordForm() {
  const router = useRouter()
  const searchParams = useSearchParams()

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const [form, setForm] = useState({
    password: '',
    password_confirmation: '',
  })
  const [showPassword, setShowPassword] = useState(false)
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')

  // Guard — if no token/email in URL, show an error immediately
  if (!token || !email) {
    return (
      <div className="auth-card text-center">
        <Alert
          variant="error"
          title="Invalid link"
          message="This password reset link is invalid or has expired. Please request a new one."
        />
        <Link href="/forgot-password" className="link-brand mt-4 inline-block text-sm">
          Request new link
        </Link>
      </div>
    )
  }

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))
    setError('')
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()

    if (form.password !== form.password_confirmation) {
      setError('Passwords do not match.')
      return
    }
    if (form.password.length < 8) {
      setError('Password must be at least 8 characters.')
      return
    }

    setLoading(true)
    setError('')

    try {
      await apiResetPassword({
        token,
        email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
      setSuccess(true)
    } catch (err) {
      setError(getErrorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  if (success) {
    return (
      <div className="auth-card text-center">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
          <CheckCircle2 className="h-8 w-8 text-green-600" />
        </div>
        <h1 className="text-2xl font-bold text-gray-900">Password reset!</h1>
        <p className="mt-2 text-sm text-gray-500">
          Your password has been updated successfully.
        </p>
        <Button
          fullWidth
          size="lg"
          className="mt-6"
          onClick={() => router.push('/login')}
        >
          Sign In Now
        </Button>
      </div>
    )
  }

  return (
    <div className="auth-card">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Reset password</h1>
        <p className="mt-1 text-sm text-gray-500">
          Enter your new password below.
        </p>
      </div>

      {error && (
        <Alert variant="error" message={error} className="mb-5" />
      )}

      <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate>
        <div className="relative">
          <Input
            label="New password"
            type={showPassword ? 'text' : 'password'}
            name="password"
            value={form.password}
            onChange={handleChange}
            placeholder="Min 8 characters"
            autoComplete="new-password"
            helperText="Must include uppercase, lowercase, number and special character."
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

        <Input
          label="Confirm new password"
          type={showPassword ? 'text' : 'password'}
          name="password_confirmation"
          value={form.password_confirmation}
          onChange={handleChange}
          placeholder="Repeat your new password"
          autoComplete="new-password"
          required
        />

        <Button type="submit" fullWidth loading={loading} size="lg">
          Reset Password
        </Button>
      </form>
    </div>
  )
}

// Wrap in Suspense because useSearchParams requires it in Next.js 14
export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<div className="auth-card animate-pulse h-64" />}>
      <ResetPasswordForm />
    </Suspense>
  )
}
