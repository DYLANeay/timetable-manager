<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api, ApiError } from '@/api/client'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')
const success = ref(false)

const token = route.query.token as string
const email = route.query.email as string

async function handleSubmit() {
  error.value = ''

  if (password.value !== passwordConfirmation.value) {
    error.value = t('profile.passwordMismatch')
    return
  }

  loading.value = true
  try {
    await api('/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({
        token,
        email,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      }),
    })
    success.value = true
    setTimeout(() => router.push('/login'), 2000)
  } catch (e) {
    if (e instanceof ApiError) {
      const data = e.data as { message?: string; errors?: Record<string, string[]> }
      const messages = data.errors
      error.value = messages
        ? Object.values(messages).flat().join(' ')
        : data.message || t('auth.unexpectedError')
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
        <h1 class="text-2xl font-bold tracking-tight">{{ t('auth.resetPassword') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ t('auth.resetPasswordSubtitle') }}</p>
      </div>

      <div v-if="success" class="space-y-4">
        <div class="rounded-md bg-primary/10 p-3 text-sm text-primary">
          {{ t('auth.passwordResetSuccess') }}
        </div>
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div v-if="error" class="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
          {{ error }}
        </div>

        <div class="space-y-2">
          <label for="password" class="text-sm font-medium">{{ t('profile.newPassword') }}</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="••••••••"
          />
        </div>

        <div class="space-y-2">
          <label for="password_confirmation" class="text-sm font-medium">{{ t('profile.confirmPassword') }}</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
        >
          {{ loading ? t('common.loading') : t('auth.resetPassword') }}
        </button>

        <div class="text-center">
          <router-link to="/login" class="text-sm text-muted-foreground hover:text-foreground">
            {{ t('auth.backToLogin') }}
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
