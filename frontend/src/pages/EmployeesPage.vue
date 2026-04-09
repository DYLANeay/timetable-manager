<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from '@/components/ui/dialog'
import { fetchEmployees, createEmployee, updateEmployee, deleteEmployee } from '@/api/employees'
import type { User } from '@/types'

const employees = ref<User[]>([])
const loading = ref(true)
const dialogOpen = ref(false)
const editingEmployee = ref<User | null>(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'employee',
})

async function load() {
  loading.value = true
  try {
    const response = await fetchEmployees()
    employees.value = response.data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingEmployee.value = null
  form.value = { name: '', email: '', password: '', role: 'employee' }
  dialogOpen.value = true
}

function openEdit(employee: User) {
  editingEmployee.value = employee
  form.value = { name: employee.name, email: employee.email, password: '', role: employee.role }
  dialogOpen.value = true
}

async function handleSave() {
  if (editingEmployee.value) {
    const data: Record<string, string> = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
    }
    if (form.value.password) data.password = form.value.password
    await updateEmployee(editingEmployee.value.id, data)
  } else {
    await createEmployee(form.value)
  }
  dialogOpen.value = false
  await load()
}

async function handleDeactivate(employee: User) {
  await deleteEmployee(employee.id)
  await load()
}

onMounted(load)
</script>

<template>
  <div class="space-y-4 p-4 md:p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">{{ $t('employees.title') }}</h1>
      <Button size="sm" @click="openCreate">{{ $t('employees.addEmployee') }}</Button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <span class="text-sm text-muted-foreground">{{ $t('common.loading') }}</span>
    </div>

    <div v-else class="space-y-3">
      <Card v-for="emp in employees" :key="emp.id">
        <CardContent class="flex items-center justify-between p-4">
          <div>
            <p class="font-medium">{{ emp.name }}</p>
            <p class="text-sm text-muted-foreground">{{ emp.email }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Badge :variant="emp.role === 'manager' ? 'default' : 'secondary'">
              {{ emp.role === 'manager' ? $t('employees.roleManager') : $t('employees.roleEmployee') }}
            </Badge>
            <Button size="sm" variant="outline" @click="openEdit(emp)">{{ $t('common.edit') }}</Button>
            <Button size="sm" variant="destructive" @click="handleDeactivate(emp)">
              {{ $t('employees.deactivate') }}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>

  <Dialog v-model:open="dialogOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ editingEmployee ? $t('employees.editEmployee') : $t('employees.createEmployee') }}</DialogTitle>
        <DialogDescription>
          {{ editingEmployee ? $t('employees.editDescription') : $t('employees.createDescription') }}
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-4 py-4" @submit.prevent="handleSave">
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('employees.name') }}</label>
          <input
            v-model="form.name"
            required
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('employees.email') }}</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">
            {{ $t('employees.password') }} {{ editingEmployee ? $t('employees.passwordKeep') : '' }}
          </label>
          <input
            v-model="form.password"
            type="password"
            :required="!editingEmployee"
            minlength="8"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('employees.role') }}</label>
          <select
            v-model="form.role"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <option value="employee">{{ $t('employees.roleEmployee') }}</option>
            <option value="manager">{{ $t('employees.roleManager') }}</option>
          </select>
        </div>

        <DialogFooter>
          <Button type="submit">
            {{ editingEmployee ? $t('common.update') : $t('common.create') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
