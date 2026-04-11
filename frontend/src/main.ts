import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import i18n from './i18n'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

app.mount('#app')

router.isReady().then(() => {
  const splash = document.getElementById('splash')
  if (splash) {
    splash.classList.add('hidden')
    splash.addEventListener('transitionend', () => splash.remove(), { once: true })
  }
})
