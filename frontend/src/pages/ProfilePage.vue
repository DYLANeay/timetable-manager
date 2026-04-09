<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { changePassword, updateAvatar } from '@/api/auth'
import { ApiError } from '@/api/client'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function handleAvatarClick() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = async (e) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    const reader = new FileReader()
    reader.onload = async () => {
      const dataUrl = reader.result as string
      const user = await updateAvatar(dataUrl)
      auth.user = user
    }
    reader.readAsDataURL(file)
  }
  input.click()
}

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
        <button
          class="relative h-12 w-12 shrink-0 overflow-hidden rounded-full bg-primary text-lg font-bold text-primary-foreground ring-2 ring-transparent transition hover:ring-primary"
          title="Changer la photo"
          @click="handleAvatarClick"
        >
          <img v-if="auth.user?.avatar" :src="auth.user.avatar" class="h-full w-full object-cover" alt="" />
          <span v-else>{{ auth.user?.name?.charAt(0)?.toUpperCase() }}</span>
          <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/30 opacity-0 transition hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          </div>
        </button>
        <div class="flex-1">
          <p class="font-semibold">{{ auth.user?.name }}</p>
          <p class="text-sm text-muted-foreground">{{ auth.user?.email }}</p>
          <Badge class="mt-1" :variant="auth.isManager ? 'default' : 'secondary'">
            {{ auth.isManager ? $t('employees.roleManager') : $t('employees.roleEmployee') }}
          </Badge>
        </div>
        <Button variant="ghost" class="text-destructive hover:text-destructive md:hidden" @click="handleLogout">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </Button>
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
