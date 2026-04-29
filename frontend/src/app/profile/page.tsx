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
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">My Profile</h1>

      {error && <Alert variant="error" message={error} className="mb-4" />}
      {successMsg && (
        <Alert variant="success" message={successMsg} className="mb-4" />
      )}

      {/* ── Profile card ──────────────────────────────────────────────────── */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-5">

        {/* Header with avatar */}
        <div className="bg-gradient-to-r from-orange-500 to-amber-400 h-24 relative" />
        <div className="px-6 pb-6">
          <div className="flex items-end justify-between -mt-12 mb-4">
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
          <div className="flex flex-wrap gap-4 text-sm text-gray-500 mb-6">
            <span className="flex items-center gap-1.5">
              <Mail className="h-4 w-4 text-gray-400" />
              {user.email}
            </span>
            <span className="flex items-center gap-1.5">
              <Shield className="h-4 w-4 text-gray-400" />
              <span className="capitalize">{user.role}</span>
            </span>
            <span className="flex items-center gap-1.5">
              <Calendar className="h-4 w-4 text-gray-400" />
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
                  <p className="text-xs font-medium text-gray-400 uppercase tracking-wide mb-0.5">
                    {label}
                  </p>
                  <p className="text-sm font-medium text-gray-900">{value}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* ── Account status card ───────────────────────────────────────────── */}
      <div className="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 className="text-sm font-semibold text-gray-900 mb-4">
          Account Status
        </h2>
        <div className="flex items-center gap-3">
          <div
            className={`h-2.5 w-2.5 rounded-full ${
              user.is_active ? 'bg-green-500' : 'bg-red-500'
            }`}
          />
          <span className="text-sm text-gray-700">
            {user.is_active ? 'Active' : 'Suspended'}
          </span>
        </div>
      </div>
    </div>
  )
}
