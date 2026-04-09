<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl font-bold">Weekly Schedule</h1>
    <p class="mt-2 text-muted-foreground">
      Welcome, {{ auth.user?.name }}. The timetable will be displayed here.
    </p>
  </div>
</template>
