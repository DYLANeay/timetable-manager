import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { addToast, removeToast, useToasts } from '@/composables/useToast'

/**
 * useToast.ts uses a module-level singleton ref.
 * We reset it before each test by removing all toasts manually
 * so tests remain independent despite module caching.
 */
beforeEach(() => {
  const { toasts } = useToasts()
  toasts.value.splice(0)
})

afterEach(() => {
  vi.restoreAllMocks()
})

describe('addToast', () => {
  it('adds a toast with the provided message', () => {
    addToast('Hello world')
    const { toasts } = useToasts()
    expect(toasts.value).toHaveLength(1)
    expect(toasts.value[0].message).toBe('Hello world')
  })

  it('assigns a unique incrementing id to each toast', () => {
    addToast('First')
    addToast('Second')
    const { toasts } = useToasts()
    expect(toasts.value[0].id).not.toBe(toasts.value[1].id)
  })

  it('stores the notificationType when provided', () => {
    addToast('Shift updated', 'planning_updated')
    const { toasts } = useToasts()
    expect(toasts.value[0].notificationType).toBe('planning_updated')
  })

  it('defaults notificationType to empty string when not provided', () => {
    addToast('Generic message')
    const { toasts } = useToasts()
    expect(toasts.value[0].notificationType).toBe('')
  })

  it('schedules auto-removal after 5 000 ms', () => {
    vi.useFakeTimers()
    addToast('Auto remove me')
    const { toasts } = useToasts()
    expect(toasts.value).toHaveLength(1)

    vi.advanceTimersByTime(5000)
    expect(toasts.value).toHaveLength(0)
    vi.useRealTimers()
  })

  it('multiple toasts are auto-removed independently', () => {
    vi.useFakeTimers()
    addToast('First')
    vi.advanceTimersByTime(2000)
    addToast('Second') // added 2 s after first

    vi.advanceTimersByTime(3000) // 5 s since first, 3 s since second
    const { toasts } = useToasts()
    expect(toasts.value).toHaveLength(1)
    expect(toasts.value[0].message).toBe('Second')

    vi.advanceTimersByTime(2000) // both expired
    expect(toasts.value).toHaveLength(0)
    vi.useRealTimers()
  })
})

describe('removeToast', () => {
  it('removes a toast by id', () => {
    addToast('Remove me')
    const { toasts } = useToasts()
    const id = toasts.value[0].id
    removeToast(id)
    expect(toasts.value).toHaveLength(0)
  })

  it('silently ignores removal of a non-existent id', () => {
    addToast('Stay')
    const { toasts } = useToasts()
    removeToast(99999)
    expect(toasts.value).toHaveLength(1)
  })

  it('only removes the targeted toast when multiple exist', () => {
    addToast('Keep A')
    addToast('Remove B')
    const { toasts } = useToasts()
    const idB = toasts.value[1].id
    removeToast(idB)
    expect(toasts.value).toHaveLength(1)
    expect(toasts.value[0].message).toBe('Keep A')
  })
})

describe('useToasts', () => {
  it('returns the same reactive toasts array (singleton)', () => {
    addToast('Singleton check')
    const { toasts: t1 } = useToasts()
    const { toasts: t2 } = useToasts()
    expect(t1).toBe(t2)
  })
})
