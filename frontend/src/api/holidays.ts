import { api } from './client'

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

export function createHoliday(data: { date: string; name: string }): Promise<{ data: PublicHoliday }> {
  return api('/public-holidays', {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export function deleteHoliday(id: number): Promise<void> {
  return api(`/public-holidays/${id}`, { method: 'DELETE' })
}
