import api from './api'
import type { ApiResponse, User } from '@/types'

export interface UpdateProfileInput {
  name?: string
  email?: string
  phone?: string
  address?: string
  city?: string
  postal_code?: string
}

export async function fetchProfile(): Promise<User> {
  const { data } = await api.get<ApiResponse<{ user: User }>>('/user/profile')
  return data.data!.user
}

export async function updateProfile(input: UpdateProfileInput): Promise<User> {
  const { data } = await api.put<ApiResponse<{ user: User }>>(
    '/user/profile',
    input,
  )
  return data.data!.user
}

export async function uploadAvatar(file: File): Promise<User> {
  const form = new FormData()
  form.append('avatar', file)
  const { data } = await api.post<ApiResponse<{ user: User }>>(
    '/user/profile/avatar',
    form,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  )
  return data.data!.user
}
