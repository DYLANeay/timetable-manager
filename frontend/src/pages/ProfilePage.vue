<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { changePassword } from '@/api/auth'
import { ApiError } from '@/api/client'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const { t } = useI18n()
const auth = useAuthStore()

const form = ref({ current_password: '', password: '', password_confirmation: '' })
const loading = ref(false)
const error = ref('')
const success = ref('')

async function handleChangePassword() {
  error.value = ''
  success.value = ''

  if (form.value.password !== form.value.password_confirmation) {
    error.value = t('profile.passwordMismatch')
    return
  }

  loading.value = true
  try {
    await changePassword({
      current_password: form.value.current_password,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    success.value = t('profile.passwordUpdated')
    form.value = { current_password: '', password: '', password_confirmation: '' }
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
  <div class="space-y-4 p-4 md:p-6">
    <h1 class="text-xl font-bold">{{ $t('profile.title') }}</h1>

    <!-- User info -->
    <Card>
      <CardContent class="flex items-center gap-4 p-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground">
          {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
        </div>
        <div>
          <p class="font-semibold">{{ auth.user?.name }}</p>
          <p class="text-sm text-muted-foreground">{{ auth.user?.email }}</p>
          <Badge class="mt-1" :variant="auth.isManager ? 'default' : 'secondary'">
            {{ auth.isManager ? $t('employees.roleManager') : $t('employees.roleEmployee') }}
          </Badge>
        </div>
      </CardContent>
    </Card>

    <!-- Change password -->
    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-base">{{ $t('profile.changePassword') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <form class="space-y-4" @submit.prevent="handleChangePassword">
          <div
            v-if="error"
            class="rounded-md bg-destructive/10 p-3 text-sm text-destructive"
          >
            {{ error }}
          </div>
          <div
            v-if="success"
            class="rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-400"
          >
            {{ success }}
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">{{ $t('profile.currentPassword') }}</label>
            <input
              v-model="form.current_password"
              type="password"
              required
              autocomplete="current-password"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">{{ $t('profile.newPassword') }}</label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">{{ $t('profile.confirmPassword') }}</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>

          <Button type="submit" :disabled="loading">
            {{ loading ? $t('profile.saving') : $t('profile.changePassword') }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
