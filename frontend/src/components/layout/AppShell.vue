<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { setLocale, getLocale } from '@/i18n'
import { useDarkMode } from '@/composables/useDarkMode'
import { ref } from 'vue'

const auth = useAuthStore()
const router = useRouter()
const currentLocale = ref(getLocale())
const { isDark, toggle: toggleDark } = useDarkMode()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function toggleLocale() {
  const next = currentLocale.value === 'fr' ? 'en' : 'fr'
  setLocale(next)
  currentLocale.value = next
}
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-background">
    <div class="flex h-full">
      <!-- Desktop sidebar -->
      <aside class="hidden w-56 shrink-0 border-r bg-muted/30 md:flex md:flex-col">
        <div class="flex h-14 items-center gap-2 border-b px-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <span class="text-sm font-semibold tracking-tight">{{ $t('nav.timetable') }}</span>
        </div>

        <nav class="flex-1 space-y-1 p-2">
          <RouterLink
            to="/schedule"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            active-class="!bg-accent !text-accent-foreground"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ $t('nav.schedule') }}
          </RouterLink>

          <RouterLink
            to="/swap-requests"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            active-class="!bg-accent !text-accent-foreground"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            {{ $t('nav.swaps') }}
          </RouterLink>

          <RouterLink
            to="/leaves"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            active-class="!bg-accent !text-accent-foreground"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
            {{ $t('nav.leaves') }}
          </RouterLink>

          <template v-if="auth.isManager">
            <RouterLink
              to="/employees"
              class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
              active-class="!bg-accent !text-accent-foreground"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              {{ $t('nav.employees') }}
            </RouterLink>

            <RouterLink
              to="/holidays"
              class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
              active-class="!bg-accent !text-accent-foreground"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              {{ $t('holidays.title') }}
            </RouterLink>
          </template>
        </nav>

        <div class="border-t p-2 space-y-1">
          <!-- Dark mode toggle -->
          <button
            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            @click="toggleDark"
          >
            <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            {{ isDark ? 'Mode clair' : 'Mode sombre' }}
          </button>

          <!-- Language toggle -->
          <button
            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            @click="toggleLocale"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            {{ currentLocale === 'fr' ? 'English' : 'Français' }}
          </button>

          <!-- User info -->
          <RouterLink
            to="/profile"
            class="flex items-center gap-3 rounded-md px-3 py-2 transition-colors hover:bg-accent"
            active-class="!bg-accent"
          >
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
              {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div class="flex-1 truncate">
              <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
              <p class="truncate text-xs text-muted-foreground">{{ auth.user?.role }}</p>
            </div>
          </RouterLink>

          <button
            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            @click="handleLogout"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            {{ $t('nav.logout') }}
          </button>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 overflow-auto pb-16 md:pb-0">
        <slot />
      </main>
    </div>

    <!-- Mobile bottom nav -->
    <nav class="fixed bottom-0 left-0 right-0 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden">
      <div class="mx-auto flex h-14 max-w-md items-center justify-around">
        <RouterLink
          to="/schedule"
          class="flex flex-col items-center gap-0.5 text-[11px] text-muted-foreground transition-colors"
          active-class="!text-primary"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ $t('nav.schedule') }}
        </RouterLink>

        <RouterLink
          to="/swap-requests"
          class="flex flex-col items-center gap-0.5 text-[11px] text-muted-foreground transition-colors"
          active-class="!text-primary"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          {{ $t('nav.swaps') }}
        </RouterLink>

        <RouterLink
          v-if="auth.isManager"
          to="/employees"
          class="flex flex-col items-center gap-0.5 text-[11px] text-muted-foreground transition-colors"
          active-class="!text-primary"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          {{ $t('nav.employees') }}
        </RouterLink>

        <RouterLink
          to="/leaves"
          class="flex flex-col items-center gap-0.5 text-[11px] text-muted-foreground transition-colors"
          active-class="!text-primary"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
          {{ $t('nav.leaves') }}
        </RouterLink>

        <RouterLink
          to="/profile"
          class="flex flex-col items-center gap-0.5 text-[11px] text-muted-foreground transition-colors"
          active-class="!text-primary"
        >
          <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground">
            {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          {{ $t('profile.title') }}
        </RouterLink>
      </div>
    </nav>
  </div>
</template>
