<script setup lang="ts">
import { useOffline } from '@/composables/useOffline'
import { computed } from 'vue'

const { isOffline, wasOffline } = useOffline()

const showReconnect = computed(() => !isOffline.value && wasOffline.value)

let reconnectTimer: ReturnType<typeof setTimeout> | null = null
const showBanner = computed(() => {
  if (showReconnect.value) {
    // Auto-hide reconnect banner after 3 seconds
    if (!reconnectTimer) {
      reconnectTimer = setTimeout(() => {
        wasOffline.value = false
      }, 3000)
    }
    return true
  }
  reconnectTimer = null
  return isOffline.value
})
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="-translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="-translate-y-full opacity-0"
  >
    <div
      v-if="showBanner"
      class="fixed left-0 right-0 top-0 z-[9999] flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium"
      :class="isOffline ? 'bg-destructive text-destructive-foreground' : 'bg-emerald-500 text-white'"
    >
      <template v-if="isOffline">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="1" y1="1" x2="23" y2="23"/>
          <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
          <path d="M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
          <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
          <line x1="12" y1="20" x2="12.01" y2="20"/>
        </svg>
        <span>Vous êtes hors ligne</span>
      </template>
      <template v-else>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span>Connexion rétablie</span>
      </template>
    </div>
  </Transition>
</template>
