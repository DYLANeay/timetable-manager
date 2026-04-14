import type { User } from '@/types'
import { api } from './client'
import { invalidateApiCaches } from '@/utils/cache'

interface ApiCollection<T> {
  data: T[]
}

export function fetchEmployees(): Promise<ApiCollection<User>> {
  return api<ApiCollection<User>>('/employees')
}

export async function createEmployee(data: {
  name: string
  email: string
  role: string
}): Promise<{ data: User }> {
  const result = await api<{ data: User }>('/employees', {
    method: 'POST',
    body: JSON.stringify(data),
  })
  await invalidateApiCaches()
  return result
}

export async function updateEmployee(
  id: number,
  data: Partial<{ name: string; email: string; role: string; is_active: boolean }>,
): Promise<{ data: User }> {
  const result = await api<{ data: User }>(`/employees/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
  await invalidateApiCaches()
  return result
}

export async function deleteEmployee(id: number): Promise<void> {
  await api<void>(`/employees/${id}`, { method: 'DELETE' })
  await invalidateApiCaches()
}
