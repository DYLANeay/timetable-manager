import type { Shift, ShiftTemplate } from '@/types'
import { api } from './client'

interface ApiCollection<T> {
  data: T[]
}

export function fetchShifts(week: string): Promise<ApiCollection<Shift>> {
  return api<ApiCollection<Shift>>(`/shifts?week=${week}`)
}

export function fetchMyShifts(week: string): Promise<ApiCollection<Shift>> {
  return api<ApiCollection<Shift>>(`/shifts/my?week=${week}`)
}

export function fetchShiftTemplates(): Promise<ApiCollection<ShiftTemplate>> {
  return api<ApiCollection<ShiftTemplate>>('/shift-templates')
}

export function createShift(data: {
  user_id: number | null
  shift_template_id: number
  date: string
  notes?: string
}): Promise<{ data: Shift }> {
  return api('/shifts', {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export function updateShift(
  id: number,
  data: { user_id?: number | null; notes?: string | null },
): Promise<{ data: Shift }> {
  return api(`/shifts/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
}

export function deleteShift(id: number): Promise<void> {
  return api(`/shifts/${id}`, { method: 'DELETE' })
}

export function bulkCreateShifts(
  shifts: Array<{
    user_id: number | null
    shift_template_id: number
    date: string
    notes?: string
  }>,
): Promise<ApiCollection<Shift>> {
  return api('/shifts/bulk', {
    method: 'POST',
    body: JSON.stringify({ shifts }),
  })
}
