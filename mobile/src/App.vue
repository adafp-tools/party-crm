<template>
  <div :class="['app-root', { rtl: isRtl }]" :dir="dir">
    <div v-if="isDemoMode" class="demo-banner">{{ t('demo_banner') }}</div>
    <div v-if="!isOnline" class="offline-badge">{{ t('offline_badge') }}</div>
    <router-view />
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { store } from './store/index.js'
import { applyLocaleToDocument, RTL_LOCALES } from './i18n/index.js'
import { useOnline } from './composables/useOnline.js'

const { t, locale } = useI18n()
const isOnline = useOnline()

const isDemoMode = computed(() => store.isDemo)
const isRtl = computed(() => RTL_LOCALES.includes(store.locale))
const dir = computed(() => isRtl.value ? 'rtl' : 'ltr')

watch(() => store.locale, (newLocale) => {
  locale.value = newLocale
  applyLocaleToDocument(newLocale)
}, { immediate: true })
</script>
