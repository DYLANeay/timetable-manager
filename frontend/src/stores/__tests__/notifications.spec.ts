import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useNotificationsStore } from '@/stores/notifications'
import type { AppNotification } from '@/types'

// ── Module mocks ──────────────────────────────────────────────────────────────

vi.mock('@/api/notifications', () => ({
  fetchNotifications: vi.fn(),
  markRead: vi.fn(),
  markAllRead: vi.fn(),
}))

vi.mock('@/composables/useToast', () => ({
  addToast: vi.fn(),
}))

// Minimal i18n stub so the store doesn't crash when calling t()
vi.mock('@/i18n', () => ({
  default: {
    global: {
      t: (key: string) => key,
      locale: { value: 'en' },
    },
  },
}))

import * as notifApi from '@/api/notifications'
import { addToast } from '@/composables/useToast'

// ── Fixtures ──────────────────────────────────────────────────────────────────

function makeNotif(id: number, readAt: string | null = null): AppNotification {
  return {
    id,
    type: 'planning_updated',
    data: { action: 'created', date: '2026-06-01', shift_type: 'morning', shift_id: '1' },
    read_at: readAt,
    created_at: '2026-06-01T10:00:00Z',
  }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

afterEach(() => {
  vi.restoreAllMocks()
})

// ─── Initial state ────────────────────────────────────────────────────────────

describe('initial state', () => {
  it('starts with empty notifications', () => {
    const store = useNotificationsStore()
    expect(store.notifications).toHaveLength(0)
  })

  it('starts with unreadCount of 0', () => {
    const store = useNotificationsStore()
    expect(store.unreadCount).toBe(0)
  })

  it('starts with loading false', () => {
    const store = useNotificationsStore()
    expect(store.loading).toBe(false)
  })
})

// ─── fetch ────────────────────────────────────────────────────────────────────

describe('fetch', () => {
  it('populates notifications from the API', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1), makeNotif(2)],
      unread_count: 2,
    })

    const store = useNotificationsStore()
    await store.fetch()

    expect(store.notifications).toHaveLength(2)
  })

  it('does not call addToast on the initial fetch', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1)],
      unread_count: 1,
    })

    const store = useNotificationsStore()
    await store.fetch() // first fetch = initial

    expect(addToast).not.toHaveBeenCalled()
  })

  it('calls addToast for each new unread notification after the initial fetch', async () => {
    // Initial fetch establishes baseline
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [makeNotif(1)],
      unread_count: 0,
    })
    const store = useNotificationsStore()
    await store.fetch()

    // Second fetch brings two new unread items
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [makeNotif(1), makeNotif(2), makeNotif(3)],
      unread_count: 2,
    })
    await store.fetch()

    expect(addToast).toHaveBeenCalledTimes(2)
  })

  it('does not toast for notifications already seen in a previous fetch', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [makeNotif(1)],
      unread_count: 1,
    })
    const store = useNotificationsStore()
    await store.fetch()

    // Second fetch: same notification appears again (already seen)
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [makeNotif(1)],
      unread_count: 1,
    })
    await store.fetch()

    expect(addToast).not.toHaveBeenCalled()
  })

  it('does not call addToast for already-read notifications in subsequent fetches', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [],
      unread_count: 0,
    })
    const store = useNotificationsStore()
    await store.fetch()

    // Second fetch: notification is already marked read
    vi.mocked(notifApi.fetchNotifications).mockResolvedValueOnce({
      notifications: [makeNotif(1, '2026-06-01T10:05:00Z')],
      unread_count: 0,
    })
    await store.fetch()

    expect(addToast).not.toHaveBeenCalled()
  })

  it('silently ignores API errors', async () => {
    vi.mocked(notifApi.fetchNotifications).mockRejectedValue(new Error('Network error'))
    const store = useNotificationsStore()
    await expect(store.fetch()).resolves.toBeUndefined()
  })

  it('does not start a second fetch while one is in progress', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [],
      unread_count: 0,
    })
    const store = useNotificationsStore()
    // Fire two concurrent fetches
    const p1 = store.fetch()
    const p2 = store.fetch()
    await Promise.all([p1, p2])

    expect(notifApi.fetchNotifications).toHaveBeenCalledTimes(1)
  })
})

// ─── unreadCount ──────────────────────────────────────────────────────────────

describe('unreadCount', () => {
  it('reflects the number of unread notifications', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1), makeNotif(2), makeNotif(3, '2026-06-01T10:00:00Z')],
      unread_count: 2,
    })

    const store = useNotificationsStore()
    await store.fetch()

    expect(store.unreadCount).toBe(2)
  })
})

// ─── markRead ────────────────────────────────────────────────────────────────

describe('markRead', () => {
  it('sets read_at on the matching notification', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1)],
      unread_count: 1,
    })
    vi.mocked(notifApi.markRead).mockResolvedValue({
      ...makeNotif(1),
      read_at: '2026-06-01T11:00:00Z',
    })

    const store = useNotificationsStore()
    await store.fetch()
    await store.markRead(1)

    expect(store.notifications[0].read_at).not.toBeNull()
  })

  it('decrements unreadCount after marking read', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1), makeNotif(2)],
      unread_count: 2,
    })
    vi.mocked(notifApi.markRead).mockResolvedValue({
      ...makeNotif(1),
      read_at: '2026-06-01T11:00:00Z',
    })

    const store = useNotificationsStore()
    await store.fetch()
    await store.markRead(1)

    expect(store.unreadCount).toBe(1)
  })
})

// ─── markAllRead ─────────────────────────────────────────────────────────────

describe('markAllRead', () => {
  it('sets read_at on all unread notifications', async () => {
    vi.mocked(notifApi.fetchNotifications).mockResolvedValue({
      notifications: [makeNotif(1), makeNotif(2)],
      unread_count: 2,
    })
    vi.mocked(notifApi.markAllRead).mockResolvedValue({ success: true })

    const store = useNotificationsStore()
    await store.fetch()
    await store.markAllRead()

    expect(store.notifications.every((n) => n.read_at !== null)).toBe(true)
    expect(store.unreadCount).toBe(0)
  })
})
