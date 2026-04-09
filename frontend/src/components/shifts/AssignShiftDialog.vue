<script setup lang="ts">
import { ref, onMounted } from 'vue'
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

const dateLabel = new Intl.DateTimeFormat('fr-CH', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}).format(new Date(props.date))

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
        <DialogTitle>Assign Shift</DialogTitle>
        <DialogDescription>
          {{ dateLabel }} — {{ template.shift_type === 'morning' ? 'Morning' : 'Afternoon' }}
          ({{ template.start_time.slice(0, 5) }}–{{ template.end_time.slice(0, 5) }})
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">Employee</label>
          <Select v-model="selectedUserId">
            <SelectTrigger>
              <SelectValue placeholder="Select an employee" />
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
          Remove
        </Button>
        <Button :disabled="loading" @click="handleSave">
          {{ loading ? 'Saving...' : existingShift ? 'Update' : 'Assign' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
