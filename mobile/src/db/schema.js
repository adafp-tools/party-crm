import Dexie from 'dexie'
import { encrypt, decrypt } from './encryption.js'

export const db = new Dexie('partyCRM')

db.version(1).stores({
  // Unencrypted index fields + encrypted_data blob
  members: 'id, org_unit_id, status, category, search_index, updated_at',
  events: 'id, org_unit_id, starts_at, updated_at',
  tasks: 'id, assignee_id, status, due_at, updated_at',
  dues_plans: 'id',
  outbox: '++id, client_uuid, type, synced, created_at',
  sync_meta: 'key',
})

/**
 * Write a member record. Sensitive fields are encrypted; index fields stored plain.
 */
export async function putMember(member) {
  const { id, org_unit_id, status, category, updated_at, ...sensitive } = member
  const encData = await encrypt(sensitive)
  await db.members.put({
    id,
    org_unit_id,
    status,
    category,
    updated_at,
    search_index: `${member.first_name ?? ''} ${member.last_name ?? ''}`.toLowerCase().trim(),
    encrypted_data: encData,
  })
}

export async function getMember(id) {
  const row = await db.members.get(id)
  if (!row) return null
  const sensitive = await decrypt(row.encrypted_data)
  return { id: row.id, org_unit_id: row.org_unit_id, status: row.status, category: row.category, updated_at: row.updated_at, ...sensitive }
}

export async function searchMembers({ search = '', status = null, category = null } = {}) {
  let query = db.members.toCollection()
  if (status) query = db.members.where('status').equals(status)
  const rows = await query.toArray()

  const filtered = rows.filter(row => {
    if (search && !row.search_index.includes(search.toLowerCase())) return false
    if (category && row.category !== category) return false
    return true
  })

  return Promise.all(filtered.map(async row => {
    try {
      const sensitive = await decrypt(row.encrypted_data)
      return { id: row.id, org_unit_id: row.org_unit_id, status: row.status, category: row.category, updated_at: row.updated_at, ...sensitive }
    } catch {
      // If decryption fails (key changed), return minimal info
      return { id: row.id, org_unit_id: row.org_unit_id, status: row.status, category: row.category, first_name: '???', last_name: '???' }
    }
  }))
}

export async function putEvent(event) {
  await db.events.put(event)
}

export async function putTask(task) {
  await db.tasks.put(task)
}

export async function addToOutbox(operation) {
  return db.outbox.add({
    client_uuid: operation.client_uuid,
    type: operation.type,
    payload: operation.payload,
    client_created_at: new Date().toISOString(),
    synced: 0,
    created_at: new Date().toISOString(),
    retries: 0,
  })
}

export async function getPendingOutbox() {
  return db.outbox.where('synced').equals(0).toArray()
}

export async function markOutboxSynced(id) {
  return db.outbox.update(id, { synced: 1 })
}

export async function getSyncMeta(key) {
  const row = await db.sync_meta.get(key)
  return row?.value ?? null
}

export async function setSyncMeta(key, value) {
  return db.sync_meta.put({ key, value })
}
