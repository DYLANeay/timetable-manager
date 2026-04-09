import { api } from './client'

export interface SwapRequestData {
  id: number
  status: string
  note: string | null
  requester: { id: number; name: string }
  target: { id: number; name: string }
  requester_shift: {
    id: number
    date: string
    shift_template: { shift_type: string; start_time: string; end_time: string }
  }
  target_shift: {
    id: number
    date: string
    shift_template: { shift_type: string; start_time: string; end_time: string }
  }
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

export function createSwapRequest(data: {
  requester_shift_id: number
  target_shift_id: number
  note?: string
}): Promise<{ data: SwapRequestData }> {
  return api('/swap-requests', {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export function respondToSwapRequest(
  id: number,
  accept: boolean,
): Promise<{ data: SwapRequestData }> {
  return api(`/swap-requests/${id}/respond`, {
    method: 'PUT',
    body: JSON.stringify({ accept }),
  })
}

export function decideSwapRequest(
  id: number,
  approve: boolean,
): Promise<{ data: SwapRequestData }> {
  return api(`/swap-requests/${id}/decide`, {
    method: 'PUT',
    body: JSON.stringify({ approve }),
  })
}

export function cancelSwapRequest(id: number): Promise<{ data: SwapRequestData }> {
  return api(`/swap-requests/${id}/cancel`, {
    method: 'PUT',
  })
}
