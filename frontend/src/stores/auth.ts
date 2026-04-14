import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { useRouter } from 'vue-router'
import type { User } from '@/types'
import * as authApi from '@/api/auth'
import { addToast } from '@/composables/useToast'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isManager = computed(() => user.value?.role === 'manager')

  async function login(email: string, password: string): Promise<{ success: boolean; error?: string }> {
    loading.value = true
    try {
      const response = await authApi.login(email, password)
      token.value = response.token
      user.value = response.user
      localStorage.setItem('auth_token', response.token)
      return { success: true }
    } catch (error) {
      let errorMessage = 'Échec de la connexion'
      if (error instanceof Error) {
        if (error.message.includes('401') || error.message.includes('422')) {
          errorMessage = 'Email ou mot de passe incorrect'
        } else if (error.message.includes('Network') || error.message.includes('fetch')) {
          errorMessage = 'Impossible de se connecter au serveur. Vérifiez votre connexion.'
        }
      }
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Silent fail on logout - we still want to clear local state
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser(): Promise<boolean> {
    if (!token.value) return false
    try {
      user.value = await authApi.fetchMe()
      return true
    } catch {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      return false
    }
  }

  return { user, token, loading, isAuthenticated, isManager, login, logout, fetchUser }
})
