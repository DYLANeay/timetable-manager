<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchSwapRequests, type SwapRequestData } from '@/api/swaps'
import { Button } from '@/components/ui/button'
import SwapRequestCard from '@/components/swaps/SwapRequestCard.vue'
import CreateGiveawayDialog from '@/components/swaps/CreateGiveawayDialog.vue'

const { t } = useI18n()

const requests = ref<SwapRequestData[]>([])
const loading = ref(true)
const activeTab = ref<'swaps' | 'giveaways'>('swaps')
const giveawayDialogOpen = ref(false)

const filteredRequests = computed(() =>
  requests.value.filter((r) =>
    activeTab.value === 'swaps' ? r.type === 'swap' : r.type === 'giveaway',
  ),
)

const emptyMessage = computed(() =>
  activeTab.value === 'swaps' ? t('swaps.noRequests') : t('swaps.noGiveaways'),
)

async function load() {
  loading.value = true
  try {
    const response = await fetchSwapRequests()
    requests.value = response.data
  } finally {
    loading.value = false
  }
}

function handleGiveawayCreated() {
  activeTab.value = 'giveaways'
  load()
}

onMounted(load)
</script>

<template>
  <div class="space-y-4 p-4 md:p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">{{ t('swaps.title') }}</h1>
      <Button v-if="activeTab === 'giveaways'" size="sm" @click="giveawayDialogOpen = true">
        {{ t('swaps.giveAwayShift') }}
      </Button>
    </div>

    <div class="flex gap-1 rounded-lg bg-muted p-1">
      <button
        :class="[
          'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
          activeTab === 'swaps'
            ? 'bg-background text-foreground shadow-sm'
            : 'text-muted-foreground hover:text-foreground',
        ]"
        @click="activeTab = 'swaps'"
      >
        {{ t('swaps.tabSwaps') }}
      </button>
      <button
        :class="[
          'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
          activeTab === 'giveaways'
            ? 'bg-background text-foreground shadow-sm'
            : 'text-muted-foreground hover:text-foreground',
        ]"
        @click="activeTab = 'giveaways'"
      >
        {{ t('swaps.tabGiveaways') }}
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <span class="text-sm text-muted-foreground">{{ t('common.loading') }}</span>
    </div>

    <div v-else-if="filteredRequests.length === 0" class="py-8 text-center text-sm text-muted-foreground">
      {{ emptyMessage }}
    </div>

    <div v-else class="space-y-3">
      <SwapRequestCard
        v-for="request in filteredRequests"
        :key="request.id"
        :request="request"
        @updated="load"
      />
    </div>

    <CreateGiveawayDialog
      :open="giveawayDialogOpen"
      @update:open="giveawayDialogOpen = $event"
      @created="handleGiveawayCreated"
    />
  </div>
</template>
