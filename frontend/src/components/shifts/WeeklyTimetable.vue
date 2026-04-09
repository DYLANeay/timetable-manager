<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Shift, ShiftTemplate } from '@/types'
import type { PublicHoliday } from '@/api/holidays'
import type { LeaveRequest } from '@/api/leaves'

const { t, locale } = useI18n()

function parseLocal(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y!, m! - 1, d!)
}

const props = defineProps<{
  weekDays: string[]
  templates: ShiftTemplate[]
  shifts: Shift[]
  holidays: PublicHoliday[]
  leaveRequests: LeaveRequest[]
  isManager: boolean
}>()

const emit = defineEmits<{
  cellClick: [date: string, template: ShiftTemplate]
}>()

const now = new Date()
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`

const holidayMap = computed(() =>
  Object.fromEntries(props.holidays.map((h) => [h.date, h])),
)

const dayColumns = computed(() => {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const weekdayFmt = new Intl.DateTimeFormat(loc, { weekday: 'short' })
  const dayFmt = new Intl.DateTimeFormat(loc, { day: 'numeric' })

  return props.weekDays.map((d) => {
    const date = parseLocal(d)
    const holiday = holidayMap.value[d]
    const isSunday = date.getDay() === 0
    return {
      date: d,
      weekday: weekdayFmt.format(date).replace('.', '').toUpperCase(),
      day: dayFmt.format(date),
      isSunday,
      isHoliday: !!holiday,
      holidayName: holiday?.name ?? null,
      isSpecial: isSunday || !!holiday, // uses sunday_holiday template
      isToday: d === today,
    }
  })
})

function getTemplate(date: string, shiftType: 'morning' | 'afternoon'): ShiftTemplate | undefined {
  const day = parseLocal(date).getDay()
  const isHoliday = !!holidayMap.value[date]
  const dayType = (day === 0 || isHoliday) ? 'sunday_holiday' : 'weekday'
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

function getLeavesForDate(date: string): LeaveRequest[] {
  return props.leaveRequests.filter(
    (l) => l.status === 'approved' && l.start_date <= date && l.end_date >= date,
  )
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
        class="flex flex-col items-center border-r py-2 last:border-r-0"
        :class="[
          col.isToday ? 'bg-primary/5' : '',
          col.isHoliday ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '',
        ]"
      >
        <span
          class="text-[11px] font-medium tracking-wider"
          :class="col.isSpecial ? 'text-destructive' : 'text-muted-foreground'"
        >
          {{ col.weekday }}
        </span>
        <span
          class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
          :class="[
            col.isToday
              ? 'bg-primary text-primary-foreground'
              : col.isSpecial
                ? 'text-destructive'
                : 'text-foreground',
          ]"
        >
          {{ col.day }}
        </span>
        <!-- Holiday badge -->
        <span
          v-if="col.isHoliday"
          class="mt-0.5 max-w-full truncate rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
          :title="col.holidayName ?? ''"
        >
          {{ col.holidayName }}
        </span>
        <!-- Leave badges -->
        <span
          v-for="leave in getLeavesForDate(col.date)"
          :key="`leave-${leave.id}`"
          class="mt-0.5 max-w-full truncate rounded-full bg-orange-100 px-1.5 py-0.5 text-[9px] font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300"
          :title="leave.user.name"
        >
          {{ leave.user.name }}
        </span>
      </div>
    </div>

    <!-- Time slots grid -->
    <div class="flex-1">
      <!-- Morning block -->
      <div class="relative border-b">
        <div class="absolute left-0 top-0 z-10 -translate-y-1/2 px-2">
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
              col.isHoliday ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : '',
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
                class="flex items-center gap-1.5 rounded-md bg-blue-50 p-1.5 ring-1 ring-blue-200/60 dark:bg-blue-950/30 dark:ring-blue-800/40"
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
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Afternoon block -->
      <div class="relative">
        <div class="absolute left-0 top-0 z-10 -translate-y-1/2 px-2">
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
              col.isHoliday ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : '',
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
                class="flex items-center gap-1.5 rounded-md bg-violet-50 p-1.5 ring-1 ring-violet-200/60 dark:bg-violet-950/30 dark:ring-violet-800/40"
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
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
