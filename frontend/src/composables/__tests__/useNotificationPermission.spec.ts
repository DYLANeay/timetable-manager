import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useNotificationPermission } from '@/composables/useNotificationPermission'

const STORAGE_KEY = 'notifications_prompt_answered'

/**
 * jsdom provides a basic Notification stub but doesn't implement
 * requestPermission. We create a configurable mock before each test.
 */

beforeEach(() => {
  localStorage.clear()
  // Reset Notification mock to "default" (not yet answered) permission
  Object.defineProperty(globalThis, 'Notification', {
    value: {
      permission: 'default' as NotificationPermission,
      requestPermission: vi.fn().mockResolvedValue('granted'),
    },
    writable: true,
    configurable: true,
  })
})

afterEach(() => {
  vi.restoreAllMocks()
  localStorage.clear()
})

describe('shouldPrompt', () => {
  it('is true when permission is default and user has not answered', () => {
    const { shouldPrompt } = useNotificationPermission()
    expect(shouldPrompt.value).toBe(true)
  })

  it('is false when localStorage already has an answer', () => {
    localStorage.setItem(STORAGE_KEY, 'asked')
    const { shouldPrompt } = useNotificationPermission()
    expect(shouldPrompt.value).toBe(false)
  })

  it('is false when Notification.permission is "granted"', () => {
    Object.defineProperty(globalThis, 'Notification', {
      value: { permission: 'granted', requestPermission: vi.fn() },
      writable: true,
      configurable: true,
    })
    const { shouldPrompt } = useNotificationPermission()
    expect(shouldPrompt.value).toBe(false)
  })

  it('is false when Notification.permission is "denied"', () => {
    Object.defineProperty(globalThis, 'Notification', {
      value: { permission: 'denied', requestPermission: vi.fn() },
      writable: true,
      configurable: true,
    })
    const { shouldPrompt } = useNotificationPermission()
    expect(shouldPrompt.value).toBe(false)
  })
})

describe('enable', () => {
  it('calls requestPermission', async () => {
    const { enable } = useNotificationPermission()
    await enable()
    expect(globalThis.Notification.requestPermission).toHaveBeenCalledOnce()
  })

  it('marks the user as having answered (sets localStorage)', async () => {
    const { enable } = useNotificationPermission()
    await enable()
    expect(localStorage.getItem(STORAGE_KEY)).toBe('asked')
  })

  it('updates the permission ref to the result of requestPermission', async () => {
    const { enable, permission } = useNotificationPermission()
    await enable()
    expect(permission.value).toBe('granted')
  })
})

describe('dismiss', () => {
  it('stores "dismissed" in localStorage', () => {
    const { dismiss } = useNotificationPermission()
    dismiss()
    expect(localStorage.getItem(STORAGE_KEY)).toBe('dismissed')
  })

  it('sets hasAnswered to true so shouldPrompt becomes false', () => {
    const { dismiss, shouldPrompt } = useNotificationPermission()
    dismiss()
    expect(shouldPrompt.value).toBe(false)
  })
})
