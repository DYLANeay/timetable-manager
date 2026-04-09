import type { User } from '@/types'
import { api } from './client'

interface ApiCollection<T> {
  data: T[]
}

export function fetchEmployees(): Promise<ApiCollection<User>> {
  return api<ApiCollection<User>>('/employees')
}

export function createEmployee(data: {
  name: string
  email: string
  role: string
}): Promise<{ data: User }> {
  return api('/employees', {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export function updateEmployee(
  id: number,
  data: Partial<{ name: string; email: string; role: string; is_active: boolean }>,
): Promise<{ data: User }> {
  return api(`/employees/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
}

export function deleteEmployee(id: number): Promise<void> {
  return api(`/employees/${id}`, { method: 'DELETE' })
}
