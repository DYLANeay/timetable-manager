<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Shift, ShiftTemplate } from '@/types'
import type { PublicHoliday } from '@/api/holidays'
import type { LeaveRequest } from '@/api/leaves'
import { getUserColor, getInitials } from '@/composables/useUserColor'

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
  currentUserId: number
  pendingShiftIds: Set<number>
}>()

const emit = defineEmits<{
  cellClick: [date: string, template: ShiftTemplate]
  jumpToWeek: [mondayDate: string, targetDate: string]
  shiftClick: [shift: Shift]
}>()

const now = new Date()
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`

// Mobile: start from today's index or 0
const mobileStartIndex = ref(
  props.weekDays.includes(today) ? props.weekDays.indexOf(today) : 0,
)

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
      isSpecial: isSunday || !!holiday,
      isToday: d === today,
    }
  })
})

// Mobile shows 3 days starting from mobileStartIndex
const mobileDays = computed(() =>
  dayColumns.value.slice(mobileStartIndex.value, mobileStartIndex.value + 3),
)

const canGoBack = computed(() => mobileStartIndex.value > 0)
const canGoForward = computed(() => mobileStartIndex.value + 3 < props.weekDays.length)

// Date jump
const showDatePicker = ref(false)
const jumpDate = ref(today)
const pendingJumpDate = ref<string | null>(null)

// After weekDays changes (week navigation), jump to the pending target date
watch(() => props.weekDays, (newDays) => {
  if (pendingJumpDate.value) {
    const idx = newDays.indexOf(pendingJumpDate.value)
    if (idx !== -1) {
      mobileStartIndex.value = Math.min(idx, newDays.length - 3)
    }
    pendingJumpDate.value = null
  } else {
    mobileStartIndex.value = 0
  }
})

function getMonday(dateStr: string): string {
  const d = parseLocal(dateStr)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  const mon = new Date(d.getFullYear(), d.getMonth(), diff)
  const y = mon.getFullYear()
  const m = String(mon.getMonth() + 1).padStart(2, '0')
  const dd = String(mon.getDate()).padStart(2, '0')
  return `${y}-${m}-${dd}`
}

function jumpToDate() {
  showDatePicker.value = false
  const idx = props.weekDays.indexOf(jumpDate.value)
  if (idx !== -1) {
    // Date is in the current week
    mobileStartIndex.value = Math.min(idx, props.weekDays.length - 3)
  } else {
    // Navigate to the week containing the chosen date
    const monday = getMonday(jumpDate.value)
    pendingJumpDate.value = jumpDate.value
    emit('jumpToWeek', monday, jumpDate.value)
  }
}

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

function formatTime(time: string): string {
  return time.slice(0, 5)
}

function handleCellClick(date: string, shiftType: 'morning' | 'afternoon') {
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

    <!-- Mobile date jump bar -->
    <div class="flex items-center justify-between border-b px-2 py-1.5 md:hidden">
      <button
        class="rounded p-1 text-muted-foreground disabled:opacity-30"
        :disabled="!canGoBack"
        @click="mobileStartIndex = Math.max(0, mobileStartIndex - 1)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button
        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
        @click="showDatePicker = !showDatePicker"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        {{ locale === 'fr' ? 'Aller à une date' : 'Jump to date' }}
      </button>
      <button
        class="rounded p-1 text-muted-foreground disabled:opacity-30"
        :disabled="!canGoForward"
        @click="mobileStartIndex = Math.min(props.weekDays.length - 3, mobileStartIndex + 1)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <!-- Date picker dropdown (mobile) -->
    <div v-if="showDatePicker" class="flex items-center gap-2 border-b px-3 py-2 md:hidden">
      <input
        v-model="jumpDate"
        type="date"
        class="flex h-9 flex-1 rounded-md border border-input bg-background px-3 text-sm"
      />
      <button
        class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
        @click="jumpToDate"
      >
        OK
      </button>
    </div>

    <!-- Day headers -->
    <!-- Desktop: 7 cols, Mobile: 3 cols -->
    <div class="hidden grid-cols-7 border-b md:grid">
      <div
        v-for="col in dayColumns"
        :key="col.date"
        class="flex flex-col items-center border-r py-2 last:border-r-0"
        :class="[
          col.isToday ? 'bg-primary/5' : '',
          col.isHoliday ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '',
        ]"
      >
        <span class="text-[11px] font-medium tracking-wider" :class="col.isSpecial ? 'text-destructive' : 'text-muted-foreground'">
          {{ col.weekday }}
        </span>
        <span
          class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
          :class="[col.isToday ? 'bg-primary text-primary-foreground' : col.isSpecial ? 'text-destructive' : 'text-foreground']"
        >
          {{ col.day }}
        </span>
        <span v-if="col.isHoliday" class="mt-0.5 max-w-full truncate rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300" :title="col.holidayName ?? ''">
          {{ col.holidayName }}
        </span>
        <span v-for="leave in getLeavesForDate(col.date)" :key="`leave-${leave.id}`" class="mt-0.5 max-w-full truncate rounded-full bg-orange-100 px-1.5 py-0.5 text-[9px] font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
          Vacances : {{ leave.user.name }}
        </span>
      </div>
    </div>

    <!-- Mobile: 3-col headers -->
    <div class="grid grid-cols-3 border-b md:hidden">
      <div
        v-for="col in mobileDays"
        :key="col.date"
        class="flex flex-col items-center border-r py-2 last:border-r-0"
        :class="[col.isToday ? 'bg-primary/5' : '', col.isHoliday ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '']"
      >
        <span class="text-[11px] font-medium tracking-wider" :class="col.isSpecial ? 'text-destructive' : 'text-muted-foreground'">
          {{ col.weekday }}
        </span>
        <span
          class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold"
          :class="[col.isToday ? 'bg-primary text-primary-foreground' : col.isSpecial ? 'text-destructive' : 'text-foreground']"
        >
          {{ col.day }}
        </span>
        <span v-if="col.isHoliday" class="mt-0.5 max-w-full truncate rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700" :title="col.holidayName ?? ''">
          {{ col.holidayName }}
        </span>
        <span v-for="leave in getLeavesForDate(col.date)" :key="`leave-${leave.id}`" class="mt-0.5 max-w-full truncate rounded-full bg-orange-100 px-1.5 py-0.5 text-[9px] font-semibold text-orange-700">
          🏖 {{ leave.user.name }}
        </span>
      </div>
    </div>

    <!-- Time slots -->
    <div class="flex-1">
      <!-- Morning -->
      <div class="relative border-b">
        <div class="absolute left-0 top-0 z-10 -translate-y-1/2 px-2">
          <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            {{ t('schedule.morning') }}
          </span>
        </div>

        <!-- Desktop -->
        <div class="hidden grid-cols-7 pt-3 md:grid">
          <div
            v-for="col in dayColumns"
            :key="`morning-${col.date}`"
            class="min-h-[120px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[col.isToday ? 'bg-primary/5' : '', col.isHoliday ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : '', isManager ? 'cursor-pointer hover:bg-accent/50' : '']"
            @click="handleCellClick(col.date, 'morning')"
          >
            <div class="mb-1.5 text-[10px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'morning')?.start_time ?? '') }}–{{ formatTime(getTemplate(col.date, 'morning')?.end_time ?? '') }}
            </div>
            <div class="space-y-1">
              <div v-for="shift in getShiftsForCell(col.date, 'morning')" :key="shift.id"
                class="flex items-center gap-1.5 rounded-md p-1.5 ring-1"
                :class="[getUserColor(shift.user?.id ?? 0).bg, 'ring-black/5', !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
                :style="shiftStyle(shift)"
                @click="handleShiftClick(shift, $event)"
              >
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-medium text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-xs font-medium" :class="getUserColor(shift.user?.id ?? 0).text">
                  {{ shift.user?.name ?? t('schedule.unassigned') }}
                </span>
              </div>
            </div>
            <div v-if="getShiftsForCell(col.date, 'morning').length === 0 && isManager" class="flex h-14 items-center justify-center rounded-md border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>

        <!-- Mobile: 3 cols -->
        <div class="grid grid-cols-3 pt-3 md:hidden">
          <div
            v-for="col in mobileDays"
            :key="`m-mob-${col.date}`"
            class="min-h-[100px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[col.isToday ? 'bg-primary/5' : '', col.isHoliday ? 'bg-emerald-50/40' : '', isManager ? 'cursor-pointer hover:bg-accent/50' : '']"
            @click="handleCellClick(col.date, 'morning')"
          >
            <div class="mb-1 text-[9px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'morning')?.start_time ?? '') }}
            </div>
            <div class="space-y-1">
              <div v-for="shift in getShiftsForCell(col.date, 'morning')" :key="shift.id"
                class="flex items-center gap-1 rounded p-1 ring-1 ring-black/5"
                :class="[getUserColor(shift.user?.id ?? 0).bg, !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
                :style="shiftStyle(shift)"
                @click="handleShiftClick(shift, $event)"
              >
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[8px] font-bold text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-[10px] font-medium" :class="getUserColor(shift.user?.id ?? 0).text">
                  {{ shift.user?.name ?? '—' }}
                </span>
              </div>
            </div>
            <div v-if="getShiftsForCell(col.date, 'morning').length === 0 && isManager" class="flex h-10 items-center justify-center rounded border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Afternoon -->
      <div class="relative">
        <div class="absolute left-0 top-0 z-10 -translate-y-1/2 px-2">
          <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
            {{ t('schedule.afternoon') }}
          </span>
        </div>

        <!-- Desktop -->
        <div class="hidden grid-cols-7 pt-3 md:grid">
          <div
            v-for="col in dayColumns"
            :key="`afternoon-${col.date}`"
            class="min-h-[120px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[col.isToday ? 'bg-primary/5' : '', col.isHoliday ? 'bg-emerald-50/40 dark:bg-emerald-950/10' : '', isManager ? 'cursor-pointer hover:bg-accent/50' : '']"
            @click="handleCellClick(col.date, 'afternoon')"
          >
            <div class="mb-1.5 text-[10px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'afternoon')?.start_time ?? '') }}–{{ formatTime(getTemplate(col.date, 'afternoon')?.end_time ?? '') }}
            </div>
            <div class="space-y-1">
              <div v-for="shift in getShiftsForCell(col.date, 'afternoon')" :key="shift.id"
                class="flex items-center gap-1.5 rounded-md p-1.5 ring-1 ring-black/5"
                :class="[getUserColor(shift.user?.id ?? 0).bg, !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
                :style="shiftStyle(shift)"
                @click="handleShiftClick(shift, $event)"
              >
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-medium text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-xs font-medium" :class="getUserColor(shift.user?.id ?? 0).text">
                  {{ shift.user?.name ?? t('schedule.unassigned') }}
                </span>
              </div>
            </div>
            <div v-if="getShiftsForCell(col.date, 'afternoon').length === 0 && isManager" class="flex h-14 items-center justify-center rounded-md border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>

        <!-- Mobile: 3 cols -->
        <div class="grid grid-cols-3 pt-3 md:hidden">
          <div
            v-for="col in mobileDays"
            :key="`a-mob-${col.date}`"
            class="min-h-[100px] border-r p-1.5 last:border-r-0 transition-colors"
            :class="[col.isToday ? 'bg-primary/5' : '', col.isHoliday ? 'bg-emerald-50/40' : '', isManager ? 'cursor-pointer hover:bg-accent/50' : '']"
            @click="handleCellClick(col.date, 'afternoon')"
          >
            <div class="mb-1 text-[9px] text-muted-foreground/60">
              {{ formatTime(getTemplate(col.date, 'afternoon')?.start_time ?? '') }}
            </div>
            <div class="space-y-1">
              <div v-for="shift in getShiftsForCell(col.date, 'afternoon')" :key="shift.id"
                class="flex items-center gap-1 rounded p-1 ring-1 ring-black/5"
                :class="[getUserColor(shift.user?.id ?? 0).bg, !isManager && shift.user?.id !== currentUserId ? 'cursor-pointer hover:brightness-95' : '']"
                :style="shiftStyle(shift)"
                @click="handleShiftClick(shift, $event)"
              >
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[8px] font-bold text-white" :class="getUserColor(shift.user?.id ?? 0).avatar">
                  {{ getInitials(shift.user?.name ?? '?') }}
                </div>
                <span class="truncate text-[10px] font-medium" :class="getUserColor(shift.user?.id ?? 0).text">
                  {{ shift.user?.name ?? '—' }}
                </span>
              </div>
            </div>
            <div v-if="getShiftsForCell(col.date, 'afternoon').length === 0 && isManager" class="flex h-10 items-center justify-center rounded border border-dashed border-border/40 opacity-0 transition-opacity hover:opacity-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-muted-foreground/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
