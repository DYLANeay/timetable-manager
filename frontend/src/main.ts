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
  addToast('Une erreur inattendue est survenue. La page va se recharger.', 'error')
  // Auto-reload after 3 seconds for critical errors
  setTimeout(() => {
    window.location.reload()
  }, 3000)
}

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled Promise Rejection:', event.reason)
  addToast('Une erreur réseau est survenue. Veuillez vérifier votre connexion.', 'error')
})

// Handle global errors
window.addEventListener('error', (event) => {
  console.error('Global Error:', event.error)
  // Prevent white screen - show splash if app fails to mount
  const splash = document.getElementById('splash')
  if (splash && !splash.classList.contains('hidden')) {
    const title = splash.querySelector('#splash-title')
    if (title) {
      title.textContent = 'Erreur de chargement. Rafraîchissement...'
    }
    setTimeout(() => window.location.reload(), 3000)
  }
})

app.use(createPinia())
app.use(router)
app.use(i18n)

// Mount the app
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
