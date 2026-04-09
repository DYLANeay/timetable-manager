<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  currentWeek: string
}>()

const emit = defineEmits<{
  previous: []
  next: []
}>()

const weekLabel = computed(() => {
  const start = new Date(props.currentWeek)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)

  const fmt = new Intl.DateTimeFormat('fr-CH', { day: 'numeric', month: 'short' })
  return `${fmt.format(start)} – ${fmt.format(end)} ${end.getFullYear()}`
})
</script>

<template>
  <div class="flex items-center justify-between gap-4">
    <Button variant="outline" size="icon" @click="emit('previous')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </Button>

    <span class="text-sm font-medium">{{ weekLabel }}</span>

    <Button variant="outline" size="icon" @click="emit('next')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </Button>
  </div>
</template>
