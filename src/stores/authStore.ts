import { create } from 'zustand'
import { authService } from '../services/authService'

export interface AuthUser {
  id: number
  username: string
  email: string
  full_name: string | null
  avatar_url: string | null
  is_premium: number
  subscription_plan: string | null
  subscription_expires_at: string | null
}

interface AuthState {
  user:      AuthUser | null
  token:     string | null
  isLoading: boolean
  setAuth:   (user: AuthUser, token: string) => void
  clearAuth: () => void
  setLoading:(v: boolean) => void
  initFromStorage: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set) => ({
  user:      null,
  token:     null,
  isLoading: true,

  setAuth: (user, token) => {
    localStorage.setItem('tc_token', token)
    set({ user, token })
  },

  clearAuth: () => {
    localStorage.removeItem('tc_token')
    set({ user: null, token: null })
  },

  setLoading: (v) => set({ isLoading: v }),

  initFromStorage: async () => {
    const token = localStorage.getItem('tc_token')
    if (!token) { set({ isLoading: false }); return }
    try {
      const data = await authService.verifyToken(token)
      if (data.status === 'success' && data.data && data.data.user) {
        set({ user: data.data.user, token, isLoading: false })
      } else {
        localStorage.removeItem('tc_token')
        set({ user: null, token: null, isLoading: false })
      }
    } catch (error) {
      // Don't log expected 401 (Unauthorized) errors as full initialization errors
      if (error instanceof Error && !error.message?.includes('status: 401')) {
        console.error('Auth initialization error:', error)
      }
      localStorage.removeItem('tc_token')
      set({ user: null, token: null, isLoading: false })
    }
  },
}))