import { createI18n } from 'vue-i18n'
import fr from './fr'
import en from './en'

const savedLocale = localStorage.getItem('locale') ?? 'fr'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'fr',
  messages: { fr, en },
})

export function setLocale(locale: 'fr' | 'en') {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  document.documentElement.lang = locale
}

export function getLocale(): string {
  return i18n.global.locale.value
}

export default i18n
