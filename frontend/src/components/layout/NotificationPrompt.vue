<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useNotificationPermission } from '@/composables/useNotificationPermission'

const { t } = useI18n()
const { shouldPrompt, enable, dismiss } = useNotificationPermission()

async function handleEnable() {
  await enable()
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-4 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-4 opacity-0"
  >
    <div
      v-if="shouldPrompt"
      class="fixed bottom-[calc(3.5rem+max(env(safe-area-inset-bottom),12px)+12px)] left-3 right-3 z-[9997] rounded-xl border bg-card shadow-xl md:bottom-4 md:left-auto md:right-4 md:max-w-sm"
    >
      <div class="flex flex-col gap-3 p-4">
        <div class="flex items-start gap-3">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
              <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold">{{ t('notifications.prompt.title') }}</p>
            <p class="mt-0.5 text-xs text-muted-foreground">{{ t('notifications.prompt.body') }}</p>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            class="flex-1 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            @click="handleEnable"
          >
            {{ t('notifications.prompt.enable') }}
          </button>
          <button
            class="flex-1 rounded-md border px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            @click="dismiss"
          >
            {{ t('notifications.prompt.dismiss') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
