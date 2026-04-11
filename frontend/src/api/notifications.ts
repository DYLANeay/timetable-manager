import { api } from './client'
import type { AppNotification } from '@/types'

export interface NotificationsResponse {
  notifications: AppNotification[]
  unread_count: number
}

export function fetchNotifications(): Promise<NotificationsResponse> {
  return api<NotificationsResponse>('/notifications')
}

export function markRead(id: number): Promise<AppNotification> {
  return api<AppNotification>(`/notifications/${id}/read`, { method: 'PUT' })
}

export function markAllRead(): Promise<{ success: boolean }> {
  return api<{ success: boolean }>('/notifications/read-all', { method: 'PUT' })
}
