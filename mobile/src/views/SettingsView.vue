<template>
  <div class="page">
    <AppHeader :title="t('settings')" />
    <div class="page-content">
      <div class="card">
        <div style="font-weight:600;margin-bottom:12px">{{ t('language') }}</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <label
            v-for="loc in availableLocales"
            :key="loc.code"
            class="checkbox-row"
            style="cursor:pointer"
          >
            <input type="radio" :value="loc.code" v-model="selectedLocale" @change="changeLocale(loc.code)" />
            <span>{{ loc.native }} ({{ loc.code.toUpperCase() }})</span>
          </label>
        </div>
      </div>

      <div class="card" style="margin-top:4px">
        <div style="font-weight:600;margin-bottom:4px">{{ store.user?.name }}</div>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">{{ store.user?.email }}</div>
        <button class="btn btn-secondary btn-full" @click="signOut">{{ t('logout') }}</button>
      </div>

      <div class="card" style="margin-top:4px">
        <div style="font-weight:600;color:var(--danger);margin-bottom:8px">⚠️ {{ t('clear_data') }}</div>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px">{{ t('clear_data_confirm') }}</div>
        <button class="btn btn-danger btn-full" @click="clearData">{{ t('clear_data_btn') }}</button>
      </div>
    </div>
    <BottomNav />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { store, _state, setLocale } from '../store/index.js'
import { wipeLocalData } from '../db/encryption.js'
import { logout, clearToken } from '../api/client.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'

const { t, locale } = useI18n()
const router = useRouter()
const selectedLocale = ref(store.locale)

// Supported locales with native names
const allLocales = [
  { code: 'en', native: 'English' },
  { code: 'fr', native: 'Français' },
  { code: 'pt', native: 'Português' },
  { code: 'ar', native: 'العربية' },
]

// Only show locales the party has enabled, or all 4 if not set
const availableLocales = computed(() => {
  const enabled = store.user?.settings?.locales ?? ['en', 'fr', 'pt', 'ar']
  return allLocales.filter(l => enabled.includes(l.code))
})

function changeLocale(code) {
  locale.value = code
  setLocale(code)
}

async function signOut() {
  try { await logout() } catch {}
  clearToken()
  _state.isAuthenticated = false
  _state.isUnlocked = false
  router.push('/login')
}

async function clearData() {
  if (!confirm(t('clear_data_confirm'))) return
  await wipeLocalData()
  _state.isAuthenticated = false
  _state.isUnlocked = false
  router.push('/login')
}
</script>
