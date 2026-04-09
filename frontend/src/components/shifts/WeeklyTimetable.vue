<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Shift, ShiftTemplate } from '@/types'

const { t, locale } = useI18n()

const props = defineProps<{
  weekDays: string[]
  templates: ShiftTemplate[]
  shifts: Shift[]
  isManager: boolean
}>()

const emit = defineEmits<{
  cellClick: [date: string, template: ShiftTemplate]
}>()

const today = new Date().toISOString().split('T')[0]

const dayColumns = computed(() => {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const weekdayFmt = new Intl.DateTimeFormat(loc, { weekday: 'short' })
  const dayFmt = new Intl.DateTimeFormat(loc, { day: 'numeric' })

  return props.weekDays.map((d) => {
    const date = new Date(d)
    return {
      date: d,
      weekday: weekdayFmt.format(date).replace('.', '').toUpperCase(),
      day: dayFmt.format(date),
      isSunday: date.getDay() === 0,
      isSaturday: date.getDay() === 6,
      isToday: d === today,
    }
  })
})

function getTemplate(date: string, shiftType: 'morning' | 'afternoon'): ShiftTemplate | undefined {
  const day = new Date(date).getDay()
  const dayType = day === 0 ? 'sunday_holiday' : 'weekday'
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

function getInitials(name: string): string {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Day headers -->
    <div class="grid grid-cols-7 border-b">
      <div
        v-for="col in dayColumns"
        :key="col.date"
        class="flex flex-col items-center border-r py-3 last:border-r-0"
        :class="col.isToday ? 'bg-primary/5' : ''"
      >
        <span
          class="text-[11px] font-medium tracking-wider"
          :class="[
            col.isSunday ? 'text-destructive' : 'text-muted-foreground',
          ]"
        >
          {{ col.weekday }}
        </span>
        <span
          class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
          :class="[
            col.isToday
              ? 'bg-primary text-primary-foreground'
              : col.isSunday
                ? 'text-destructive'
                : 'text-foreground',
          ]"
        >
          {{ col.day }}
        </span>
      </div>
    </div>

    <!-- Time slots grid -->
    <div class="flex-1">
      <!-- Morning block -->
      <div class="relative border-b">
        <div class="absolute -top-0 left-0 z-10 -translate-y-1/2 px-2">
          <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            {{ t('schedule.morning') }}
          </span>
        </div>
        <div class="grid grid-cols-7 pt-3">
          <div
            v-for="col in dayColumns"
            :key="`morning-${col.date}`"
            class="min-h-[120px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[
              col.isToday ? 'bg-primary/5' : '',
              isManager ? 'cursor-pointer hover:bg-accent/50' : '',
            ]"
            @click="handleCellClick(col.date, 'morning')"
          >
            <div class="mb-1.5 text-[10px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'morning')?.start_time ?? '') }}–{{ formatTime(getTemplate(col.date, 'morning')?.end_time ?? '') }}
            </div>

            <div class="space-y-1">
              <div
                v-for="shift in getShiftsForCell(col.date, 'morning')"
                :key="shift.id"
                class="group flex items-center gap-1.5 rounded-md bg-blue-50 p-1.5 ring-1 ring-blue-200/60 dark:bg-blue-950/30 dark:ring-blue-800/40"
              >
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500 text-[10px] font-medium text-white">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-xs font-medium text-blue-900 dark:text-blue-200">
                  {{ shift.user?.name ?? t('schedule.unassigned') }}
                </span>
              </div>
            </div>

            <div
              v-if="getShiftsForCell(col.date, 'morning').length === 0 && isManager"
              class="flex h-14 items-center justify-center rounded-md border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Afternoon block -->
      <div class="relative">
        <div class="absolute -top-0 left-0 z-10 -translate-y-1/2 px-2">
          <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
            {{ t('schedule.afternoon') }}
          </span>
        </div>
        <div class="grid grid-cols-7 pt-3">
          <div
            v-for="col in dayColumns"
            :key="`afternoon-${col.date}`"
            class="min-h-[120px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[
              col.isToday ? 'bg-primary/5' : '',
              isManager ? 'cursor-pointer hover:bg-accent/50' : '',
            ]"
            @click="handleCellClick(col.date, 'afternoon')"
          >
            <div class="mb-1.5 text-[10px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'afternoon')?.start_time ?? '') }}–{{ formatTime(getTemplate(col.date, 'afternoon')?.end_time ?? '') }}
            </div>

            <div class="space-y-1">
              <div
                v-for="shift in getShiftsForCell(col.date, 'afternoon')"
                :key="shift.id"
                class="group flex items-center gap-1.5 rounded-md bg-violet-50 p-1.5 ring-1 ring-violet-200/60 dark:bg-violet-950/30 dark:ring-violet-800/40"
              >
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-violet-500 text-[10px] font-medium text-white">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-xs font-medium text-violet-900 dark:text-violet-200">
                  {{ shift.user?.name ?? t('schedule.unassigned') }}
                </span>
              </div>
            </div>

            <div
              v-if="getShiftsForCell(col.date, 'afternoon').length === 0 && isManager"
              class="flex h-14 items-center justify-center rounded-md border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
