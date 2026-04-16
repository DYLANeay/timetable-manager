import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { Shift, ShiftTemplate } from '@/types'
import * as shiftsApi from '@/api/shifts'
import { fetchHolidays, type PublicHoliday } from '@/api/holidays'
import { fetchLeaveRequests, type LeaveRequest } from '@/api/leaves'
import { addToast } from '@/composables/useToast'

export type ViewMode = 'week' | 'month'

/** Format a Date as YYYY-MM-DD in local timezone (avoids UTC shift bugs) */
function toLocalDate(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function toLocalMonth(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `${y}-${m}`
}

function getMonday(date: Date): string {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  d.setDate(diff)
  return toLocalDate(d)
}

function parseLocalDate(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y!, m! - 1, d!)
}

export const useShiftStore = defineStore('shifts', () => {
  const shifts = ref<Shift[]>([])
  const templates = ref<ShiftTemplate[]>([])
  const holidays = ref<PublicHoliday[]>([])
  const leaveRequests = ref<LeaveRequest[]>([])
  const currentWeek = ref(getMonday(new Date()))
  const currentMonth = ref(toLocalMonth(new Date()))
  const viewMode = ref<ViewMode>('week')
  const loading = ref(false)
  const error = ref<string | null>(null)

  const weekDays = computed(() => {
    const start = parseLocalDate(currentWeek.value)
    const days: string[] = []
    for (let i = 0; i < 7; i++) {
      const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i)
      days.push(toLocalDate(d))
    }
    return days
  })

  const monthDays = computed(() => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    const firstDay = new Date(year!, month! - 1, 1)
    const lastDay = new Date(year!, month!, 0)

    const startMonday = new Date(firstDay)
    const dow = firstDay.getDay()
    startMonday.setDate(firstDay.getDate() + (dow === 0 ? -6 : 1 - dow))

    const endSunday = new Date(lastDay)
    const endDow = lastDay.getDay()
    endSunday.setDate(lastDay.getDate() + (endDow === 0 ? 0 : 7 - endDow))

    const days: string[] = []
    const cur = new Date(startMonday)
    while (cur <= endSunday) {
      days.push(toLocalDate(cur))
      cur.setDate(cur.getDate() + 1)
    }
    return days
  })

  const holidayDates = computed(() => new Set(holidays.value.map((h) => h.date)))

  function isHoliday(date: string): boolean {
    return holidayDates.value.has(date)
  }

  function getHoliday(date: string): PublicHoliday | undefined {
    return holidays.value.find((h) => h.date === date)
  }

  function getShiftsForDateAndTemplate(date: string, templateId: number): Shift[] {
    return shifts.value.filter(
      (s) => s.date === date && s.shift_template.id === templateId,
    )
  }

  async function loadTemplates(): Promise<boolean> {
    if (templates.value.length > 0) return true
    try {
      const response = await shiftsApi.fetchShiftTemplates()
      templates.value = response.data
      return true
    } catch (err) {
      console.error('Failed to load templates:', err)
      addToast('Impossible de charger les modèles de shift', 'error')
      return false
    }
  }

  async function loadLeaves(): Promise<boolean> {
    try {
      const year = viewMode.value === 'month'
        ? Number(currentMonth.value.slice(0, 4))
        : parseLocalDate(currentWeek.value).getFullYear()
      const res = await fetchLeaveRequests(year)
      leaveRequests.value = res.data
      return true
    } catch (err) {
      console.error('Failed to load leaves:', err)
      return false
    }
  }

  function getLeavesForDate(date: string): LeaveRequest[] {
    return leaveRequests.value.filter(
      (l) => l.status === 'approved' && l.start_date <= date && l.end_date >= date,
    )
  }

  async function loadHolidays(): Promise<boolean> {
    try {
      const year = viewMode.value === 'month'
        ? Number(currentMonth.value.slice(0, 4))
        : parseLocalDate(currentWeek.value).getFullYear()
      const res = await fetchHolidays(year)
      holidays.value = res.data
      return true
    } catch (err) {
      console.error('Failed to load holidays:', err)
      return false
    }
  }

  async function loadShifts(): Promise<boolean> {
    loading.value = true
    error.value = null
    try {
      if (viewMode.value === 'month') {
        const response = await shiftsApi.fetchShiftsByMonth(currentMonth.value)
        shifts.value = response.data
      } else {
        const response = await shiftsApi.fetchShifts(currentWeek.value)
        shifts.value = response.data
      }
      return true
    } catch (err) {
      error.value = 'Impossible de charger les shifts'
      addToast(error.value, 'error')
      return false
    } finally {
      loading.value = false
    }
  }

  async function load(): Promise<boolean> {
    const results = await Promise.all([loadShifts(), loadHolidays(), loadLeaves()])
    return results.every(r => r)
  }

  function previousWeek() {
    const d = parseLocalDate(currentWeek.value)
    d.setDate(d.getDate() - 7)
    currentWeek.value = toLocalDate(d)
  }

  function nextWeek() {
    const d = parseLocalDate(currentWeek.value)
    d.setDate(d.getDate() + 7)
    currentWeek.value = toLocalDate(d)
  }

  function previousMonth() {
    const [y, m] = currentMonth.value.split('-').map(Number)
    const d = new Date(y!, m! - 2, 1)
    currentMonth.value = toLocalMonth(d)
  }

  function nextMonth() {
    const [y, m] = currentMonth.value.split('-').map(Number)
    const d = new Date(y!, m!, 1)
    currentMonth.value = toLocalMonth(d)
  }

  function setViewMode(mode: ViewMode) {
    viewMode.value = mode
    if (mode === 'month') {
      currentMonth.value = currentWeek.value.slice(0, 7)
    } else {
      currentWeek.value = getMonday(parseLocalDate(currentMonth.value + '-01'))
    }
  }

  return {
    shifts, templates, holidays, leaveRequests, currentWeek, currentMonth, viewMode, loading, error,
    weekDays, monthDays, holidayDates, isHoliday, getHoliday, getLeavesForDate,
    getShiftsForDateAndTemplate,
    loadTemplates, loadHolidays, loadLeaves, loadShifts, load,
    previousWeek, nextWeek, previousMonth, nextMonth, setViewMode,
  }
})
