import { ref, computed } from 'vue'

const STORAGE_KEY = 'notifications_prompt_answered'

export function useNotificationPermission() {
  const isSupported = typeof Notification !== 'undefined'

  const permission = ref<NotificationPermission>(
    isSupported ? Notification.permission : 'denied',
  )

  const hasAnswered = ref(!!localStorage.getItem(STORAGE_KEY))

  // Show prompt only if: browser supports it, hasn't denied it before,
  // and the user hasn't answered our custom prompt yet.
  const shouldPrompt = computed(
    () => isSupported && permission.value === 'default' && !hasAnswered.value,
  )

  async function enable() {
    if (!isSupported) return
    localStorage.setItem(STORAGE_KEY, 'asked')
    hasAnswered.value = true
    const result = await Notification.requestPermission()
    permission.value = result
  }

  function dismiss() {
    localStorage.setItem(STORAGE_KEY, 'dismissed')
    hasAnswered.value = true
  }

  return { isSupported, permission, shouldPrompt, enable, dismiss }
}
