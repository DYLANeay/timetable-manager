import { api } from './client'
import { invalidateLeavesCache } from '@/utils/cache'

export interface LeaveRequest {
  id: number
  user: { id: number; name: string }
  start_date: string
  end_date: string
  business_days: number
  status: 'pending' | 'approved' | 'denied'
  note: string | null
  manager?: { id: number; name: string } | null
  decided_at: string | null
  created_at: string
}

export interface LeaveBalance {
  total_days: number
  used_days: number
  remaining_days: number
  year: number
}

export function fetchLeaveRequests(year?: number, userId?: number) {
  const params = new URLSearchParams()
  if (year) params.set('year', String(year))
  if (userId) params.set('user_id', String(userId))
  const qs = params.toString()
  return api<{ data: LeaveRequest[] }>(`/leave-requests${qs ? `?${qs}` : ''}`)
}

export async function createLeaveRequest(data: { start_date: string; end_date: string; note?: string }) {
  const result = await api<{ data: LeaveRequest }>('/leave-requests', {
    method: 'POST',
    body: JSON.stringify(data),
  })
  await invalidateLeavesCache()
  return result
}

export async function decideLeaveRequest(id: number, status: 'approved' | 'denied') {
  const result = await api<{ data: LeaveRequest }>(`/leave-requests/${id}/decide`, {
    method: 'PUT',
    body: JSON.stringify({ status }),
  })
  await invalidateLeavesCache()
  return result
}

export async function cancelLeaveRequest(id: number) {
  await api<void>(`/leave-requests/${id}`, { method: 'DELETE' })
  await invalidateLeavesCache()
}

export function fetchLeaveBalance(year?: number, userId?: number) {
  const params = new URLSearchParams()
  if (year) params.set('year', String(year))
  if (userId) params.set('user_id', String(userId))
  const qs = params.toString()
  return api<LeaveBalance>(`/leave-requests/balance${qs ? `?${qs}` : ''}`)
}