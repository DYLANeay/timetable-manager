<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription,
} from '@/components/ui/dialog'
import {
  fetchLeaveRequests, createLeaveRequest, decideLeaveRequest, cancelLeaveRequest,
  fetchLeaveBalance, type LeaveRequest, type LeaveBalance,
} from '@/api/leaves'

const { t, locale } = useI18n()
const auth = useAuthStore()

const leaves = ref<LeaveRequest[]>([])
const balance = ref<LeaveBalance | null>(null)
const loading = ref(true)
const dialogOpen = ref(false)
const selectedYear = ref(new Date().getFullYear())
const form = ref({ start_date: '', end_date: '', note: '' })

const years = computed(() => {
  const y = new Date().getFullYear()
  return [y - 1, y, y + 1]
})

function formatDate(date: string): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const [y, m, d] = date.split('-').map(Number)
  return new Intl.DateTimeFormat(loc, { day: 'numeric', month: 'short' })
    .format(new Date(y!, m! - 1, d!))
}

function formatRange(start: string, end: string): string {
  return `${formatDate(start)} → ${formatDate(end)}`
}

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  pending: 'outline',
  approved: 'default',
  denied: 'destructive',
}

async function load() {
  loading.value = true
  try {
    const [res, bal] = await Promise.all([
      fetchLeaveRequests(selectedYear.value),
      fetchLeaveBalance(selectedYear.value),
    ])
    leaves.value = res.data
    balance.value = bal
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  await createLeaveRequest({
    start_date: form.value.start_date,
    end_date: form.value.end_date,
    note: form.value.note || undefined,
  })
  dialogOpen.value = false
  form.value = { start_date: '', end_date: '', note: '' }
  await load()
}

async function handleDecide(id: number, status: 'approved' | 'denied') {
  await decideLeaveRequest(id, status)
  await load()
}

async function handleCancel(id: number) {
  await cancelLeaveRequest(id)
  await load()
}

function openCreate() {
  form.value = { start_date: '', end_date: '', note: '' }
  dialogOpen.value = true
}

onMounted(load)
</script>

<template>
  <div class="space-y-4 p-4 md:p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">{{ $t('leaves.title') }}</h1>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedYear"
          class="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          @change="load"
        >
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <Button size="sm" @click="openCreate">{{ $t('leaves.request') }}</Button>
      </div>
    </div>

    <!-- Balance card -->
    <Card v-if="balance">
      <CardContent class="p-4">
        <div class="flex items-center justify-around gap-2">
          <div class="text-center">
            <p class="text-2xl font-bold text-primary">{{ balance.remaining_days }}</p>
            <p class="text-xs text-muted-foreground">{{ $t('leaves.remaining') }}</p>
          </div>
          <div class="h-8 w-px bg-border" />
          <div class="text-center">
            <p class="text-2xl font-bold">{{ balance.used_days }}</p>
            <p class="text-xs text-muted-foreground">{{ $t('leaves.used') }}</p>
          </div>
          <div class="h-8 w-px bg-border" />
          <div class="text-center">
            <p class="text-2xl font-bold text-muted-foreground">{{ balance.total_days }}</p>
            <p class="text-xs text-muted-foreground">{{ $t('leaves.total') }}</p>
          </div>
        </div>
        <div class="mt-3">
          <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
            <div
              class="h-full rounded-full bg-primary transition-all"
              :style="{ width: `${(balance.used_days / balance.total_days) * 100}%` }"
            />
          </div>
          <p class="mt-1 text-right text-[10px] text-muted-foreground">
            {{ balance.used_days }}/{{ balance.total_days }} {{ $t('leaves.days') }}
          </p>
        </div>
      </CardContent>
    </Card>

    <div v-if="loading" class="flex justify-center py-8">
      <span class="text-sm text-muted-foreground">{{ $t('common.loading') }}</span>
    </div>

    <div v-else-if="leaves.length === 0" class="py-8 text-center text-sm text-muted-foreground">
      {{ $t('leaves.noRequests') }}
    </div>

    <div v-else class="space-y-2">
      <Card v-for="leave in leaves" :key="leave.id">
        <CardContent class="flex items-center justify-between p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-lg dark:bg-orange-900/30">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600 dark:text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            </div>
            <div>
              <p class="font-medium">
                <template v-if="auth.isManager && leave.user.id !== auth.user?.id">
                  {{ leave.user.name }} —
                </template>
                {{ formatRange(leave.start_date, leave.end_date) }}
              </p>
              <p class="text-sm text-muted-foreground">
                {{ leave.business_days }} {{ $t('leaves.days') }}
                <template v-if="leave.note"> · {{ leave.note }}</template>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Badge :variant="statusVariant[leave.status]">
              {{ $t(`leaves.status.${leave.status}`) }}
            </Badge>
            <template v-if="auth.isManager && leave.status === 'pending'">
              <Button size="sm" variant="outline" @click="handleDecide(leave.id, 'approved')">
                {{ $t('leaves.approve') }}
              </Button>
              <Button size="sm" variant="ghost" class="text-destructive" @click="handleDecide(leave.id, 'denied')">
                {{ $t('leaves.deny') }}
              </Button>
            </template>
            <Button
              v-if="leave.user.id === auth.user?.id && leave.status === 'pending'"
              size="sm"
              variant="ghost"
              class="text-destructive"
              @click="handleCancel(leave.id)"
            >
              {{ $t('common.cancel') }}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>

  <Dialog v-model:open="dialogOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('leaves.requestTitle') }}</DialogTitle>
        <DialogDescription>{{ $t('leaves.requestDescription') }}</DialogDescription>
      </DialogHeader>
      <form class="space-y-4 py-4" @submit.prevent="handleCreate">
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('leaves.startDate') }}</label>
          <input
            v-model="form.start_date"
            type="date"
            required
            class="block h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('leaves.endDate') }}</label>
          <input
            v-model="form.end_date"
            type="date"
            required
            class="block h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('leaves.note') }}</label>
          <textarea
            v-model="form.note"
            rows="2"
            :placeholder="locale === 'fr' ? 'Raison (optionnel)' : 'Reason (optional)'"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <DialogFooter>
          <Button type="submit">{{ $t('leaves.submit') }}</Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
