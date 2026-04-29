'use client'

import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useReducer,
} from 'react'
import {
  apiGetMe,
  apiLogin,
  apiLogout,
  apiRegister,
  clearToken,
  hasToken,
  saveToken,
} from '@/lib/auth'
import type {
  AuthContextValue,
  AuthState,
  LoginInput,
  RegisterInput,
  User,
} from '@/types'

// ── Reducer ───────────────────────────────────────────────────────────────────

type AuthAction =
  | { type: 'SET_LOADING' }
  | { type: 'SET_USER'; user: User }
  | { type: 'SET_UNAUTHENTICATED' }

function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'SET_LOADING':
      return { status: 'loading' }
    case 'SET_USER':
      return { status: 'authenticated', user: action.user }
    case 'SET_UNAUTHENTICATED':
      return { status: 'unauthenticated' }
    default:
      return state
  }
}

// ── Context ───────────────────────────────────────────────────────────────────

const AuthContext = createContext<AuthContextValue | null>(null)

// ── Provider ──────────────────────────────────────────────────────────────────

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(authReducer, { status: 'idle' })

  // On mount — try to restore session from saved token
  useEffect(() => {
    if (!hasToken()) {
      dispatch({ type: 'SET_UNAUTHENTICATED' })
      return
    }

    dispatch({ type: 'SET_LOADING' })

    apiGetMe()
      .then((user) => dispatch({ type: 'SET_USER', user }))
      .catch(() => {
        clearToken()
        dispatch({ type: 'SET_UNAUTHENTICATED' })
      })
  }, [])

  const login = useCallback(async (input: LoginInput): Promise<User> => {
    dispatch({ type: 'SET_LOADING' })
    const response = await apiLogin(input)
    saveToken(response.data.token.access_token)
    dispatch({ type: 'SET_USER', user: response.data.user })
    return response.data.user
  }, [])

  const register = useCallback(async (input: RegisterInput) => {
    dispatch({ type: 'SET_LOADING' })
    const response = await apiRegister(input)
    saveToken(response.data.token.access_token)
    dispatch({ type: 'SET_USER', user: response.data.user })
  }, [])

  const logout = useCallback(async () => {
    try {
      await apiLogout()
    } finally {
      clearToken()
      dispatch({ type: 'SET_UNAUTHENTICATED' })
    }
  }, [])

  const refreshUser = useCallback(async () => {
    try {
      const user = await apiGetMe()
      dispatch({ type: 'SET_USER', user })
    } catch {
      clearToken()
      dispatch({ type: 'SET_UNAUTHENTICATED' })
    }
  }, [])

  const user = state.status === 'authenticated' ? state.user : null

  const value: AuthContextValue = {
    state,
    user,
    isAuthenticated: state.status === 'authenticated',
    isAdmin: user?.role === 'admin',
    isLoading: state.status === 'loading' || state.status === 'idle',
    login,
    register,
    logout,
    refreshUser,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

// ── Hook ──────────────────────────────────────────────────────────────────────

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used inside <AuthProvider>')
  }
  return ctx
}
