import { reactive, readonly } from 'vue'
import { isUnlocked, lock, hasPinSet, wipeLocalData } from '../db/encryption.js'
import { hasToken } from '../api/client.js'

const IDLE_TIMEOUT_MS = 5 * 60 * 1000 // 5 minutes
const STALE_SYNC_DAYS = 30

const state = reactive({
  user: null,
  isAuthenticated: false,
  isUnlocked: false,
  hasPinSet: false,
  isSyncing: false,
  lastSyncAt: null,
  outboxCount: 0,
  syncError: null,
  isDemo: false,
  partyName: '',
  currency: 'USD',
  locale: localStorage.getItem('crm_locale') || 'en',
  duesPlans: [],
})

let idleTimer = null

function resetIdleTimer() {
  clearTimeout(idleTimer)
  if (state.isUnlocked) {
    idleTimer = setTimeout(() => {
      lockApp()
    }, IDLE_TIMEOUT_MS)
  }
}

export function setupActivityListeners() {
  const events = ['touchstart', 'click', 'keydown', 'scroll']
  events.forEach(e => document.addEventListener(e, resetIdleTimer, { passive: true }))
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
      clearTimeout(idleTimer)
    } else if (state.isUnlocked) {
      resetIdleTimer()
    }
  })
}

export function lockApp() {
  lock()
  state.isUnlocked = false
  clearTimeout(idleTimer)
}

export function setUnlocked(user) {
  state.isUnlocked = true
  if (user) setUser(user)
  resetIdleTimer()
}

export function setUser(user) {
  state.user = user
  state.isAuthenticated = true
  state.isDemo = user.settings?.is_demo ?? false
  state.partyName = user.settings?.party_name ?? ''
  state.currency = user.settings?.currency ?? 'USD'
  state.duesPlans = user.dues_plans ?? []
  state.hasPinSet = hasPinSet()
}

export function setSyncing(val) {
  state.isSyncing = val
}

export function setSyncError(err) {
  state.syncError = err
}

export function setLastSync(date) {
  state.lastSyncAt = date
  if (date) {
    localStorage.setItem('crm_last_sync', date.toISOString())
    checkStaleness()
  }
}

export function setOutboxCount(count) {
  state.outboxCount = count
}

export function setLocale(locale) {
  state.locale = locale
  localStorage.setItem('crm_locale', locale)
}

export async function checkStaleness() {
  const last = localStorage.getItem('crm_last_sync')
  if (!last) return
  const diff = Date.now() - new Date(last).getTime()
  if (diff > STALE_SYNC_DAYS * 86400 * 1000) {
    await wipeLocalData()
    state.isAuthenticated = false
    state.isUnlocked = false
    return true
  }
  return false
}

export function initState() {
  state.isAuthenticated = hasToken()
  state.isUnlocked = isUnlocked()
  state.hasPinSet = hasPinSet()
  const lastSync = localStorage.getItem('crm_last_sync')
  if (lastSync) state.lastSyncAt = new Date(lastSync)
  const savedLocale = localStorage.getItem('crm_locale')
  if (savedLocale) state.locale = savedLocale
}

export const store = readonly(state)
export { state as _state }
