<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
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
import { createSwapRequest } from '@/api/swaps'
import type { Shift } from '@/types'

const { t, locale } = useI18n()

const props = defineProps<{
  open: boolean
  targetShift: Shift
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  created: []
}>()

const myShifts = ref<Shift[]>([])
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
    weeks.push(monday.toISOString().slice(0, 10))
  }
  const unique = [...new Set(weeks)]
  const results = await Promise.all(unique.map((w) => fetchMyShifts(w)))
  const todayStr = today.toISOString().slice(0, 10)
  myShifts.value = results
    .flatMap((r) => r.data)
    .filter((s) => s.date >= todayStr && s.id !== props.targetShift.id)
})

function formatShift(shift: Shift): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const date = new Intl.DateTimeFormat(loc, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  }).format(new Date(shift.date + 'T12:00:00'))
  const type =
    shift.shift_template.shift_type === 'morning' ? t('schedule.morning') : t('schedule.afternoon')
  return `${date} — ${type}`
}

const targetLabel = computed(() => formatShift(props.targetShift))

async function handleSubmit() {
  if (!selectedShiftId.value) return
  loading.value = true
  try {
    await createSwapRequest({
      requester_shift_id: Number(selectedShiftId.value),
      target_shift_id: props.targetShift.id,
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
        <DialogTitle>{{ t('swaps.requestSwap') }}</DialogTitle>
        <DialogDescription>{{ t('swaps.requestSwapDescription') }}</DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="rounded-md bg-muted px-3 py-2 text-sm">
          <span class="text-muted-foreground">{{ t('swaps.theirShift') }}:</span>
          <p class="font-medium">{{ targetShift.user?.name }} — {{ targetLabel }}</p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ t('swaps.yourShift') }}</label>
          <Select v-model="selectedShiftId">
            <SelectTrigger>
              <SelectValue :placeholder="t('swaps.selectShift')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="shift in myShifts" :key="shift.id" :value="String(shift.id)">
                {{ formatShift(shift) }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="myShifts.length === 0" class="text-xs text-muted-foreground">
            {{ t('swaps.noShiftsToOffer') }}
          </p>
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
        <Button :disabled="!selectedShiftId || loading || myShifts.length === 0" @click="handleSubmit">
          {{ loading ? t('schedule.saving') : t('swaps.sendRequest') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
