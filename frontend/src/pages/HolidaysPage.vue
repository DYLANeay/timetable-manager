<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription,
} from '@/components/ui/dialog'
import { fetchHolidays, createHoliday, deleteHoliday, type PublicHoliday } from '@/api/holidays'

const { t, locale } = useI18n()

const holidays = ref<PublicHoliday[]>([])
const loading = ref(true)
const dialogOpen = ref(false)
const selectedYear = ref(new Date().getFullYear())
const form = ref({ date: '', name: '' })

const years = computed(() => {
  const y = new Date().getFullYear()
  return [y - 1, y, y + 1]
})

function formatDate(date: string): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  return new Intl.DateTimeFormat(loc, { weekday: 'long', day: 'numeric', month: 'long' })
    .format(new Date(date))
}

function isSunday(date: string): boolean {
  return new Date(date).getDay() === 0
}

async function load() {
  loading.value = true
  try {
    const res = await fetchHolidays(selectedYear.value)
    holidays.value = res.data
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  await createHoliday(form.value)
  dialogOpen.value = false
  form.value = { date: '', name: '' }
  await load()
}

async function handleDelete(id: number) {
  await deleteHoliday(id)
  await load()
}

function openCreate() {
  form.value = { date: `${selectedYear.value}-01-01`, name: '' }
  dialogOpen.value = true
}

onMounted(load)
</script>

<template>
  <div class="space-y-4 p-4 md:p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">{{ $t('holidays.title') }}</h1>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedYear"
          class="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          @change="load"
        >
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <Button size="sm" @click="openCreate">{{ $t('holidays.addHoliday') }}</Button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <span class="text-sm text-muted-foreground">{{ $t('common.loading') }}</span>
    </div>

    <div v-else-if="holidays.length === 0" class="py-8 text-center text-sm text-muted-foreground">
      {{ $t('holidays.noHolidays') }}
    </div>

    <div v-else class="space-y-2">
      <Card v-for="holiday in holidays" :key="holiday.id">
        <CardContent class="flex items-center justify-between p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
              {{ new Date(holiday.date).getDate() }}
            </div>
            <div>
              <p class="font-medium">{{ holiday.name }}</p>
              <p class="text-sm capitalize text-muted-foreground">{{ formatDate(holiday.date) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Badge v-if="isSunday(holiday.date)" variant="secondary" class="text-xs">
              Dimanche
            </Badge>
            <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive" @click="handleDelete(holiday.id)">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>

  <Dialog v-model:open="dialogOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('holidays.createHoliday') }}</DialogTitle>
        <DialogDescription>{{ $t('holidays.createDescription') }}</DialogDescription>
      </DialogHeader>
      <form class="space-y-4 py-4" @submit.prevent="handleCreate">
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('holidays.date') }}</label>
          <input
            v-model="form.date"
            type="date"
            required
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('holidays.name') }}</label>
          <input
            v-model="form.name"
            required
            :placeholder="locale === 'fr' ? 'ex. Noël' : 'e.g. Christmas'"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <DialogFooter>
          <Button type="submit">{{ $t('common.create') }}</Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
