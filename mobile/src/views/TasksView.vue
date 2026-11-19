<template>
  <div class="page">
    <AppHeader :title="t('tasks')" />
    <div class="page-content">
      <div v-if="loading" class="empty-state"><div class="spinner" style="border-top-color:var(--accent)" /></div>
      <div v-else-if="tasks.length === 0" class="empty-state">
        <div class="empty-icon">✅</div>
        <div class="empty-text">{{ t('no_tasks') }}</div>
      </div>
      <div v-else>
        <div v-for="task in tasks" :key="task.id" class="card-sm">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
            <div style="flex:1">
              <div style="font-weight:500;font-size:15px">{{ task.title }}</div>
              <div v-if="task.due_at" style="font-size:12px;margin-top:4px" :class="isOverdue(task) ? 'text-danger' : 'text-muted'" class="ltr-num">
                {{ isOverdue(task) ? '⚠️ ' : '' }}{{ t('due') }}: {{ formatDate(task.due_at) }}
              </div>
              <div v-if="task.details" style="font-size:13px;color:var(--text-muted);margin-top:4px">{{ task.details }}</div>
            </div>
            <select
              :value="task.status"
              class="input"
              style="width:auto;min-width:120px;min-height:36px;font-size:13px"
              @change="updateStatus(task, $event.target.value)"
            >
              <option value="pending">{{ t('pending') }}</option>
              <option value="in_progress">{{ t('in_progress') }}</option>
              <option value="completed">{{ t('completed') }}</option>
            </select>
          </div>
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
import { queueAndSync } from '../sync/index.js'
import { v4 as uuidv4 } from '../utils/uuid.js'
import { store } from '../store/index.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'

const { t } = useI18n()
const router = useRouter()
const tasks = ref([])
const loading = ref(true)

onMounted(async () => {
  const userId = store.user?.id
  tasks.value = userId
    ? await db.tasks.where('assignee_id').equals(userId).toArray()
    : await db.tasks.toArray()
  loading.value = false
})

function isOverdue(task) {
  return task.status !== 'completed' && task.due_at && new Date(task.due_at) < new Date()
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

async function updateStatus(task, status) {
  task.status = status
  await db.tasks.update(task.id, { status })
  await queueAndSync({
    client_uuid: uuidv4(),
    type: 'update_task',
    payload: { id: task.id, status },
  }, router)
}
</script>

<style scoped>
.text-danger { color: var(--danger); }
.text-muted { color: var(--text-muted); }
</style>
