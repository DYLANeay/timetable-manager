import type { Shift, ShiftTemplate } from '@/types'
import { api } from './client'
import { invalidateShiftsCache } from '@/utils/cache'

interface ApiCollection<T> {
  data: T[]
}

export function fetchShifts(week: string): Promise<ApiCollection<Shift>> {
  return api<ApiCollection<Shift>>(`/shifts?week=${week}`)
}

export function fetchShiftsByMonth(month: string): Promise<ApiCollection<Shift>> {
  return api<ApiCollection<Shift>>(`/shifts?month=${month}`)
}

export function fetchMyShifts(week: string): Promise<ApiCollection<Shift>> {
  return api<ApiCollection<Shift>>(`/shifts/my?week=${week}`)
}

export function fetchShiftTemplates(): Promise<ApiCollection<ShiftTemplate>> {
  return api<ApiCollection<ShiftTemplate>>('/shift-templates')
}

export async function createShift(data: {
  user_id: number | null
  shift_template_id: number
  date: string
  notes?: string
}): Promise<{ data: Shift }> {
  const result = await api<{ data: Shift }>('/shifts', {
    method: 'POST',
    body: JSON.stringify(data),
  })
  await invalidateShiftsCache()
  return result
}

export async function updateShift(
  id: number,
  data: { user_id?: number | null; notes?: string | null },
): Promise<{ data: Shift }> {
  const result = await api<{ data: Shift }>(`/shifts/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
  await invalidateShiftsCache()
  return result
}

export async function deleteShift(id: number): Promise<void> {
  await api<void>(`/shifts/${id}`, { method: 'DELETE' })
  await invalidateShiftsCache()
}

export async function bulkCreateShifts(
  shifts: Array<{
    user_id: number | null
    shift_template_id: number
    date: string
    notes?: string
  }>,
): Promise<ApiCollection<Shift>> {
  const result = await api<ApiCollection<Shift>>('/shifts/bulk', {
    method: 'POST',
    body: JSON.stringify({ shifts }),
  })
  await invalidateShiftsCache()
  return result
}
