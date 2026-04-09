export type Role = 'employee' | 'manager'

export interface User {
  id: number
  name: string
  email: string
  role: Role
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface LoginResponse {
  user: User
  token: string
}
