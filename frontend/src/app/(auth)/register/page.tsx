'use client'

import React, { useState } from 'react'
import Link from 'next/link'
import { Eye, EyeOff } from 'lucide-react'
import { useAuth } from '@/context/AuthContext'
import { getErrorMessage } from '@/lib/api'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { SocialAuthButtons } from '@/components/auth/SocialAuthButtons'

export default function RegisterPage() {
  const { register } = useAuth()

  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [showPassword, setShowPassword] = useState(false)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [globalError, setGlobalError] = useState('')
  const [loading, setLoading] = useState(false)

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
    setErrors((prev) => ({ ...prev, [name]: '' }))
    setGlobalError('')
  }

  function validate(): boolean {
    const newErrors: Record<string, string> = {}

    if (!form.name.trim()) newErrors.name = 'Name is required.'
    if (!form.email.trim()) newErrors.email = 'Email is required.'
    if (form.password.length < 8)
      newErrors.password = 'Password must be at least 8 characters.'
    if (form.password !== form.password_confirmation)
      newErrors.password_confirmation = 'Passwords do not match.'

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!validate()) return

    setLoading(true)
    setGlobalError('')

    try {
      await register(form)
      window.location.href = '/'
    } catch (err) {
      setGlobalError(getErrorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth-card">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Create account</h1>
        <p className="mt-1 text-sm text-gray-500">
          Join thousands of pet owners in Bangladesh
        </p>
      </div>

      {globalError && (
        <Alert variant="error" message={globalError} className="mb-5" />
      )}

      <form onSubmit={handleSubmit} className="flex flex-col gap-4" noValidate>
        <Input
          label="Full name"
          type="text"
          name="name"
          value={form.name}
          onChange={handleChange}
          placeholder="Redwan Ahmed"
          autoComplete="name"
          error={errors.name}
          required
        />

        <Input
          label="Email address"
          type="email"
          name="email"
          value={form.email}
          onChange={handleChange}
          placeholder="you@example.com"
          autoComplete="email"
          error={errors.email}
          required
        />

        <div className="relative">
          <Input
            label="Password"
            type={showPassword ? 'text' : 'password'}
            name="password"
            value={form.password}
            onChange={handleChange}
            placeholder="Min 8 characters"
            autoComplete="new-password"
            error={errors.password}
            helperText="Must include uppercase, lowercase, number and special character."
            required
          />
          <button
            type="button"
            onClick={() => setShowPassword((v) => !v)}
            className="absolute right-3 top-[38px] text-slate-400 hover:text-slate-600"
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
          label="Confirm password"
          type={showPassword ? 'text' : 'password'}
          name="password_confirmation"
          value={form.password_confirmation}
          onChange={handleChange}
          placeholder="Repeat your password"
          autoComplete="new-password"
          error={errors.password_confirmation}
          required
        />

        <Button type="submit" fullWidth loading={loading} size="lg" className="mt-1">
          Create Account
        </Button>
      </form>

      <div className="mt-5">
        <SocialAuthButtons label="sign up" />
      </div>

      <p className="mt-6 text-center text-sm text-gray-600">
        Already have an account?{' '}
        <Link href="/login" className="link-brand">
          Sign in
        </Link>
      </p>

    </div>
  )
}
