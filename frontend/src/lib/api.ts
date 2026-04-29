import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios'
import Cookies from 'js-cookie'

// ── Constants ─────────────────────────────────────────────────────────────────

export const TOKEN_KEY = 'pm_token'

// ── Axios instance ────────────────────────────────────────────────────────────

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

// ── Request interceptor — attach JWT token ────────────────────────────────────

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = Cookies.get(TOKEN_KEY)
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// ── Response interceptor — handle 401, attempt token refresh ─────────────────

let isRefreshing = false
let refreshSubscribers: Array<(token: string) => void> = []

function subscribeTokenRefresh(cb: (token: string) => void) {
  refreshSubscribers.push(cb)
}

function onTokenRefreshed(token: string) {
  refreshSubscribers.forEach((cb) => cb(token))
  refreshSubscribers = []
}

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & {
      _retry?: boolean
    }

    // Only attempt refresh on 401, not on the refresh endpoint itself
    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !originalRequest.url?.includes('auth/refresh')
    ) {
      originalRequest._retry = true

      if (isRefreshing) {
        // Queue this request until the refresh completes
        return new Promise((resolve) => {
          subscribeTokenRefresh((newToken: string) => {
            if (originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${newToken}`
            }
            resolve(api(originalRequest))
          })
        })
      }

      isRefreshing = true

      try {
        const { data } = await api.post('/auth/refresh')
        const newToken: string = data.data.token.access_token

        Cookies.set(TOKEN_KEY, newToken, { expires: 14, sameSite: 'strict' })

        onTokenRefreshed(newToken)

        if (originalRequest.headers) {
          originalRequest.headers.Authorization = `Bearer ${newToken}`
        }

        return api(originalRequest)
      } catch {
        // Refresh failed — clear token and redirect to login
        Cookies.remove(TOKEN_KEY)
        if (typeof window !== 'undefined') {
          window.location.href = '/login'
        }
        return Promise.reject(error)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  },
)

export default api

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Extract a user-friendly error message from an Axios error.
 */
export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    // Validation errors — return first field error
    const errors = error.response?.data?.errors as
      | Record<string, string[]>
      | undefined
    if (errors) {
      const firstField = Object.values(errors)[0]
      if (firstField?.[0]) return firstField[0]
    }
    // API-level message
    if (error.response?.data?.message) {
      return error.response.data.message as string
    }
    // Network error
    if (error.message === 'Network Error') {
      return 'Cannot connect to the server. Please check your connection.'
    }
  }
  return 'An unexpected error occurred. Please try again.'
}
