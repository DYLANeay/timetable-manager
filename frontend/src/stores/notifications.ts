import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { fetchNotifications, markRead as apiMarkRead, markAllRead as apiMarkAllRead } from '@/api/notifications'
import type { AppNotification } from '@/types'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<AppNotification[]>([])
  const loading = ref(false)

  const unreadCount = computed(() => notifications.value.filter((n) => !n.read_at).length)

  async function fetch() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await fetchNotifications()
      notifications.value = res.notifications
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
