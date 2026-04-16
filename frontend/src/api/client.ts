import { getOfflineStatus } from '@/composables/useOffline'

const API_BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

const MAX_RETRIES = 3
const RETRY_DELAY = 1000
const REQUEST_TIMEOUT = 30000

export class ApiError extends Error {
  constructor(
    public status: number,
    public data: Record<string, unknown>,
  ) {
    super(`API error ${status}`)
  }
}

function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms))
}

async function fetchWithTimeout(
  url: string,
  options: RequestInit,
  timeoutMs: number
): Promise<Response> {
  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs)

  try {
    const response = await fetch(url, {
      ...options,
      signal: controller.signal,
    })
    return response
  } finally {
    clearTimeout(timeoutId)
  }
}

export async function api<T>(
  path: string,
  options: RequestInit = {},
  retryCount = 0
): Promise<T> {
  // Check if offline first
  const { isOffline } = getOfflineStatus()
  if (isOffline) {
    throw new ApiError(0, { message: 'Vous êtes hors ligne. Vérifiez votre connexion.' })
  }

  const token = localStorage.getItem('auth_token')

  try {
    const response = await fetchWithTimeout(
      `${API_BASE}${path}`,
      {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
          ...options.headers,
        },
      },
      REQUEST_TIMEOUT
    )

    // Handle 401 - Token expired or invalid
    if (response.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
      throw new ApiError(401, { message: 'Session expirée. Veuillez vous reconnecter.' })
    }

    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      throw new ApiError(response.status, data)
    }

    if (response.status === 204) {
      return undefined as T
    }

    return response.json()
  } catch (error) {
    // Retry on network errors (not HTTP errors)
    if (
      retryCount < MAX_RETRIES &&
      (error instanceof TypeError || error instanceof Error && error.name === 'AbortError')
    ) {
      await sleep(RETRY_DELAY * (retryCount + 1))
      return api(path, options, retryCount + 1)
    }

    // If all retries failed, throw a user-friendly error
    if (error instanceof ApiError) {
      throw error
    }

    if (error instanceof Error && error.name === 'AbortError') {
      throw new ApiError(408, { message: 'La requête a expiré. Le serveur met trop de temps à répondre.' })
    }

    throw new ApiError(0, { message: 'Impossible de se connecter au serveur. Vérifiez votre connexion.' })
  }
}

