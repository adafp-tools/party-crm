<template>
  <div class="page">
    <AppHeader :title="t('events')" />
    <div class="page-content">
      <div v-if="loading" class="empty-state"><div class="spinner" style="border-top-color:var(--accent)" /></div>
      <div v-else-if="events.length === 0" class="empty-state">
        <div class="empty-icon">📅</div>
        <div class="empty-text">{{ t('no_events') }}</div>
      </div>
      <div v-else>
        <div
          v-for="ev in events"
          :key="ev.id"
          class="card-sm"
          style="cursor:pointer"
          @click="router.push(`/events/${ev.id}`)"
        >
          <div style="font-weight:500;font-size:15px">{{ ev.title }}</div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px" class="ltr-num">
            {{ formatDate(ev.starts_at) }}
          </div>
          <div v-if="ev.location_text" style="font-size:12px;color:var(--text-muted)">{{ ev.location_text }}</div>
        </div>
      </div>
    </div>
    <BottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { db } from '../db/schema.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'

const { t } = useI18n()
const router = useRouter()
const events = ref([])
const loading = ref(true)

onMounted(async () => {
  events.value = await db.events.orderBy('starts_at').reverse().limit(50).toArray()
  loading.value = false
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
