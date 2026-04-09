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
import { fetchEmployees } from '@/api/employees'
import { createShift, updateShift, deleteShift } from '@/api/shifts'
import type { Shift, ShiftTemplate, User } from '@/types'

const { t, locale } = useI18n()

const props = defineProps<{
  open: boolean
  date: string
  template: ShiftTemplate
  existingShift?: Shift
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  saved: []
}>()

const employees = ref<User[]>([])
const selectedUserId = ref<string>('')
const loading = ref(false)

onMounted(async () => {
  const response = await fetchEmployees()
  employees.value = response.data
  if (props.existingShift?.user) {
    selectedUserId.value = String(props.existingShift.user.id)
  }
})

const dateLabel = computed(() => {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  return new Intl.DateTimeFormat(loc, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(new Date(props.date))
})

const shiftTypeLabel = computed(() =>
  props.template.shift_type === 'morning' ? t('schedule.morning') : t('schedule.afternoon')
)

async function handleSave() {
  loading.value = true
  try {
    const userId = selectedUserId.value ? Number(selectedUserId.value) : null
    if (props.existingShift) {
      await updateShift(props.existingShift.id, { user_id: userId })
    } else {
      await createShift({
        user_id: userId,
        shift_template_id: props.template.id,
        date: props.date,
      })
    }
    emit('saved')
    emit('update:open', false)
  } finally {
    loading.value = false
  }
}

async function handleRemove() {
  if (!props.existingShift) return
  loading.value = true
  try {
    await deleteShift(props.existingShift.id)
    emit('saved')
    emit('update:open', false)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('schedule.assignShift') }}</DialogTitle>
        <DialogDescription>
          {{ dateLabel }} — {{ shiftTypeLabel }}
          ({{ template.start_time.slice(0, 5) }}–{{ template.end_time.slice(0, 5) }})
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('schedule.employee') }}</label>
          <Select v-model="selectedUserId">
            <SelectTrigger>
              <SelectValue :placeholder="$t('schedule.selectEmployee')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="emp in employees"
                :key="emp.id"
                :value="String(emp.id)"
              >
                {{ emp.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <DialogFooter class="gap-2">
        <Button
          v-if="existingShift"
          variant="destructive"
          :disabled="loading"
          @click="handleRemove"
        >
          {{ $t('schedule.remove') }}
        </Button>
        <Button :disabled="loading" @click="handleSave">
          {{ loading ? $t('schedule.saving') : existingShift ? $t('common.update') : $t('schedule.assign') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
