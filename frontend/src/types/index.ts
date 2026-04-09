export type Role = 'employee' | 'manager'
export type DayType = 'weekday' | 'sunday_holiday'
export type ShiftTypeName = 'morning' | 'afternoon'

export interface User {
  id: number
  name: string
  email: string
  role: Role
  is_active: boolean
  avatar: string | null
  created_at: string
  updated_at: string
}

export interface LoginResponse {
  user: User
  token: string
}

export interface ShiftTemplate {
  id: number
  day_type: DayType
  shift_type: ShiftTypeName
  start_time: string
  end_time: string
}

export interface ShiftUser {
  id: number
  name: string
}

export interface Shift {
  id: number
  date: string
  notes: string | null
  user: ShiftUser | null
  shift_template: ShiftTemplate
  created_at: string
  updated_at: string
}
