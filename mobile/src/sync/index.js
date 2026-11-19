import { syncPull, syncPush, heartbeat } from '../api/client.js'
import { putMember, putEvent, putTask, getPendingOutbox, markOutboxSynced, getSyncMeta, setSyncMeta } from '../db/schema.js'
import { setSyncing, setSyncError, setLastSync, setOutboxCount, _state } from '../store/index.js'
import { wipeLocalData } from '../db/encryption.js'

let syncInProgress = false

export async function runSync(router) {
  if (syncInProgress) return
  syncInProgress = true
  setSyncing(true)
  setSyncError(null)

  try {
    // Heartbeat: check for remote wipe
    const hb = await heartbeat()
    if (hb.wipe) {
      await wipeLocalData()
      _state.isAuthenticated = false
      _state.isUnlocked = false
      router?.push('/login')
      return
    }

    // Push outbox items
    const pending = await getPendingOutbox()
    if (pending.length > 0) {
      const batches = chunk(pending, 100)
      for (const batch of batches) {
        const ops = batch.map(item => ({
          client_uuid: item.client_uuid,
          type: item.type,
          payload: item.payload,
          client_created_at: item.client_created_at,
        }))
        const result = await syncPush(ops)
        for (const res of result.results) {
          if (res.status === 'accepted') {
            const item = batch.find(b => b.client_uuid === res.client_uuid)
            if (item) await markOutboxSynced(item.id)
          }
        }
      }
    }

    // Pull changes
    const since = await getSyncMeta('cursor')
    const deviceName = localStorage.getItem('crm_device_name') ?? navigator.userAgent.slice(0, 100)
    const platform = /android/i.test(navigator.userAgent) ? 'android' : /iphone|ipad/i.test(navigator.userAgent) ? 'ios' : 'web'
    const pullResult = await syncPull(since, deviceName, platform)

    for (const member of pullResult.data ?? []) {
      await putMember(member)
    }
    for (const event of pullResult.events ?? []) {
      await putEvent(event)
    }
    for (const task of pullResult.tasks ?? []) {
      await putTask(task)
    }

    if (pullResult.meta?.server_time) {
      await setSyncMeta('cursor', pullResult.meta.server_time)
    }

    setLastSync(new Date())
    const remaining = await getPendingOutbox()
    setOutboxCount(remaining.length)
  } catch (err) {
    setSyncError(err.message ?? 'Sync failed')
  } finally {
    setSyncing(false)
    syncInProgress = false
  }
}

export async function queueAndSync(operation, router) {
  const { addToOutbox } = await import('../db/schema.js')
  await addToOutbox(operation)
  const pending = await getPendingOutbox()
  setOutboxCount(pending.length)
  if (navigator.onLine) {
    runSync(router)
  }
}

function chunk(arr, size) {
  const chunks = []
  for (let i = 0; i < arr.length; i += size) chunks.push(arr.slice(i, i + size))
  return chunks
}

// Background sync via Service Worker BackgroundSync API
export function registerBackgroundSync() {
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    navigator.serviceWorker.ready.then(reg => {
      reg.sync.register('outbox-sync').catch(() => {})
    })
  }
}

// Listen for online events to trigger sync
export function setupSyncListeners(router) {
  window.addEventListener('online', () => runSync(router))
  // Initial sync after unlock
}
