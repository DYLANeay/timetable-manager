import { ref, onMounted, onUnmounted } from 'vue'

const isOffline = ref(false)
const wasOffline = ref(false)

function updateStatus() {
  const offline = !navigator.onLine
  if (isOffline.value !== offline) {
    isOffline.value = offline
    if (!offline) wasOffline.value = true
  }
}

export function useOffline() {
  onMounted(() => {
    window.addEventListener('online', updateStatus)
    window.addEventListener('offline', updateStatus)
    updateStatus()
  })

  onUnmounted(() => {
    window.removeEventListener('online', updateStatus)
    window.removeEventListener('offline', updateStatus)
  })

  return { isOffline, wasOffline }
}

export function getOfflineStatus() {
  return { isOffline: isOffline.value, wasOffline: wasOffline.value }
}

