<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { fetchSwapRequests, type SwapRequestData } from '@/api/swaps'
import SwapRequestCard from '@/components/swaps/SwapRequestCard.vue'

const requests = ref<SwapRequestData[]>([])
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const response = await fetchSwapRequests()
    requests.value = response.data
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-4 p-4">
    <h1 class="text-xl font-bold">Swap Requests</h1>

    <div v-if="loading" class="flex justify-center py-8">
      <span class="text-sm text-muted-foreground">Loading...</span>
    </div>

    <div v-else-if="requests.length === 0" class="py-8 text-center text-sm text-muted-foreground">
      No swap requests yet.
    </div>

    <div v-else class="space-y-3">
      <SwapRequestCard
        v-for="request in requests"
        :key="request.id"
        :request="request"
        @updated="load"
      />
    </div>
  </div>
</template>
