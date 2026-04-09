<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useShiftStore } from '@/stores/shifts'
import WeekPicker from '@/components/shifts/WeekPicker.vue'
import WeeklyTimetable from '@/components/shifts/WeeklyTimetable.vue'
import MonthlyTimetable from '@/components/shifts/MonthlyTimetable.vue'
import AssignShiftDialog from '@/components/shifts/AssignShiftDialog.vue'
import { ref } from 'vue'
import type { Shift, ShiftTemplate } from '@/types'
import type { ViewMode } from '@/stores/shifts'

const auth = useAuthStore()
const shiftStore = useShiftStore()

const dialogOpen = ref(false)
const selectedDate = ref('')
const selectedTemplate = ref<ShiftTemplate | null>(null)
const selectedShift = ref<Shift | undefined>(undefined)

onMounted(async () => {
  await shiftStore.loadTemplates()
  await shiftStore.load()
})

watch([() => shiftStore.currentWeek, () => shiftStore.currentMonth, () => shiftStore.viewMode], () => {
  shiftStore.load()
})

function handleCellClick(date: string, template: ShiftTemplate) {
  selectedDate.value = date
  selectedTemplate.value = template
  selectedShift.value = shiftStore.getShiftsForDateAndTemplate(date, template.id)[0]
  dialogOpen.value = true
}

function handleSaved() {
  shiftStore.load()
}

function handleToday() {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  if (shiftStore.viewMode === 'month') {
    shiftStore.currentMonth = `${now.getFullYear()}-${pad(now.getMonth() + 1)}`
  } else {
    const day = now.getDay()
    const diff = now.getDate() - day + (day === 0 ? -6 : 1)
    const monday = new Date(now.getFullYear(), now.getMonth(), diff)
    shiftStore.currentWeek = `${monday.getFullYear()}-${pad(monday.getMonth() + 1)}-${pad(monday.getDate())}`
  }
}

function handlePrevious() {
  if (shiftStore.viewMode === 'month') shiftStore.previousMonth()
  else shiftStore.previousWeek()
}

function handleNext() {
  if (shiftStore.viewMode === 'month') shiftStore.nextMonth()
  else shiftStore.nextWeek()
}

function handleSetView(mode: ViewMode) {
  shiftStore.setViewMode(mode)
}

function printSchedule() {
  globalThis.window.print()
}
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Top bar -->
    <header class="flex shrink-0 items-center gap-2 border-b px-4 py-3 md:px-6">
      <WeekPicker
        :current-week="shiftStore.currentWeek"
        :current-month="shiftStore.currentMonth"
        :view-mode="shiftStore.viewMode"
        @previous="handlePrevious"
        @next="handleNext"
        @today="handleToday"
        @set-view="handleSetView"
      />
      <button
        class="hidden items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent print:hidden md:flex"
        @click="printSchedule"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        {{ $t('schedule.print') }}
      </button>
    </header>

    <!-- Timetable -->
    <div class="flex-1 overflow-auto">
      <div v-if="shiftStore.loading" class="flex h-full items-center justify-center">
        <div class="flex flex-col items-center gap-2">
          <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          <span class="text-sm text-muted-foreground">{{ $t('schedule.loadingSchedule') }}</span>
        </div>
      </div>

      <template v-else>
        <WeeklyTimetable
          v-if="shiftStore.viewMode === 'week'"
          :week-days="shiftStore.weekDays"
          :templates="shiftStore.templates"
          :shifts="shiftStore.shifts"
          :holidays="shiftStore.holidays"
          :leave-requests="shiftStore.leaveRequests"
          :is-manager="auth.isManager"
          @cell-click="handleCellClick"
        />
        <MonthlyTimetable
          v-else
          :month-days="shiftStore.monthDays"
          :current-month="shiftStore.currentMonth"
          :templates="shiftStore.templates"
          :shifts="shiftStore.shifts"
          :holidays="shiftStore.holidays"
          :leave-requests="shiftStore.leaveRequests"
          :is-manager="auth.isManager"
          @cell-click="handleCellClick"
        />
      </template>
    </div>
  </div>

  <AssignShiftDialog
    v-if="dialogOpen && selectedTemplate"
    :open="dialogOpen"
    :date="selectedDate"
    :template="selectedTemplate"
    :existing-shift="selectedShift"
    @update:open="dialogOpen = $event"
    @saved="handleSaved"
  />
</template>
