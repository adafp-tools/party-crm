<template>
  <div class="page">
    <AppHeader :title="t('members')" />
    <div class="page-content">
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input v-model="search" type="search" :placeholder="t('search_members')" @input="doSearch" />
      </div>

      <div style="display:flex;gap:8px;margin-bottom:12px">
        <select class="input" style="flex:1" v-model="filterStatus" @change="doSearch">
          <option value="">{{ t('all_statuses') }}</option>
          <option v-for="s in statuses" :key="s" :value="s">{{ t(s) }}</option>
        </select>
      </div>

      <div v-if="loading" class="empty-state"><div class="spinner" style="border-top-color:var(--accent)" /></div>

      <div v-else-if="members.length === 0" class="empty-state">
        <div class="empty-icon">👥</div>
        <div class="empty-text">{{ t('no_members') }}</div>
      </div>

      <div v-else>
        <div
          v-for="m in members"
          :key="m.id"
          class="member-item"
          @click="router.push(`/members/${m.id}`)"
        >
          <div class="member-avatar">{{ initials(m) }}</div>
          <div class="member-info">
            <div class="member-name">{{ m.first_name }} {{ m.last_name }}</div>
            <div class="member-no ltr-num">{{ m.membership_no ?? t('membership_pending') }}</div>
          </div>
          <span class="status-badge" :class="`status-${m.status}`">{{ t(m.status) }}</span>
        </div>
      </div>
    </div>

    <router-link to="/members/new" class="fab" :title="t('register_member')">＋</router-link>
    <BottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { searchMembers } from '../db/schema.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'

const { t } = useI18n()
const router = useRouter()
const members = ref([])
const search = ref('')
const filterStatus = ref('')
const loading = ref(true)
const statuses = ['applicant', 'active', 'lapsed', 'suspended', 'expelled', 'deceased', 'resigned']

onMounted(() => doSearch())

async function doSearch() {
  loading.value = true
  members.value = await searchMembers({ search: search.value, status: filterStatus.value || null })
  loading.value = false
}

function initials(m) {
  return ((m.first_name?.[0] ?? '') + (m.last_name?.[0] ?? '')).toUpperCase() || '?'
}
</script>
