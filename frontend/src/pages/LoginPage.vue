<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { ApiError } from '@/api/client'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/schedule')
  } catch (e) {
    if (e instanceof ApiError) {
      const messages = (e.data as { errors?: Record<string, string[]> }).errors
      error.value = messages ? Object.values(messages).flat().join(' ') : t('auth.loginFailed')
    } else {
      error.value = t('auth.unexpectedError')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-background px-4">
    <div class="w-full max-w-sm space-y-6">
      <div class="text-center">
        <h1 class="text-2xl font-bold tracking-tight">{{ $t('auth.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ $t('auth.subtitle') }}</p>
      </div>

      <form class="space-y-4" @submit.prevent="handleLogin">
        <div v-if="error" class="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
          {{ error }}
        </div>

        <div class="space-y-2">
          <label for="email" class="text-sm font-medium">{{ $t('auth.email') }}</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="you@station.com"
          />
        </div>

        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label for="password" class="text-sm font-medium">{{ $t('auth.password') }}</label>
            <router-link to="/forgot-password" class="text-xs text-muted-foreground hover:text-foreground">
              {{ $t('auth.forgotPassword') }}
            </router-link>
          </div>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
        >
          {{ loading ? $t('auth.signingIn') : $t('auth.signIn') }}
        </button>
      </form>
    </div>
  </div>
</template>
