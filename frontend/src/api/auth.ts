import type { LoginResponse, User } from '@/types'
import { api } from './client'

export function login(email: string, password: string): Promise<LoginResponse> {
  return api<LoginResponse>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function logout(): Promise<void> {
  return api('/auth/logout', { method: 'POST' })
}

export function fetchMe(): Promise<User> {
  return api<User>('/auth/me')
}

export function changePassword(data: {
  current_password: string
  password: string
  password_confirmation: string
}): Promise<void> {
  return api('/auth/password', {
    method: 'PUT',
    body: JSON.stringify(data),
  })
}
