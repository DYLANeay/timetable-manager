import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { Shift, ShiftTemplate } from '@/types'
import * as shiftsApi from '@/api/shifts'

export const useShiftStore = defineStore('shifts', () => {
  const shifts = ref<Shift[]>([])
  const templates = ref<ShiftTemplate[]>([])
  const currentWeek = ref(getMonday(new Date()))
  const loading = ref(false)

  function getMonday(date: Date): string {
    const d = new Date(date)
    const day = d.getDay()
    const diff = d.getDate() - day + (day === 0 ? -6 : 1)
    d.setDate(diff)
    return d.toISOString().split('T')[0]!
  }

  const weekDays = computed(() => {
    const days: string[] = []
    const start = new Date(currentWeek.value)
    for (let i = 0; i < 7; i++) {
      const d = new Date(start)
      d.setDate(start.getDate() + i)
      days.push(d.toISOString().split('T')[0]!)
    }
    return days
  })

  function getShiftsForDateAndTemplate(date: string, templateId: number): Shift[] {
    return shifts.value.filter(
      (s) => s.date === date && s.shift_template.id === templateId,
    )
  }

  async function loadTemplates() {
    if (templates.value.length > 0) return
    const response = await shiftsApi.fetchShiftTemplates()
    templates.value = response.data
  }

  async function loadShifts() {
    loading.value = true
    try {
      const response = await shiftsApi.fetchShifts(currentWeek.value)
      shifts.value = response.data
    } finally {
      loading.value = false
    }
  }

  function previousWeek() {
    const d = new Date(currentWeek.value)
    d.setDate(d.getDate() - 7)
    currentWeek.value = d.toISOString().split('T')[0]!
  }

  function nextWeek() {
    const d = new Date(currentWeek.value)
    d.setDate(d.getDate() + 7)
    currentWeek.value = d.toISOString().split('T')[0]!
  }

  return {
    shifts,
    templates,
    currentWeek,
    loading,
    weekDays,
    getShiftsForDateAndTemplate,
    loadTemplates,
    loadShifts,
    previousWeek,
    nextWeek,
  }
})
