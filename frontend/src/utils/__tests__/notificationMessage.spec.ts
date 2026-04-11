import { describe, it, expect } from 'vitest'
import {
  getNotificationMessage,
  getNotificationRoute,
  formatNotifDate,
} from '@/utils/notificationMessage'
import type { AppNotification } from '@/types'

// Minimal stub t() that returns the key + all param values so tests can
// assert both the message type and the interpolated content.
const t = (key: string, params?: Record<string, string>): string => {
  if (!params || Object.keys(params).length === 0) return key
  return `${key} ${Object.values(params).join(' ')}`
}

function makeNotif(type: string, data: Record<string, string> = {}): AppNotification {
  return { id: 1, type, data, read_at: null, created_at: '2026-06-01T10:00:00Z' }
}

// ─── formatNotifDate ──────────────────────────────────────────────────────────

describe('formatNotifDate', () => {
  it('formats a date string in English locale', () => {
    const result = formatNotifDate('2026-06-01', 'en')
    expect(result).toContain('Jun')
    expect(result).toContain('1')
  })

  it('formats a date string in French locale', () => {
    const result = formatNotifDate('2026-06-01', 'fr')
    expect(result).toContain('juin')
  })

  it('returns empty string for empty input', () => {
    expect(formatNotifDate('', 'en')).toBe('')
  })

  it('includes weekday in the formatted output', () => {
    // 2026-06-01 is a Monday
    const result = formatNotifDate('2026-06-01', 'en')
    expect(result).toMatch(/Mon/i)
  })
})

// ─── getNotificationRoute ─────────────────────────────────────────────────────

describe('getNotificationRoute', () => {
  it('routes swap_request to /swap-requests', () => {
    expect(getNotificationRoute('swap_request')).toBe('/swap-requests')
  })

  it('routes swap_targeting_you to /swap-requests', () => {
    expect(getNotificationRoute('swap_targeting_you')).toBe('/swap-requests')
  })

  it('routes swap_decided to /swap-requests', () => {
    expect(getNotificationRoute('swap_decided')).toBe('/swap-requests')
  })

  it('routes leave_request to /leaves', () => {
    expect(getNotificationRoute('leave_request')).toBe('/leaves')
  })

  it('routes holiday_request to /leaves', () => {
    expect(getNotificationRoute('holiday_request')).toBe('/leaves')
  })

  it('routes leave_decided to /leaves', () => {
    expect(getNotificationRoute('leave_decided')).toBe('/leaves')
  })

  it('routes planning_updated to /schedule', () => {
    expect(getNotificationRoute('planning_updated')).toBe('/schedule')
  })

  it('falls back to /schedule for unknown types', () => {
    expect(getNotificationRoute('unknown_type')).toBe('/schedule')
  })
})

// ─── getNotificationMessage ───────────────────────────────────────────────────

describe('getNotificationMessage', () => {
  it('generates a swap_request message for swap type', () => {
    const notif = makeNotif('swap_request', { requester_name: 'Bob', swap_type: 'swap' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.swap_request')
    expect(msg).toContain('Bob')
  })

  it('generates a giveaway_request message when swap_type is giveaway', () => {
    const notif = makeNotif('swap_request', { requester_name: 'Alice', swap_type: 'giveaway' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.giveaway_request')
    expect(msg).toContain('Alice')
  })

  it('generates a leave_request message', () => {
    const notif = makeNotif('leave_request', { requester_name: 'Carol' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.leave_request')
    expect(msg).toContain('Carol')
  })

  it('generates a holiday_request message', () => {
    const notif = makeNotif('holiday_request', { requester_name: 'Dave' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.holiday_request')
    expect(msg).toContain('Dave')
  })

  it('generates a swap_targeting_you message', () => {
    const notif = makeNotif('swap_targeting_you', { requester_name: 'Eve' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.swap_targeting_you')
    expect(msg).toContain('Eve')
  })

  it('generates a planning_created message for planning_updated with action=created', () => {
    const notif = makeNotif('planning_updated', {
      action: 'created',
      shift_type: 'morning',
      date: '2026-06-01',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.planning_created')
  })

  it('generates a planning_deleted message for planning_updated with action=deleted', () => {
    const notif = makeNotif('planning_updated', {
      action: 'deleted',
      shift_type: 'afternoon',
      date: '2026-06-02',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.planning_deleted')
  })

  it('generates a swap_approved message for swap_decided with approved swap', () => {
    const notif = makeNotif('swap_decided', {
      decision: 'approved',
      swap_type: 'swap',
      shift_date: '2026-06-01',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.swap_approved')
  })

  it('generates a swap_denied message for swap_decided with denied swap', () => {
    const notif = makeNotif('swap_decided', {
      decision: 'denied',
      swap_type: 'swap',
      shift_date: '2026-06-01',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.swap_denied')
  })

  it('generates a giveaway_approved message for swap_decided with approved giveaway', () => {
    const notif = makeNotif('swap_decided', {
      decision: 'approved',
      swap_type: 'giveaway',
      shift_date: '2026-06-01',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.giveaway_approved')
  })

  it('generates a giveaway_denied message for swap_decided with denied giveaway', () => {
    const notif = makeNotif('swap_decided', {
      decision: 'denied',
      swap_type: 'giveaway',
      shift_date: '2026-06-01',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.giveaway_denied')
  })

  it('generates a leave_approved message for leave_decided with approved decision', () => {
    const notif = makeNotif('leave_decided', {
      decision: 'approved',
      start_date: '2026-07-01',
      end_date: '2026-07-05',
    })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.leave_approved')
  })

  it('generates a leave_denied message for leave_decided with denied decision', () => {
    const notif = makeNotif('leave_decided', { decision: 'denied' })
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toContain('notifications.types.leave_denied')
  })

  it('falls back to the raw type string for unknown notification types', () => {
    const notif = makeNotif('some_unknown_type')
    const msg = getNotificationMessage(notif, t, 'en')
    expect(msg).toBe('some_unknown_type')
  })
})
