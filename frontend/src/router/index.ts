import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/LoginPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/pages/ForgotPasswordPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/pages/ResetPasswordPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/schedule',
      name: 'schedule',
      component: () => import('@/pages/SchedulePage.vue'),
      meta: { auth: true },
    },
    {
      path: '/swap-requests',
      name: 'swap-requests',
      component: () => import('@/pages/SwapRequestsPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/leaves',
      name: 'leaves',
      component: () => import('@/pages/LeavesPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/employees',
      name: 'employees',
      component: () => import('@/pages/EmployeesPage.vue'),
      meta: { auth: true, manager: true },
    },
    {
      path: '/holidays',
      name: 'holidays',
      component: () => import('@/pages/HolidaysPage.vue'),
      meta: { auth: true, manager: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/pages/ProfilePage.vue'),
      meta: { auth: true },
    },
    {
      path: '/',
      redirect: '/schedule',
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  if (to.meta.auth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'schedule' }
  }

  if (to.meta.manager && !auth.isManager) {
    return { name: 'schedule' }
  }
})

export default router
