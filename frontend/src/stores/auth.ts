import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { useRouter } from 'vue-router'
import type { User } from '@/types'
import * as authApi from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isManager = computed(() => user.value?.role === 'manager')

  async function login(email: string, password: string) {
    const response = await authApi.login(email, password)
    token.value = response.token
    user.value = response.user
    localStorage.setItem('auth_token', response.token)
  }

  async function logout() {
    try {
      await authApi.logout()
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      user.value = await authApi.fetchMe()
    } catch {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  return { user, token, isAuthenticated, isManager, login, logout, fetchUser }
})
