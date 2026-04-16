// Cache invalidation utilities for manager actions
// These are called after CRUD operations to ensure fresh data

const API_CACHE_NAMES = [
  'shifts-cache',
  'swaps-cache',
  'templates-cache',
  'leaves-cache',
]

/**
 * Invalidate all API caches - call after any manager mutation
 */
export async function invalidateApiCaches(): Promise<void> {
  if (!('caches' in window)) return

  try {
    const cacheNames = await caches.keys()
    const apiCaches = cacheNames.filter(name =>
      API_CACHE_NAMES.some(apiName => name.includes(apiName))
    )

    await Promise.all(
      apiCaches.map(cacheName => caches.delete(cacheName))
    )

    console.log('[Cache] Invalidated:', apiCaches)
  } catch (err) {
    console.error('[Cache] Failed to invalidate:', err)
  }
}

/**
 * Invalidate specific cache by pattern
 */
export async function invalidateCacheByPattern(urlPattern: RegExp): Promise<void> {
  if (!('caches' in window)) return

  try {
    const cacheNames = await caches.keys()

    for (const cacheName of cacheNames) {
      const cache = await caches.open(cacheName)
      const requests = await cache.keys()

      const matchingRequests = requests.filter(request =>
        urlPattern.test(request.url)
      )

      await Promise.all(
        matchingRequests.map(request => cache.delete(request))
      )
    }
  } catch (err) {
    console.error('[Cache] Failed to invalidate pattern:', err)
  }
}

/**
 * Invalidate shifts cache specifically
 */
export async function invalidateShiftsCache(): Promise<void> {
  await invalidateCacheByPattern(/\/api\/shifts/)
}

/**
 * Invalidate swap requests cache
 */
export async function invalidateSwapsCache(): Promise<void> {
  await invalidateCacheByPattern(/\/api\/swap-requests/)
}

/**
 * Invalidate leave requests cache
 */
export async function invalidateLeavesCache(): Promise<void> {
  await invalidateCacheByPattern(/\/api\/leave-requests/)
}
