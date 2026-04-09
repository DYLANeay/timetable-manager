<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useShiftStore } from '@/stores/shifts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import WeekPicker from '@/components/shifts/WeekPicker.vue'
import WeeklyTimetable from '@/components/shifts/WeeklyTimetable.vue'
import type { ShiftTemplate } from '@/types'

const auth = useAuthStore()
const shiftStore = useShiftStore()

onMounted(async () => {
  await shiftStore.loadTemplates()
  await shiftStore.loadShifts()
})

watch(() => shiftStore.currentWeek, () => {
  shiftStore.loadShifts()
})

function handleCellClick(date: string, template: ShiftTemplate) {
  // TODO: Open assignment dialog for managers
  console.log('Cell clicked:', date, template)
}
</script>

<template>
  <div class="space-y-4 p-4">
    <Card>
      <CardHeader class="pb-3">
        <CardTitle class="text-lg">Weekly Schedule</CardTitle>
        <WeekPicker
          :current-week="shiftStore.currentWeek"
          @previous="shiftStore.previousWeek()"
          @next="shiftStore.nextWeek()"
        />
      </CardHeader>
      <CardContent>
        <div v-if="shiftStore.loading" class="flex justify-center py-8">
          <span class="text-sm text-muted-foreground">Loading...</span>
        </div>
        <WeeklyTimetable
          v-else
          :week-days="shiftStore.weekDays"
          :templates="shiftStore.templates"
          :shifts="shiftStore.shifts"
          :is-manager="auth.isManager"
          @cell-click="handleCellClick"
        />
      </CardContent>
    </Card>
  </div>
</template>
