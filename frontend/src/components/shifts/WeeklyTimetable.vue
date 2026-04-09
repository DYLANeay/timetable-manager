<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import type { Shift, ShiftTemplate } from '@/types'

const props = defineProps<{
  weekDays: string[]
  templates: ShiftTemplate[]
  shifts: Shift[]
  isManager: boolean
}>()

const emit = defineEmits<{
  cellClick: [date: string, template: ShiftTemplate]
}>()

const dayLabels = computed(() => {
  const fmt = new Intl.DateTimeFormat('fr-CH', { weekday: 'short', day: 'numeric' })
  return props.weekDays.map((d) => ({
    date: d,
    label: fmt.format(new Date(d)),
    isSunday: new Date(d).getDay() === 0,
  }))
})

const morningTemplates = computed(() =>
  props.templates.filter((t) => t.shift_type === 'morning'),
)

const afternoonTemplates = computed(() =>
  props.templates.filter((t) => t.shift_type === 'afternoon'),
)

function getTemplate(date: string, shiftType: 'morning' | 'afternoon'): ShiftTemplate | undefined {
  const day = new Date(date).getDay()
  const isSundayOrHoliday = day === 0
  const dayType = isSundayOrHoliday ? 'sunday_holiday' : 'weekday'
  return props.templates.find((t) => t.day_type === dayType && t.shift_type === shiftType)
}

function getShiftsForCell(date: string, shiftType: 'morning' | 'afternoon'): Shift[] {
  const template = getTemplate(date, shiftType)
  if (!template) return []
  return props.shifts.filter(
    (s) => s.date === date && s.shift_template.id === template.id,
  )
}

function formatTime(time: string): string {
  return time.slice(0, 5)
}

function handleCellClick(date: string, shiftType: 'morning' | 'afternoon') {
  const template = getTemplate(date, shiftType)
  if (template && props.isManager) {
    emit('cellClick', date, template)
  }
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[640px] border-collapse text-sm">
      <thead>
        <tr>
          <th class="w-20 border-b p-2 text-left text-xs font-medium text-muted-foreground">
            Shift
          </th>
          <th
            v-for="day in dayLabels"
            :key="day.date"
            class="border-b p-2 text-center text-xs font-medium"
            :class="day.isSunday ? 'text-destructive' : 'text-muted-foreground'"
          >
            {{ day.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- Morning row -->
        <tr>
          <td class="border-b p-2 text-xs font-medium text-muted-foreground">
            Morning
          </td>
          <td
            v-for="day in dayLabels"
            :key="`morning-${day.date}`"
            class="border-b p-1 text-center"
            :class="isManager ? 'cursor-pointer hover:bg-accent' : ''"
            @click="handleCellClick(day.date, 'morning')"
          >
            <div class="min-h-[3rem] space-y-1">
              <template v-for="shift in getShiftsForCell(day.date, 'morning')" :key="shift.id">
                <Badge variant="secondary" class="block text-xs">
                  {{ shift.user?.name ?? 'Unassigned' }}
                </Badge>
              </template>
              <span
                v-if="getShiftsForCell(day.date, 'morning').length === 0"
                class="text-xs text-muted-foreground/50"
              >
                —
              </span>
            </div>
            <div class="mt-1 text-[10px] text-muted-foreground">
              {{ formatTime(getTemplate(day.date, 'morning')?.start_time ?? '') }}–{{ formatTime(getTemplate(day.date, 'morning')?.end_time ?? '') }}
            </div>
          </td>
        </tr>

        <!-- Afternoon row -->
        <tr>
          <td class="border-b p-2 text-xs font-medium text-muted-foreground">
            Afternoon
          </td>
          <td
            v-for="day in dayLabels"
            :key="`afternoon-${day.date}`"
            class="border-b p-1 text-center"
            :class="isManager ? 'cursor-pointer hover:bg-accent' : ''"
            @click="handleCellClick(day.date, 'afternoon')"
          >
            <div class="min-h-[3rem] space-y-1">
              <template v-for="shift in getShiftsForCell(day.date, 'afternoon')" :key="shift.id">
                <Badge variant="secondary" class="block text-xs">
                  {{ shift.user?.name ?? 'Unassigned' }}
                </Badge>
              </template>
              <span
                v-if="getShiftsForCell(day.date, 'afternoon').length === 0"
                class="text-xs text-muted-foreground/50"
              >
                —
              </span>
            </div>
            <div class="mt-1 text-[10px] text-muted-foreground">
              {{ formatTime(getTemplate(day.date, 'afternoon')?.start_time ?? '') }}–{{ formatTime(getTemplate(day.date, 'afternoon')?.end_time ?? '') }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
