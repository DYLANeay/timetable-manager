<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  currentWeek: string
}>()

const emit = defineEmits<{
  previous: []
  next: []
  today: []
}>()

const monthYear = computed(() => {
  const start = new Date(props.currentWeek)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)

  const fmtMonth = new Intl.DateTimeFormat('en-US', { month: 'long' })
  const fmtYear = new Intl.DateTimeFormat('en-US', { year: 'numeric' })

  const startMonth = fmtMonth.format(start)
  const endMonth = fmtMonth.format(end)
  const year = fmtYear.format(end)

  if (startMonth === endMonth) {
    return `${startMonth} ${year}`
  }
  return `${startMonth} – ${endMonth} ${year}`
})

const isCurrentWeek = computed(() => {
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(now)
  monday.setDate(diff)
  return monday.toISOString().split('T')[0] === props.currentWeek
})
</script>

<template>
  <div class="flex items-center gap-2">
    <h2 class="text-lg font-semibold tracking-tight md:text-xl">{{ monthYear }}</h2>

    <div class="ml-auto flex items-center gap-1">
      <Button
        v-if="!isCurrentWeek"
        variant="outline"
        size="sm"
        class="h-8 text-xs"
        @click="emit('today')"
      >
        Today
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
