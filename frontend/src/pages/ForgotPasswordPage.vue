<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api, ApiError } from '@/api/client'

const { t, locale } = useI18n()

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const error = ref('')

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    await api('/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email: email.value, locale: locale.value }),
    })
    sent.value = true
  } catch (e) {
    if (e instanceof ApiError) {
      const messages = (e.data as { errors?: Record<string, string[]> }).errors
      error.value = messages ? Object.values(messages).flat().join(' ') : t('auth.unexpectedError')
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
        <h1 class="text-2xl font-bold tracking-tight">{{ t('auth.forgotPassword') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ t('auth.forgotPasswordSubtitle') }}</p>
      </div>

      <div v-if="sent" class="space-y-4">
        <div class="rounded-md bg-primary/10 p-3 text-sm text-primary">
          {{ t('auth.resetLinkSent') }}
        </div>
        <div class="text-center">
          <router-link to="/login" class="text-sm text-primary hover:underline">
            {{ t('auth.backToLogin') }}
          </router-link>
        </div>
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div v-if="error" class="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
          {{ error }}
        </div>

        <div class="space-y-2">
          <label for="email" class="text-sm font-medium">{{ t('auth.email') }}</label>
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

        <button
          type="submit"
          :disabled="loading"
          class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
        >
          {{ loading ? t('common.loading') : t('auth.sendResetLink') }}
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
