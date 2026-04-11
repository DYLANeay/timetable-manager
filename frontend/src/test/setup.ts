import { config } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import en from '@/i18n/en'
import fr from '@/i18n/fr'

// Mount a minimal i18n instance for all tests
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  messages: { en, fr },
})

config.global.plugins = [i18n]
