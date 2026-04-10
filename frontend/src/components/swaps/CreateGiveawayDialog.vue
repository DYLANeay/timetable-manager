<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { fetchMyShifts } from '@/api/shifts'
import { createGiveaway } from '@/api/swaps'
import type { Shift } from '@/types'

const { t, locale } = useI18n()

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  created: []
}>()

const shifts = ref<Shift[]>([])
const selectedShiftId = ref<string>('')
const note = ref('')
const loading = ref(false)

onMounted(async () => {
  const today = new Date()
  const weeks: string[] = []
  for (let i = 0; i < 4; i++) {
    const d = new Date(today)
    d.setDate(d.getDate() + i * 7)
    const monday = new Date(d)
    monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7))
    weeks.push(monday.toISOString().split('T')[0])
  }

  const unique = [...new Set(weeks)]
  const results = await Promise.all(unique.map((w) => fetchMyShifts(w)))
  const all = results.flatMap((r) => r.data)

  const todayStr = today.toISOString().split('T')[0]
  shifts.value = all.filter((s) => s.date >= todayStr)
})

function formatShift(shift: Shift): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const date = new Intl.DateTimeFormat(loc, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  }).format(new Date(shift.date))
  const type =
    shift.shift_template.shift_type === 'morning' ? t('schedule.morning') : t('schedule.afternoon')
  return `${date} — ${type}`
}

async function handleSubmit() {
  if (!selectedShiftId.value) return
  loading.value = true
  try {
    await createGiveaway({
      requester_shift_id: Number(selectedShiftId.value),
      note: note.value || undefined,
    })
    emit('created')
    emit('update:open', false)
    selectedShiftId.value = ''
    note.value = ''
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ t('swaps.giveAwayShift') }}</DialogTitle>
        <DialogDescription>{{ t('swaps.giveAwayDescription') }}</DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ t('swaps.selectShift') }}</label>
          <Select v-model="selectedShiftId">
            <SelectTrigger>
              <SelectValue :placeholder="t('swaps.selectShift')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="shift in shifts" :key="shift.id" :value="String(shift.id)">
                {{ formatShift(shift) }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ t('leaves.note') }}</label>
          <textarea
            v-model="note"
            class="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
            rows="2"
            maxlength="500"
          />
        </div>
      </div>

      <DialogFooter>
        <Button :disabled="!selectedShiftId || loading" @click="handleSubmit">
          {{ loading ? t('schedule.saving') : t('common.confirm') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
