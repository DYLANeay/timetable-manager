import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { fetchNotifications, markRead as apiMarkRead, markAllRead as apiMarkAllRead } from '@/api/notifications'
import { addToast } from '@/composables/useToast'
import { getNotificationMessage } from '@/utils/notificationMessage'
import i18n from '@/i18n'
import type { AppNotification } from '@/types'

async function showBrowserNotification(count: number) {
  if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return

  const locale = localStorage.getItem('locale') ?? 'fr'
  const appName = locale === 'fr' ? 'Horaires Station' : 'Timetable Manager'
  const body =
    locale === 'fr'
      ? `${count} nouvelle${count > 1 ? 's' : ''} notification${count > 1 ? 's' : ''}`
      : `${count} new notification${count > 1 ? 's' : ''}`

  try {
    const reg = await navigator.serviceWorker?.ready
    if (reg) {
      await reg.showNotification(appName, {
        body,
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: 'new-notifications',
      })
      return
    }
  } catch {
    // service worker not available
  }

  new Notification(appName, { body, icon: '/icons/icon-192.png' })
}

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<AppNotification[]>([])
  const loading = ref(false)

  const seenIds = new Set<number>()
  let isInitialFetch = true

  const unreadCount = computed(() => notifications.value.filter((n) => !n.read_at).length)

  async function fetch() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await fetchNotifications()

      const newUnread = res.notifications.filter((n) => !n.read_at && !seenIds.has(n.id))
      res.notifications.forEach((n) => seenIds.add(n.id))
      notifications.value = res.notifications

      if (!isInitialFetch && newUnread.length > 0) {
        const t = i18n.global.t.bind(i18n.global) as (key: string, params?: Record<string, string>) => string
        const locale = i18n.global.locale.value

        newUnread.forEach((n) => {
          addToast(getNotificationMessage(n, t, locale), n.type)
        })

        showBrowserNotification(newUnread.length)
      }

      isInitialFetch = false
    } catch {
      // silently ignore — user may not be authenticated yet
    } finally {
      loading.value = false
    }
  }

  async function markRead(id: number) {
    await apiMarkRead(id)
    const n = notifications.value.find((n) => n.id === id)
    if (n) n.read_at = new Date().toISOString()
  }

  async function markAllRead() {
    await apiMarkAllRead()
    notifications.value.forEach((n) => {
      if (!n.read_at) n.read_at = new Date().toISOString()
    })
  }

  return { notifications, unreadCount, loading, fetch, markRead, markAllRead }
})
