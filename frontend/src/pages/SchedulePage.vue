<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useShiftStore } from '@/stores/shifts'
import WeekPicker from '@/components/shifts/WeekPicker.vue'
import WeeklyTimetable from '@/components/shifts/WeeklyTimetable.vue'
import AssignShiftDialog from '@/components/shifts/AssignShiftDialog.vue'
import type { Shift, ShiftTemplate } from '@/types'

const auth = useAuthStore()
const shiftStore = useShiftStore()

const dialogOpen = ref(false)
const selectedDate = ref('')
const selectedTemplate = ref<ShiftTemplate | null>(null)
const selectedShift = ref<Shift | undefined>(undefined)

onMounted(async () => {
  await shiftStore.loadTemplates()
  await shiftStore.loadShifts()
})

watch(() => shiftStore.currentWeek, () => {
  shiftStore.loadShifts()
})

function handleCellClick(date: string, template: ShiftTemplate) {
  selectedDate.value = date
  selectedTemplate.value = template

  const existing = shiftStore.getShiftsForDateAndTemplate(date, template.id)
  selectedShift.value = existing[0]

  dialogOpen.value = true
}

function handleSaved() {
  shiftStore.loadShifts()
}

function handleToday() {
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(now)
  monday.setDate(diff)
  shiftStore.currentWeek = monday.toISOString().split('T')[0]!
}
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Top bar -->
    <header class="flex shrink-0 items-center border-b px-4 py-3 md:px-6">
      <WeekPicker
        :current-week="shiftStore.currentWeek"
        @previous="shiftStore.previousWeek()"
        @next="shiftStore.nextWeek()"
        @today="handleToday"
      />
    </header>

    <!-- Timetable -->
    <div class="flex-1 overflow-auto">
      <div v-if="shiftStore.loading" class="flex h-full items-center justify-center">
        <div class="flex flex-col items-center gap-2">
          <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          <span class="text-sm text-muted-foreground">Loading schedule...</span>
        </div>
      </div>
      <WeeklyTimetable
        v-else
        :week-days="shiftStore.weekDays"
        :templates="shiftStore.templates"
        :shifts="shiftStore.shifts"
        :is-manager="auth.isManager"
        @cell-click="handleCellClick"
      />
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
