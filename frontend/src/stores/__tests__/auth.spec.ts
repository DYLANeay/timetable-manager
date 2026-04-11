import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// Mock the auth API module
vi.mock('@/api/auth', () => ({
  login: vi.fn(),
  logout: vi.fn(),
  fetchMe: vi.fn(),
}))

import * as authApi from '@/api/auth'

// Mock vue-router to avoid needing a full router
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}))

const mockEmployee = {
  id: 1,
  name: 'Alice',
  email: 'alice@example.com',
  role: 'employee' as const,
  is_active: true,
  avatar: null,
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

const mockManager = { ...mockEmployee, id: 2, role: 'manager' as const }

beforeEach(() => {
  setActivePinia(createPinia())
  localStorage.clear()
  vi.clearAllMocks()
})

afterEach(() => {
  vi.restoreAllMocks()
})

describe('initial state', () => {
  it('starts with null user', () => {
    const store = useAuthStore()
    expect(store.user).toBeNull()
  })

  it('reads token from localStorage on init', () => {
    localStorage.setItem('auth_token', 'existing-token')
    const store = useAuthStore()
    expect(store.token).toBe('existing-token')
  })

  it('isAuthenticated is false when no token', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
  })
})

describe('login', () => {
  it('sets user and token on successful login', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc123' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')

    expect(store.user).toEqual(mockEmployee)
    expect(store.token).toBe('abc123')
  })

  it('persists token to localStorage', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'my-token' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')

    expect(localStorage.getItem('auth_token')).toBe('my-token')
  })

  it('isAuthenticated becomes true after successful login', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc123' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')

    expect(store.isAuthenticated).toBe(true)
  })

  it('propagates API errors to caller', async () => {
    vi.mocked(authApi.login).mockRejectedValue(new Error('Invalid credentials'))

    const store = useAuthStore()
    await expect(store.login('x', 'y')).rejects.toThrow('Invalid credentials')
  })
})

describe('logout', () => {
  it('clears user and token', async () => {
    vi.mocked(authApi.logout).mockResolvedValue(undefined)
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc123' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')
    await store.logout()

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
  })

  it('removes token from localStorage', async () => {
    vi.mocked(authApi.logout).mockResolvedValue(undefined)
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc123' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')
    await store.logout()

    expect(localStorage.getItem('auth_token')).toBeNull()
  })

  it('clears credentials even when API call fails', async () => {
    vi.mocked(authApi.logout).mockRejectedValue(new Error('Network error'))
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc123' })

    const store = useAuthStore()
    await store.login('alice@example.com', 'password')
    // The store uses try/finally so state is cleared even if API throws
    try { await store.logout() } catch { /* expected */ }

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
  })
})

describe('fetchUser', () => {
  it('populates user from API', async () => {
    vi.mocked(authApi.fetchMe).mockResolvedValue(mockEmployee)
    localStorage.setItem('auth_token', 'some-token')

    const store = useAuthStore()
    await store.fetchUser()

    expect(store.user).toEqual(mockEmployee)
  })

  it('does nothing when token is absent', async () => {
    const store = useAuthStore()
    await store.fetchUser()

    expect(authApi.fetchMe).not.toHaveBeenCalled()
  })

  it('clears token and user when API returns an error', async () => {
    vi.mocked(authApi.fetchMe).mockRejectedValue(new Error('Unauthorized'))
    localStorage.setItem('auth_token', 'bad-token')

    const store = useAuthStore()
    await store.fetchUser()

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
  })
})

describe('isManager', () => {
  it('returns false for employee role', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ user: mockEmployee, token: 'abc' })

    const store = useAuthStore()
    await store.login('x', 'y')

    expect(store.isManager).toBe(false)
  })

  it('returns true for manager role', async () => {
    vi.mocked(authApi.login).mockResolvedValue({ user: mockManager, token: 'abc' })

    const store = useAuthStore()
    await store.login('x', 'y')

    expect(store.isManager).toBe(true)
  })
})
