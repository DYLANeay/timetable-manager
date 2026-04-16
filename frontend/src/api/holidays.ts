import { api } from './client'
import { invalidateApiCaches } from '@/utils/cache'

export interface PublicHoliday {
  id: number
  date: string
  name: string
}

interface ApiCollection<T> {
  data: T[]
}

export function fetchHolidays(year: number): Promise<ApiCollection<PublicHoliday>> {
  return api<ApiCollection<PublicHoliday>>(`/public-holidays?year=${year}`)
}

export async function createHoliday(data: { date: string; name: string }): Promise<{ data: PublicHoliday }> {
  const result = await api<{ data: PublicHoliday }>('/public-holidays', {
    method: 'POST',
    body: JSON.stringify(data),
  })
  // Holidays affect shift templates, so invalidate all
  await invalidateApiCaches()
  return result
}

export async function deleteHoliday(id: number): Promise<void> {
  await api<void>(`/public-holidays/${id}`, { method: 'DELETE' })
  // Holidays affect shift templates, so invalidate all
  await invalidateApiCaches()
}
