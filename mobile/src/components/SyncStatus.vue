<template>
  <div class="sync-status">
    <div class="sync-dot" :class="dotClass" />
    <span>{{ label }}</span>
    <button v-if="!store.isSyncing" class="btn btn-secondary" style="font-size:12px;min-height:32px;padding:0 10px" @click="sync">
      {{ t('sync_now') }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { store } from '../store/index.js'
import { runSync } from '../sync/index.js'

const { t } = useI18n()
const router = useRouter()

const dotClass = computed(() => {
  if (store.isSyncing) return 'syncing'
  if (store.syncError) return 'error'
  if (store.outboxCount > 0) return 'pending'
  return 'ok'
})

const label = computed(() => {
  if (store.isSyncing) return t('sync_status_syncing')
  if (store.syncError) return t('sync_status_error')
  if (store.outboxCount > 0) return t('sync_status_pending', { count: store.outboxCount }, store.outboxCount)
  if (store.lastSyncAt) return t('last_synced', { time: formatTime(store.lastSyncAt) })
  return t('sync_status_ok')
})

function formatTime(d) {
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function sync() { runSync(router) }
</script>
