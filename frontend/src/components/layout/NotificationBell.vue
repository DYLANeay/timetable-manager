<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications'
import type { AppNotification } from '@/types'

const { t, locale } = useI18n()
const router = useRouter()
const store = useNotificationsStore()

const isOpen = ref(false)
const bellRef = ref<HTMLElement | null>(null)
const panelRef = ref<HTMLElement | null>(null)
const panelStyle = ref<Record<string, string>>({})

function toggle() {
  if (!isOpen.value) {
    isOpen.value = true
    nextTick(() => updatePanelPosition())
  } else {
    isOpen.value = false
  }
}

function updatePanelPosition() {
  if (!bellRef.value) return
  const rect = bellRef.value.getBoundingClientRect()
  const isMobile = window.innerWidth < 768
  const panelWidth = isMobile ? Math.min(window.innerWidth - 16, 360) : 320

  const style: Record<string, string> = {
    position: 'fixed',
    top: `${rect.bottom + 8}px`,
    width: `${panelWidth}px`,
    zIndex: '9998',
  }

  // Bell on the left half (desktop sidebar) → anchor panel's left edge to the bell
  // Bell on the right half (mobile header) → anchor panel's right edge to the bell
  if (rect.left + rect.width / 2 < window.innerWidth / 2) {
    style.left = `${Math.max(8, rect.left)}px`
  } else {
    style.right = `${Math.max(8, window.innerWidth - rect.right)}px`
  }

  panelStyle.value = style
}

function handleOutsideClick(event: MouseEvent) {
  const target = event.target as Node
  const clickedBell = bellRef.value?.contains(target)
  const clickedPanel = panelRef.value?.contains(target)
  if (!clickedBell && !clickedPanel) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  window.addEventListener('resize', updatePanelPosition)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
  window.removeEventListener('resize', updatePanelPosition)
})

// ── Message rendering ──────────────────────────────────────────────────────

function formatDate(dateStr: string): string {
  const loc = locale.value === 'fr' ? 'fr-CH' : 'en-US'
  return new Intl.DateTimeFormat(loc, { weekday: 'short', day: 'numeric', month: 'short' }).format(
    new Date(dateStr + 'T12:00:00'),
  )
}

function shiftLabel(type: string): string {
  return t(`schedule.${type}`)
}

function getMessage(n: AppNotification): string {
  const d = n.data
  const name = d.requester_name ?? ''
  switch (n.type) {
    case 'swap_request':
      return d.swap_type === 'giveaway'
        ? t('notifications.types.giveaway_request', { name })
        : t('notifications.types.swap_request', { name })
    case 'leave_request':
      return t('notifications.types.leave_request', { name })
    case 'holiday_request':
      return t('notifications.types.holiday_request', { name })
    case 'swap_targeting_you':
      return t('notifications.types.swap_targeting_you', { name })
    case 'planning_updated':
      return d.action === 'created'
        ? t('notifications.types.planning_created', {
            shift: shiftLabel(d.shift_type ?? ''),
            date: formatDate(d.date ?? ''),
          })
        : t('notifications.types.planning_deleted', {
            shift: shiftLabel(d.shift_type ?? ''),
            date: formatDate(d.date ?? ''),
          })
    case 'swap_decided':
      if (d.swap_type === 'giveaway') {
        return d.decision === 'approved'
          ? t('notifications.types.giveaway_approved', { date: formatDate(d.shift_date ?? '') })
          : t('notifications.types.giveaway_denied', { date: formatDate(d.shift_date ?? '') })
      }
      return d.decision === 'approved'
        ? t('notifications.types.swap_approved', { date: formatDate(d.shift_date ?? '') })
        : t('notifications.types.swap_denied', { date: formatDate(d.shift_date ?? '') })
    case 'leave_decided':
      return d.decision === 'approved'
        ? t('notifications.types.leave_approved', {
            start: formatDate(d.start_date ?? ''),
            end: formatDate(d.end_date ?? ''),
          })
        : t('notifications.types.leave_denied')
    default:
      return n.type
  }
}

function getRoute(n: AppNotification): string {
  switch (n.type) {
    case 'swap_request':
    case 'swap_targeting_you':
    case 'swap_decided':
      return '/swap-requests'
    case 'leave_request':
    case 'holiday_request':
    case 'leave_decided':
      return '/leaves'
    case 'planning_updated':
      return '/schedule'
    default:
      return '/schedule'
  }
}

function relativeTime(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const min = Math.floor(diff / 60_000)
  const h = Math.floor(min / 60)
  const d = Math.floor(h / 24)
  const isFr = locale.value === 'fr'

  if (min < 1) return isFr ? "à l'instant" : 'just now'
  if (min < 60) return isFr ? `il y a ${min} min` : `${min}m ago`
  if (h < 24) return isFr ? `il y a ${h}h` : `${h}h ago`
  if (d === 1) return isFr ? 'hier' : 'yesterday'
  return new Intl.DateTimeFormat(isFr ? 'fr-CH' : 'en-US', { day: 'numeric', month: 'short' }).format(
    new Date(dateStr),
  )
}

async function handleClick(n: AppNotification) {
  isOpen.value = false
  if (!n.read_at) {
    await store.markRead(n.id)
  }
  router.push(getRoute(n))
}

async function handleMarkAllRead() {
  await store.markAllRead()
}
</script>

<template>
  <div ref="bellRef" class="relative">
    <!-- Bell button -->
    <button
      class="relative flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
      :aria-label="t('notifications.title')"
      @click.stop="toggle"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>

      <!-- Unread badge -->
      <span
        v-if="store.unreadCount > 0"
        class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-destructive px-0.5 text-[10px] font-semibold leading-none text-white"
      >
        {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
      </span>
    </button>

    <!-- Panel — teleported to body to avoid overflow clipping -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="panelRef"
        :style="panelStyle"
        class="overflow-hidden rounded-lg border bg-popover text-popover-foreground shadow-lg"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b px-4 py-3">
          <span class="text-sm font-semibold">{{ t('notifications.title') }}</span>
          <button
            v-if="store.unreadCount > 0"
            class="text-xs text-muted-foreground transition-colors hover:text-foreground"
            @click.stop="handleMarkAllRead"
          >
            {{ t('notifications.markAllRead') }}
          </button>
        </div>

        <!-- List -->
        <div class="max-h-[420px] overflow-y-auto overscroll-contain">
          <!-- Empty state -->
          <div
            v-if="store.notifications.length === 0"
            class="flex flex-col items-center gap-3 py-10 text-muted-foreground"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-8 w-8 opacity-40"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
              <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="text-sm">{{ t('notifications.noNotifications') }}</span>
          </div>

          <!-- Notification items -->
          <button
            v-for="n in store.notifications"
            :key="n.id"
            class="flex w-full items-start gap-3 border-b px-4 py-3 text-left transition-colors last:border-0 hover:bg-accent"
            :class="{ 'bg-muted/40': !n.read_at }"
            @click.stop="handleClick(n)"
          >
            <!-- Type icon -->
            <div
              class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
              :class="
                n.type === 'planning_updated'
                  ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                  : n.type === 'leave_request' || n.type === 'holiday_request' || n.type === 'leave_decided'
                    ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                    : 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400'
              "
            >
              <!-- Calendar icon (planning) -->
              <svg
                v-if="n.type === 'planning_updated'"
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
              <!-- Cloud icon (leave / holiday) -->
              <svg
                v-else-if="
                  n.type === 'leave_request' || n.type === 'holiday_request' || n.type === 'leave_decided'
                "
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" />
              </svg>
              <!-- Swap icon (swaps) -->
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="17 1 21 5 17 9" />
                <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                <polyline points="7 23 3 19 7 15" />
                <path d="M21 13v2a4 4 0 0 1-4 4H3" />
              </svg>
            </div>

            <!-- Text -->
            <div class="min-w-0 flex-1">
              <p class="text-sm leading-snug" :class="{ 'font-medium': !n.read_at }">
                {{ getMessage(n) }}
              </p>
              <p class="mt-0.5 text-xs text-muted-foreground">{{ relativeTime(n.created_at) }}</p>
            </div>

            <!-- Unread dot -->
            <div v-if="!n.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary" />
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
