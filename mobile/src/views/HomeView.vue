<template>
  <div class="page">
    <AppHeader :title="store.partyName || t('app_name')" />
    <div class="page-content">
      <div class="card" style="margin-bottom:16px">
        <SyncStatus />
      </div>

      <div class="card">
        <div style="font-weight:600;margin-bottom:12px">{{ store.user?.name }}</div>
        <div style="font-size:13px;color:var(--text-muted)">{{ rolesDisplay }}</div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px">
        <router-link to="/members/new" class="card-sm" style="text-decoration:none;text-align:center;cursor:pointer">
          <div style="font-size:28px;margin-bottom:4px">➕</div>
          <div style="font-size:13px;font-weight:500">{{ t('register_member') }}</div>
        </router-link>
        <router-link to="/members" class="card-sm" style="text-decoration:none;text-align:center;cursor:pointer">
          <div style="font-size:28px;margin-bottom:4px">👥</div>
          <div style="font-size:13px;font-weight:500">{{ t('members') }}</div>
        </router-link>
        <router-link to="/events" class="card-sm" style="text-decoration:none;text-align:center;cursor:pointer">
          <div style="font-size:28px;margin-bottom:4px">📅</div>
          <div style="font-size:13px;font-weight:500">{{ t('events') }}</div>
        </router-link>
        <router-link to="/tasks" class="card-sm" style="text-decoration:none;text-align:center;cursor:pointer">
          <div style="font-size:28px;margin-bottom:4px">✅</div>
          <div style="font-size:13px;font-weight:500">{{ t('tasks') }}</div>
        </router-link>
      </div>
    </div>
    <BottomNav />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { store } from '../store/index.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'
import SyncStatus from '../components/SyncStatus.vue'

const { t } = useI18n()
const rolesDisplay = computed(() => store.user?.roles?.join(', ') ?? '')
</script>
