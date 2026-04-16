// Cache invalidation utilities for manager actions
// These are called after CRUD operations to ensure fresh data

const API_CACHE_NAMES = [
  'shifts-cache',
  'swaps-cache',
  'templates-cache',
  'leaves-cache',
]

async function deleteCache(name: string): Promise<void> {
  if (!('caches' in window)) return
  try {
    await caches.delete(name)
  } catch (err) {
    console.error(`[Cache] Failed to invalidate ${name}:`, err)
  }
}

/**
 * Invalidate all API caches - call after broad mutations (employees, holidays)
 */
export async function invalidateApiCaches(): Promise<void> {
  await Promise.all(API_CACHE_NAMES.map(deleteCache))
}

export async function invalidateShiftsCache(): Promise<void> {
  await deleteCache('shifts-cache')
}

export async function invalidateSwapsCache(): Promise<void> {
  await deleteCache('swaps-cache')
}

export async function invalidateLeavesCache(): Promise<void> {
  await deleteCache('leaves-cache')
}
