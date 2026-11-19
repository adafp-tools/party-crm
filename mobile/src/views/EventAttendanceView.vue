<template>
  <div class="page">
    <AppHeader :title="event?.title ?? t('event_attendance')" :show-back="true" />
    <div class="page-content">
      <div class="card" style="display:flex;gap:12px;margin-bottom:16px">
        <button class="btn btn-secondary" :class="{ 'btn-primary': mode === 'list' }" @click="mode = 'list'">
          👥 {{ t('members') }}
        </button>
        <button class="btn btn-secondary" :class="{ 'btn-primary': mode === 'scan' }" @click="mode = 'scan'">
          📷 {{ t('scan_qr') }}
        </button>
      </div>

      <!-- QR Scanner mode -->
      <div v-if="mode === 'scan'" class="card">
        <QrScanner @decoded="onQrDecoded" @error="qrError = $event" />
        <div v-if="qrResult" class="card" style="margin-top:12px;text-align:center">
          <div style="font-size:28px">{{ qrResult.icon }}</div>
          <div style="font-weight:500;margin-top:8px">{{ qrResult.name }}</div>
          <div style="font-size:13px;color:var(--text-muted)">{{ qrResult.message }}</div>
        </div>
        <div v-if="qrError" class="error-msg" style="margin-top:8px">{{ qrError.message }}</div>
      </div>

      <!-- List mode -->
      <div v-if="mode === 'list'">
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input v-model="search" type="search" @input="filterMembers" />
        </div>
        <div v-for="m in filteredMembers" :key="m.id" class="member-item">
          <div class="member-avatar" :style="attended.has(m.id) ? 'background:var(--success)' : ''">
            {{ initials(m) }}
          </div>
          <div class="member-info">
            <div class="member-name">{{ m.first_name }} {{ m.last_name }}</div>
            <div class="member-no ltr-num">{{ m.membership_no ?? t('membership_pending') }}</div>
          </div>
          <button
            class="btn"
            :class="attended.has(m.id) ? 'btn-secondary' : 'btn-primary'"
            style="min-height:36px;padding:0 12px;font-size:13px"
            @click="toggleAttendance(m)"
          >
            {{ attended.has(m.id) ? '✓' : t('mark_attended') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { db, searchMembers } from '../db/schema.js'
import { queueAndSync } from '../sync/index.js'
import { v4 as uuidv4 } from '../utils/uuid.js'
import { useRouter } from 'vue-router'
import QrScanner from '../components/QrScanner.vue'
import AppHeader from '../components/AppHeader.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const eventId = route.params.id
const event = ref(null)
const mode = ref('list')
const members = ref([])
const filteredMembers = ref([])
const attended = ref(new Set())
const search = ref('')
const qrResult = ref(null)
const qrError = ref(null)

onMounted(async () => {
  event.value = await db.events.get(eventId)
  members.value = await searchMembers()
  filteredMembers.value = members.value
})

function filterMembers() {
  const q = search.value.toLowerCase()
  filteredMembers.value = q ? members.value.filter(m =>
    `${m.first_name} ${m.last_name}`.toLowerCase().includes(q)
  ) : members.value
}

async function toggleAttendance(member) {
  if (attended.value.has(member.id)) return
  attended.value.add(member.id)

  await queueAndSync({
    client_uuid: uuidv4(),
    type: 'event_attendance',
    payload: { event_id: eventId, member_id: member.id },
  }, router)
}

async function onQrDecoded(url) {
  qrResult.value = null
  // Parse member ID from signed verify URL /{locale}/verify/{membership_no}
  const match = url.match(/\/verify\/([^/?]+)/)
  if (!match) {
    qrResult.value = { icon: '❌', name: '', message: 'Invalid QR code' }
    return
  }
  const membershipNo = match[1]
  const found = await db.members.where('search_index').startsWithIgnoreCase('').filter(
    m => m.membership_no === membershipNo
  ).first().catch(() => null)

  if (found) {
    if (!attended.value.has(found.id)) {
      attended.value.add(found.id)
      await queueAndSync({
        client_uuid: uuidv4(),
        type: 'event_attendance',
        payload: { event_id: eventId, member_id: found.id },
      }, router)
    }
    qrResult.value = { icon: '✅', name: `${found.first_name} ${found.last_name}`, message: t('attended') }
  } else {
    qrResult.value = { icon: '❌', name: membershipNo, message: 'Not found' }
  }

  // Auto-reset to scan again after 3 seconds
  setTimeout(() => { qrResult.value = null; mode.value = 'scan' }, 3000)
}

function initials(m) {
  return ((m.first_name?.[0] ?? '') + (m.last_name?.[0] ?? '')).toUpperCase() || '?'
}
</script>
