<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Shift, ShiftTemplate } from '@/types'
import type { PublicHoliday } from '@/api/holidays'
import type { LeaveRequest } from '@/api/leaves'
import { getUserColor, getInitials } from '@/composables/useUserColor'

const { t, locale } = useI18n()

const props = defineProps<{
  monthDays: string[]
  currentMonth: string
  templates: ShiftTemplate[]
  shifts: Shift[]
  holidays: PublicHoliday[]
  leaveRequests: LeaveRequest[]
  isManager: boolean
  currentUserId: number
  pendingShiftIds: Set<number>
}>()

const emit = defineEmits<{
  cellClick: [date: string, template: ShiftTemplate]
  shiftClick: [shift: Shift]
}>()

const now = new Date()
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
const currentMonthNum = Number(props.currentMonth.split('-')[1])

function parseLocal(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y!, m! - 1, d!)
}

const holidayMap = computed(() =>
  Object.fromEntries(props.holidays.map((h) => [h.date, h])),
)

const weekdayHeaders = computed(() => {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  // Use known dates for Mon-Sun (local dates)
  const base = new Date(2026, 3, 6) // April 6, 2026 = Monday
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(2026, 3, 6 + i)
    return new Intl.DateTimeFormat(loc, { weekday: 'short' }).format(d).replace('.', '').toUpperCase()
  })
})

const days = computed(() =>
  props.monthDays.map((d) => {
    const date = parseLocal(d)
    const holiday = holidayMap.value[d]
    const isSunday = date.getDay() === 0
    const isCurrentMonth = date.getMonth() + 1 === currentMonthNum
    const isSpecial = isSunday || !!holiday
    return {
      date: d,
      day: date.getDate(),
      isCurrentMonth,
      isSunday,
      isHoliday: !!holiday,
      holidayName: holiday?.name ?? null,
      isSpecial,
      isToday: d === today,
    }
  })
)

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

function getLeavesForDate(date: string): LeaveRequest[] {
  return props.leaveRequests.filter(
    (l) => l.status === 'approved' && l.start_date <= date && l.end_date >= date,
  )
}

function handleClick(date: string, shiftType: 'morning' | 'afternoon') {
  const template = getTemplate(date, shiftType)
  if (template && props.isManager) {
    emit('cellClick', date, template)
  }
}

function handleShiftClick(shift: Shift, event: MouseEvent) {
  if (props.isManager) return
  event.stopPropagation()
  if (shift.user?.id !== props.currentUserId) {
    emit('shiftClick', shift)
  }
}

const STRIPE = 'repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(0,0,0,0.07) 5px, rgba(0,0,0,0.07) 10px)'

function shiftStyle(shift: Shift): Record<string, string> {
  return props.pendingShiftIds.has(shift.id) ? { backgroundImage: STRIPE } : {}
}
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Weekday headers -->
    <div class="grid grid-cols-7 border-b">
      <div
        v-for="label in weekdayHeaders"
        :key="label"
        class="border-r py-2 text-center text-[11px] font-medium tracking-wider text-muted-foreground last:border-r-0"
      >
        {{ label }}
      </div>
    </div>

    <!-- Day grid -->
    <div class="grid flex-1 grid-cols-7" style="grid-template-rows: repeat(6, 1fr)">
      <div
        v-for="col in days"
        :key="col.date"
        class="group min-h-[100px] border-b border-r p-1 last-of-type:border-r-0"
        :class="[
          !col.isCurrentMonth ? 'bg-muted/30' : '',
          col.isToday ? 'bg-primary/5' : '',
          col.isHoliday ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '',
        ]"
      >
        <!-- Day number + holiday name -->
        <div class="mb-1 flex items-center justify-between">
          <span
            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
            :class="[
              col.isToday ? 'bg-primary text-primary-foreground' :
              col.isHoliday ? 'text-emerald-700 dark:text-emerald-400' :
              col.isSunday ? 'text-destructive' :
              !col.isCurrentMonth ? 'text-muted-foreground/50' :
              'text-foreground',
            ]"
          >
            {{ col.day }}
          </span>
          <span
            v-if="col.isHoliday"
            class="truncate rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
            :title="col.holidayName ?? ''"
          >
            {{ col.holidayName }}
          </span>
        </div>

        <!-- Shift chips -->
        <div class="space-y-0.5">
          <!-- Morning -->
          <div
            v-if="getShiftsForCell(col.date, 'morning').length > 0 || (isManager && col.isCurrentMonth)"
            :class="isManager ? 'cursor-pointer' : ''"
            @click="handleClick(col.date, 'morning')"
          >
            <div
              v-for="shift in getShiftsForCell(col.date, 'morning')"
              :key="`m-${shift.id}`"
              class="flex items-center gap-1 rounded px-1.5 py-0.5"
              :class="[getUserColor(shift.user?.id ?? 0).bg, !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
              :style="shiftStyle(shift)"
              @click="handleShiftClick(shift, $event)"
            >
              <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[8px] font-bold text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                {{ getInitials(shift.user?.name ?? '?') }}
              </div>
              <span class="truncate text-[10px] font-medium" :class="getUserColor(shift.user?.id ?? 0).text">{{ shift.user?.name }}</span>
            </div>
            <div
              v-if="isManager && getShiftsForCell(col.date, 'morning').length === 0 && col.isCurrentMonth"
              class="flex h-5 items-center justify-center rounded border border-dashed border-blue-300/40 text-[8px] text-blue-400 opacity-0 transition-opacity group-hover:opacity-100"
            >
              + {{ t('schedule.morning') }}
            </div>
          </div>

          <!-- Afternoon -->
          <div
            v-if="getShiftsForCell(col.date, 'afternoon').length > 0 || (isManager && col.isCurrentMonth)"
            :class="isManager ? 'cursor-pointer' : ''"
            @click="handleClick(col.date, 'afternoon')"
          >
            <div
              v-for="shift in getShiftsForCell(col.date, 'afternoon')"
              :key="`a-${shift.id}`"
              class="flex items-center gap-1 rounded px-1.5 py-0.5"
              :class="[getUserColor(shift.user?.id ?? 0).bg, !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
              :style="shiftStyle(shift)"
              @click="handleShiftClick(shift, $event)"
            >
              <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[8px] font-bold text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                {{ getInitials(shift.user?.name ?? '?') }}
              </div>
              <span class="truncate text-[10px] font-medium" :class="getUserColor(shift.user?.id ?? 0).text">{{ shift.user?.name }}</span>
            </div>
            <div
              v-if="isManager && getShiftsForCell(col.date, 'afternoon').length === 0 && col.isCurrentMonth"
              class="flex h-5 items-center justify-center rounded border border-dashed border-violet-300/40 text-[8px] text-violet-400 opacity-0 transition-opacity group-hover:opacity-100"
            >
              + {{ t('schedule.afternoon') }}
            </div>
          </div>

          <!-- Leaves -->
          <div
            v-for="leave in getLeavesForDate(col.date)"
            :key="`l-${leave.id}`"
            class="flex items-center gap-1 rounded bg-orange-100 px-1.5 py-0.5 dark:bg-orange-950/40"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            <span class="truncate text-[10px] font-medium text-orange-900 dark:text-orange-200">Vacances : {{ leave.user.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
