'use client'

import { useCallback, useEffect, useState } from 'react'
import { fetchProfile, updateProfile, uploadAvatar, type UpdateProfileInput } from '@/lib/profile'
import type { User } from '@/types'

export function useProfile() {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [successMsg, setSuccessMsg] = useState<string | null>(null)

  useEffect(() => {
    fetchProfile()
      .then(setUser)
      .catch(() => setError('Failed to load profile.'))
      .finally(() => setLoading(false))
  }, [])

  const update = useCallback(async (input: UpdateProfileInput) => {
    setSaving(true)
    setError(null)
    setSuccessMsg(null)
    try {
      const updated = await updateProfile(input)
      setUser(updated)
      setSuccessMsg('Profile updated successfully.')
    } catch (err: any) {
      const msg =
        err?.response?.data?.errors
          ? Object.values(err.response.data.errors as Record<string, string[]>)
              .flat()[0]
          : err?.response?.data?.message ?? 'Failed to update profile.'
      setError(msg)
    } finally {
      setSaving(false)
    }
  }, [])

  const changeAvatar = useCallback(async (file: File) => {
    setSaving(true)
    setError(null)
    try {
      await uploadAvatar(file)
      const refreshed = await fetchProfile()
      setUser(refreshed)
      setSuccessMsg('Avatar updated.')
    } catch {
      setError('Failed to upload avatar.')
    } finally {
      setSaving(false)
    }
  }, [])

  return { user, loading, saving, error, successMsg, update, changeAvatar }
}
