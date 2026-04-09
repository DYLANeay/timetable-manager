<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/stores/auth'
import {
  respondToSwapRequest,
  decideSwapRequest,
  cancelSwapRequest,
  type SwapRequestData,
} from '@/api/swaps'

const { t, locale } = useI18n()

const props = defineProps<{
  request: SwapRequestData
}>()

const emit = defineEmits<{
  updated: []
}>()

const auth = useAuthStore()
const loading = ref(false)

const statusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  pending_peer: 'secondary',
  peer_accepted: 'outline',
  peer_declined: 'destructive',
  manager_approved: 'default',
  manager_denied: 'destructive',
  cancelled: 'secondary',
}

function formatShift(shift: SwapRequestData['requester_shift']): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  const date = new Intl.DateTimeFormat(loc, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  }).format(new Date(shift.date))
  const type = shift.shift_template.shift_type === 'morning' ? t('schedule.morning') : t('schedule.afternoon')
  return `${date} — ${type}`
}

const canPeerRespond =
  props.request.status === 'pending_peer' && props.request.target.id === auth.user?.id

const canManagerDecide =
  props.request.status === 'peer_accepted' && auth.isManager

const canCancel =
  props.request.requester.id === auth.user?.id &&
  !['manager_approved', 'manager_denied', 'cancelled'].includes(props.request.status)

async function handleRespond(accept: boolean) {
  loading.value = true
  try {
    await respondToSwapRequest(props.request.id, accept)
    emit('updated')
  } finally {
    loading.value = false
  }
}

async function handleDecide(approve: boolean) {
  loading.value = true
  try {
    await decideSwapRequest(props.request.id, approve)
    emit('updated')
  } finally {
    loading.value = false
  }
}

async function handleCancel() {
  loading.value = true
  try {
    await cancelSwapRequest(props.request.id)
    emit('updated')
  } finally {
    loading.value = false
  }
}

const statusKey = computed(() => `swaps.status.${props.request.status}`)
</script>

<template>
  <Card>
    <CardHeader class="pb-2">
      <div class="flex items-center justify-between">
        <CardTitle class="text-sm font-medium">
          {{ request.requester.name }} ↔ {{ request.target.name }}
        </CardTitle>
        <Badge :variant="statusVariants[request.status] ?? 'secondary'">
          {{ t(statusKey) }}
        </Badge>
      </div>
    </CardHeader>
    <CardContent class="space-y-2 text-sm">
      <div class="grid grid-cols-2 gap-2">
        <div>
          <span class="text-muted-foreground">{{ request.requester.name }}:</span>
          <p class="font-medium">{{ formatShift(request.requester_shift) }}</p>
        </div>
        <div>
          <span class="text-muted-foreground">{{ request.target.name }}:</span>
          <p class="font-medium">{{ formatShift(request.target_shift) }}</p>
        </div>
      </div>

      <p v-if="request.note" class="text-xs text-muted-foreground italic">
        "{{ request.note }}"
      </p>

      <div class="flex gap-2 pt-2">
        <template v-if="canPeerRespond">
          <Button size="sm" :disabled="loading" @click="handleRespond(true)">
            {{ $t('swaps.accept') }}
          </Button>
          <Button size="sm" variant="outline" :disabled="loading" @click="handleRespond(false)">
            {{ $t('swaps.decline') }}
          </Button>
        </template>

        <template v-if="canManagerDecide">
          <Button size="sm" :disabled="loading" @click="handleDecide(true)">
            {{ $t('swaps.approve') }}
          </Button>
          <Button size="sm" variant="outline" :disabled="loading" @click="handleDecide(false)">
            {{ $t('swaps.deny') }}
          </Button>
        </template>

        <Button v-if="canCancel" size="sm" variant="destructive" :disabled="loading" @click="handleCancel">
          {{ $t('swaps.cancel') }}
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
