import Cookies from 'js-cookie'
import api, { TOKEN_KEY } from './api'
import type {
  AuthResponse,
  LoginInput,
  RegisterInput,
  User,
} from '@/types'

// ── Token helpers ─────────────────────────────────────────────────────────────

export function saveToken(token: string): void {
  // Expires in 14 days — matches JWT_REFRESH_TTL on backend
  Cookies.set(TOKEN_KEY, token, {
    expires: 14,
    sameSite: 'strict',
    // secure: true,  // uncomment in production (HTTPS only)
  })
}

export function clearToken(): void {
  Cookies.remove(TOKEN_KEY)
}

export function getToken(): string | undefined {
  return Cookies.get(TOKEN_KEY)
}

export function hasToken(): boolean {
  return !!Cookies.get(TOKEN_KEY)
}

// ── Auth API ──────────────────────────────────────────────────────────────────

export async function apiRegister(input: RegisterInput): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/register', input)
  return data
}

export async function apiLogin(input: LoginInput): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/login', input)
  return data
}

export async function apiLogout(): Promise<void> {
  await api.post('/auth/logout')
  clearToken()
}

export async function apiRefreshToken(): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/refresh')
  return data
}

export async function apiGetMe(): Promise<User> {
  const { data } = await api.get<{ success: boolean; data: { user: User } }>(
    '/auth/me',
  )
  return data.data.user
}

export async function apiForgotPassword(email: string): Promise<string> {
  const { data } = await api.post<{ success: boolean; message: string }>(
    '/auth/forgot-password',
    { email },
  )
  return data.message
}

export async function apiResetPassword(payload: {
  token: string
  email: string
  password: string
  password_confirmation: string
}): Promise<string> {
  const { data } = await api.post<{ success: boolean; message: string }>(
    '/auth/reset-password',
    payload,
  )
  return data.message
}
