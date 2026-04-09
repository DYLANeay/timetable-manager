<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import type { ViewMode } from '@/stores/shifts'

const { t, locale } = useI18n()

const props = defineProps<{
  currentWeek: string
  currentMonth: string
  viewMode: ViewMode
}>()

const emit = defineEmits<{
  previous: []
  next: []
  today: []
  setView: [mode: ViewMode]
}>()

function parseLocal(s: string): Date {
  const [y, m, d] = s.split('-').map(Number)
  return new Date(y!, m! - 1, d!)
}

const label = computed(() => {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const capitalize = (s: string) => s.charAt(0).toUpperCase() + s.slice(1)

  if (props.viewMode === 'month') {
    const [y, m] = props.currentMonth.split('-').map(Number)
    const d = new Date(y!, m! - 1, 1)
    return capitalize(new Intl.DateTimeFormat(loc, { month: 'long', year: 'numeric' }).format(d))
  }

  const start = parseLocal(props.currentWeek)
  const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6)
  const fmtMonth = new Intl.DateTimeFormat(loc, { month: 'long' })
  const startMonth = fmtMonth.format(start)
  const endMonth = fmtMonth.format(end)
  const year = end.getFullYear()
  if (startMonth === endMonth) return `${capitalize(startMonth)} ${year}`
  return `${capitalize(startMonth)} – ${capitalize(endMonth)} ${year}`
})

// Mobile: show week day range e.g. "17–23"
const weekRangeLabel = computed(() => {
  if (props.viewMode !== 'week') return ''
  const start = parseLocal(props.currentWeek)
  const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6)
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const fmtDay = new Intl.DateTimeFormat(loc, { day: 'numeric' })
  const fmtDayMonth = new Intl.DateTimeFormat(loc, { day: 'numeric', month: 'short' })
  if (start.getMonth() === end.getMonth()) {
    return `${fmtDay.format(start)}–${fmtDay.format(end)}`
  }
  return `${fmtDay.format(start)}–${fmtDayMonth.format(end)}`
})

const isCurrentPeriod = computed(() => {
  if (props.viewMode === 'month') {
    return props.currentMonth === new Date().toISOString().slice(0, 7)
  }
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(now)
  monday.setDate(diff)
  return monday.toISOString().split('T')[0] === props.currentWeek
})
</script>

<template>
  <div class="flex flex-1 items-center gap-2">
    <div class="flex items-baseline gap-2">
      <h2 class="text-lg font-semibold tracking-tight md:text-xl">{{ label }}</h2>
      <span v-if="weekRangeLabel" class="text-sm font-medium text-muted-foreground md:hidden">{{ weekRangeLabel }}</span>
    </div>

    <div class="ml-auto flex items-center gap-1">
      <!-- View toggle — desktop only -->
      <div class="mr-1 hidden items-center gap-0.5 rounded-lg border p-0.5 md:flex">
        <button
          class="rounded px-2.5 py-1 text-xs font-medium transition-colors"
          :class="viewMode === 'week' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
          @click="emit('setView', 'week')"
        >
          {{ locale === 'fr' ? 'Semaine' : 'Week' }}
        </button>
        <button
          class="rounded px-2.5 py-1 text-xs font-medium transition-colors"
          :class="viewMode === 'month' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
          @click="emit('setView', 'month')"
        >
          {{ locale === 'fr' ? 'Mois' : 'Month' }}
        </button>
      </div>

      <Button
        v-if="!isCurrentPeriod"
        variant="outline"
        size="sm"
        class="h-8 text-xs"
        @click="emit('today')"
      >
        {{ t('common.today') }}
      </Button>

      <Button variant="ghost" size="icon" class="h-8 w-8" @click="emit('previous')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </Button>

      <Button variant="ghost" size="icon" class="h-8 w-8" @click="emit('next')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </Button>
    </div>
  </div>
</template>
