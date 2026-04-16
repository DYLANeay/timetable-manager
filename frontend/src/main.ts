import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import i18n from './i18n'
import { addToast } from './composables/useToast'

const app = createApp(App)

// Global error handler for Vue errors
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err, info)
  addToast('Une erreur inattendue est survenue.', 'error')
}

// Handle global errors - prevent white screen during initial load
window.addEventListener('error', (event) => {
  console.error('Global Error:', event.error)
  const splash = document.getElementById('splash')
  if (splash && !splash.classList.contains('hidden')) {
    const title = splash.querySelector('#splash-title')
    if (title) {
      title.textContent = 'Erreur de chargement. Veuillez rafraîchir la page.'
    }
  }
})

const pinia = createPinia()
app.use(pinia)
app.use(router)
app.use(i18n)

// Pre-fetch user before mounting so isAuthenticated is stable on first render.
// Without this, App.vue starts with v-else (no AppShell), then flips to v-if
// when fetchUser resolves — destroying and recreating <RouterView>, which can
// break navigation until the next hard refresh.
const { useAuthStore } = await import('./stores/auth')
const auth = useAuthStore(pinia)
if (auth.token) {
  await auth.fetchUser()
}

app.mount('#app')

// Hide splash screen when router is ready
router.isReady().then(() => {
  const splash = document.getElementById('splash')
  if (splash) {
    splash.classList.add('hidden')
    splash.addEventListener('transitionend', () => splash.remove(), { once: true })
  }
}).catch((err) => {
  console.error('Router initialization error:', err)
  addToast('Erreur de chargement. Veuillez rafraîchir la page.', 'error')
})
