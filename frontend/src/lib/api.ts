import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios'
import Cookies from 'js-cookie'

export const TOKEN_KEY = 'pm_token'

function resolveApiBaseUrl(): string {
  // Server-side (inside Docker/container) should use internal network URL when available.
  if (typeof window === 'undefined' && process.env.INTERNAL_API_URL) {
    return process.env.INTERNAL_API_URL
  }

  if (process.env.NEXT_PUBLIC_API_URL) {
    // Prefer IPv4 loopback in local dev to avoid localhost/IPv6 resolution mismatches.
    return process.env.NEXT_PUBLIC_API_URL.replace('://localhost:', '://127.0.0.1:')
  }

  if (typeof window !== 'undefined') {
    const protocol = window.location.protocol
    const host = window.location.hostname
    return `${protocol}//${host}:8000/api`
  }

  return 'http://127.0.0.1:8000/api'
}

const apiBaseUrl = resolveApiBaseUrl()

const api = axios.create({
  baseURL: apiBaseUrl,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

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
    const requestUrl = originalRequest.url ?? ''
    const isPublicAuthRequest = [
      '/auth/login',
      '/auth/register',
      '/auth/forgot-password',
      '/auth/reset-password',
    ].some((path) => requestUrl.includes(path))

    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !requestUrl.includes('auth/refresh') &&
      !isPublicAuthRequest
    ) {
      originalRequest._retry = true

      if (isRefreshing) {
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

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const errors = error.response?.data?.errors as
      | Record<string, string[]>
      | undefined
    if (errors) {
      const firstField = Object.values(errors)[0]
      if (firstField?.[0]) return firstField[0]
    }

    if (error.response?.data?.message) {
      return error.response.data.message as string
    }

    if (error.message === 'Network Error' || (error as AxiosError).code === 'ERR_NETWORK') {
      return `Cannot connect to the server (${apiBaseUrl}). Please check backend server and CORS.`
    }
  }
  return 'An unexpected error occurred. Please try again.'
}
