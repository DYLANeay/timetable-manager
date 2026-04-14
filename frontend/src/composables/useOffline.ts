import { ref, onMounted, onUnmounted } from 'vue'

const isOffline = ref(false)
const wasOffline = ref(false)
const listeners = new Set<(online: boolean) => void>()

function updateStatus() {
  const offline = !navigator.onLine
  if (isOffline.value !== offline) {
    isOffline.value = offline
    if (!offline) wasOffline.value = true
    listeners.forEach(cb => cb(!offline))
  }
}

export function useOffline() {
  const notify = (online: boolean) => {
    // Component-level callback if needed
  }

  onMounted(() => {
    listeners.add(notify)
    window.addEventListener('online', updateStatus)
    window.addEventListener('offline', updateStatus)
    updateStatus()
  })

  onUnmounted(() => {
    listeners.delete(notify)
    window.removeEventListener('online', updateStatus)
    window.removeEventListener('offline', updateStatus)
  })

  return { isOffline, wasOffline }
}

export function getOfflineStatus() {
  return { isOffline: isOffline.value, wasOffline: wasOffline.value }
}

export function onConnectionRestore(callback: () => void) {
  const handler = () => {
    if (navigator.onLine) {
      callback()
    }
  }
  window.addEventListener('online', handler)
  return () => window.removeEventListener('online', handler)
}
