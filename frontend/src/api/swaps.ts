import { api } from './client'
import { invalidateSwapsCache, invalidateShiftsCache } from '@/utils/cache'

export interface SwapRequestData {
  id: number
  type: 'swap' | 'giveaway'
  status: string
  note: string | null
  requester: { id: number; name: string }
  target: { id: number; name: string } | null
  requester_shift: {
    id: number
    date: string
    shift_template: { shift_type: string; start_time: string; end_time: string }
  }
  target_shift: {
    id: number
    date: string
    shift_template: { shift_type: string; start_time: string; end_time: string }
  } | null
  manager: { id: number; name: string } | null
  peer_responded_at: string | null
  manager_decided_at: string | null
  created_at: string
}

interface ApiCollection<T> {
  data: T[]
}

export function fetchSwapRequests(): Promise<ApiCollection<SwapRequestData>> {
  return api<ApiCollection<SwapRequestData>>('/swap-requests')
}

export async function createSwapRequest(data: {
  requester_shift_id: number
  target_shift_id: number
  note?: string
}): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>('/swap-requests', {
    method: 'POST',
    body: JSON.stringify(data),
  })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}

export async function createGiveaway(data: {
  requester_shift_id: number
  note?: string
}): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>('/swap-requests', {
    method: 'POST',
    body: JSON.stringify({ ...data, type: 'giveaway' }),
  })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}

export async function claimGiveaway(id: number): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>(`/swap-requests/${id}/claim`, { method: 'PUT' })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}

export async function respondToSwapRequest(
  id: number,
  accept: boolean,
): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>(`/swap-requests/${id}/respond`, {
    method: 'PUT',
    body: JSON.stringify({ accept }),
  })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}

export async function decideSwapRequest(
  id: number,
  approve: boolean,
): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>(`/swap-requests/${id}/decide`, {
    method: 'PUT',
    body: JSON.stringify({ approve }),
  })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}

export async function cancelSwapRequest(id: number): Promise<{ data: SwapRequestData }> {
  const result = await api<{ data: SwapRequestData }>(`/swap-requests/${id}/cancel`, {
    method: 'PUT',
  })
  await Promise.all([invalidateSwapsCache(), invalidateShiftsCache()])
  return result
}
