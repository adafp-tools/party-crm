import { createI18n } from 'vue-i18n'
import en from '../locales/en.json'
import fr from '../locales/fr.json'
import pt from '../locales/pt.json'
import ar from '../locales/ar.json'

export const RTL_LOCALES = ['ar']

export function getLocaleDir(locale) {
  return RTL_LOCALES.includes(locale) ? 'rtl' : 'ltr'
}

export function applyLocaleToDocument(locale) {
  document.documentElement.lang = locale
  document.documentElement.dir = getLocaleDir(locale)
}

export const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('crm_locale') || 'en',
  fallbackLocale: 'en',
  messages: { en, fr, pt, ar },
})

export default i18n
