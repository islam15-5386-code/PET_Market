'use client'

import React, { useEffect, useState } from 'react'
import { Calendar, Mail, Shield, Edit3, Check, X } from 'lucide-react'
import { AvatarUpload } from '@/components/profile/AvatarUpload'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Spinner } from '@/components/ui/Spinner'
import { useProfile } from '@/hooks/useProfile'
import type { UpdateProfileInput } from '@/lib/profile'

export default function ProfilePage() {
  const { user, loading, saving, error, successMsg, update, changeAvatar } =
    useProfile()

  const [editing, setEditing] = useState(false)
  const [form, setForm] = useState<UpdateProfileInput>({})

  // Sync form when user loads
  useEffect(() => {
    if (user) {
      setForm({
        name:        user.name,
        email:       user.email,
        phone:       user.phone ?? '',
        address:     user.address ?? '',
        city:        user.city ?? '',
        postal_code: user.postal_code ?? '',
      })
    }
  }, [user])

  function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
    setForm((f) => ({ ...f, [e.target.name]: e.target.value }))
  }

  async function handleSave() {
    await update(form)
    setEditing(false)
  }

  function handleCancel() {
    if (user) {
      setForm({
        name:        user.name,
        email:       user.email,
        phone:       user.phone ?? '',
        address:     user.address ?? '',
        city:        user.city ?? '',
        postal_code: user.postal_code ?? '',
      })
    }
    setEditing(false)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Spinner size="lg" />
      </div>
    )
  }

  if (!user) {
    return (
      <div className="max-w-2xl mx-auto px-4 py-16 text-center text-gray-500">
        Could not load profile.
      </div>
    )
  }

  const joinedDate = new Date(user.created_at).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })

  return (
    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
      <h1 className="mb-6 text-3xl font-bold text-slate-900">My Profile</h1>

      {error && <Alert variant="error" message={error} className="mb-4" />}
      {successMsg && (
        <Alert variant="success" message={successMsg} className="mb-4" />
      )}

      {/* ── Profile card ──────────────────────────────────────────────────── */}
      <div className="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_20px_48px_-36px_rgba(15,23,42,.55)]">

        {/* Header with avatar */}
        <div className="relative h-32 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-400" />
        <div className="px-6 pb-6">
          <div className="-mt-14 mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <AvatarUpload
              currentUrl={user.avatar_url}
              name={user.name}
              onUpload={changeAvatar}
              disabled={saving}
            />
            {!editing ? (
              <Button
                variant="outline"
                size="sm"
                onClick={() => setEditing(true)}
              >
                <Edit3 className="h-3.5 w-3.5" />
                Edit Profile
              </Button>
            ) : (
              <div className="flex gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleCancel}
                  disabled={saving}
                >
                  <X className="h-3.5 w-3.5" />
                  Cancel
                </Button>
                <Button
                  size="sm"
                  onClick={handleSave}
                  loading={saving}
                >
                  <Check className="h-3.5 w-3.5" />
                  Save
                </Button>
              </div>
            )}
          </div>

          {/* Account meta */}
          <div className="mb-6 flex flex-wrap gap-4 text-sm text-slate-500">
            <span className="flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5">
              <Mail className="h-4 w-4 text-slate-400" />
              {user.email}
            </span>
            <span className="flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5">
              <Shield className="h-4 w-4 text-slate-400" />
              <span className="capitalize">{user.role}</span>
            </span>
            <span className="flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5">
              <Calendar className="h-4 w-4 text-slate-400" />
              Joined {joinedDate}
            </span>
          </div>

          {/* Editable fields */}
          {editing ? (
            <div className="flex flex-col gap-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="Full name"
                  name="name"
                  value={form.name ?? ''}
                  onChange={handleChange}
                  required
                />
                <Input
                  label="Phone"
                  name="phone"
                  value={form.phone ?? ''}
                  onChange={handleChange}
                  placeholder="01700000000"
                />
              </div>
              <Input
                label="Address"
                name="address"
                value={form.address ?? ''}
                onChange={handleChange}
                placeholder="House, Road, Area"
              />
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Input
                  label="City"
                  name="city"
                  value={form.city ?? ''}
                  onChange={handleChange}
                  placeholder="Dhaka"
                />
                <Input
                  label="Postal code"
                  name="postal_code"
                  value={form.postal_code ?? ''}
                  onChange={handleChange}
                  placeholder="1205"
                />
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
              {[
                { label: 'Full Name',    value: user.name },
                { label: 'Phone',        value: user.phone ?? '—' },
                { label: 'Address',      value: user.address ?? '—' },
                { label: 'City',         value: user.city ?? '—' },
                { label: 'Postal Code',  value: user.postal_code ?? '—' },
              ].map(({ label, value }) => (
                <div key={label}>
                  <p className="mb-0.5 text-xs font-medium uppercase tracking-wide text-slate-400">
                    {label}
                  </p>
                  <p className="text-base font-semibold text-slate-900">{value}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* ── Account status card ───────────────────────────────────────────── */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_20px_48px_-36px_rgba(15,23,42,.55)]">
        <h2 className="mb-4 text-sm font-semibold text-slate-900">
          Account Status
        </h2>
        <div className="flex items-center gap-3">
          <div
            className={`h-2.5 w-2.5 rounded-full ${
              user.is_active ? 'bg-green-500' : 'bg-red-500'
            }`}
          />
          <span className="text-sm text-slate-700">
            {user.is_active ? 'Active' : 'Suspended'}
          </span>
        </div>
      </div>
    </div>
  )
}
