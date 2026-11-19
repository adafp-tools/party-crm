<template>
  <div class="page">
    <AppHeader :title="memberName" :show-back="true">
      <template #right>
        <router-link :to="`/members/${id}/dues`" class="btn-icon" :title="t('record_payment')">💳</router-link>
      </template>
    </AppHeader>
    <div class="page-content">
      <div v-if="loading" class="empty-state"><div class="spinner" style="border-top-color:var(--accent)" /></div>

      <template v-else-if="member">
        <div class="card" style="text-align:center;margin-bottom:16px">
          <div class="member-avatar" style="width:72px;height:72px;font-size:28px;margin:0 auto 12px">{{ initials }}</div>
          <div style="font-size:20px;font-weight:600">{{ memberName }}</div>
          <div class="ltr-num" style="font-size:14px;color:var(--text-muted);margin-top:4px">
            {{ member.membership_no ?? t('membership_pending') }}
          </div>
          <span class="status-badge" :class="`status-${member.status}`" style="margin-top:8px;display:inline-block">
            {{ t(member.status) }}
          </span>
        </div>

        <div class="card">
          <div class="detail-row">
            <span class="detail-label">{{ t('category') }}</span>
            <span class="detail-value">{{ t(member.category) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">{{ t('gender') }}</span>
            <span class="detail-value">{{ t(member.gender ?? 'undisclosed') }}</span>
          </div>
          <div v-if="member.date_of_birth" class="detail-row">
            <span class="detail-label">{{ t('date_of_birth') }}</span>
            <span class="detail-value ltr-num">{{ member.date_of_birth }}</span>
          </div>
          <div v-if="member.phone" class="detail-row">
            <span class="detail-label">{{ t('phone') }}</span>
            <a :href="`tel:${member.phone}`" class="detail-value ltr-num" style="color:var(--accent)">
              {{ member.phone }}
            </a>
          </div>
          <div v-if="member.email" class="detail-row">
            <span class="detail-label">{{ t('email') }}</span>
            <span class="detail-value" style="word-break:break-all">{{ member.email }}</span>
          </div>
        </div>

        <div style="font-size:11px;color:var(--text-muted);text-align:center;padding:8px">
          {{ t('phone_only') }}
        </div>
      </template>

      <div v-else class="empty-state">
        <div class="empty-icon">❓</div>
      </div>
    </div>
    <BottomNav />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getMember } from '../db/schema.js'
import AppHeader from '../components/AppHeader.vue'
import BottomNav from '../components/BottomNav.vue'

const { t } = useI18n()
const route = useRoute()
const id = route.params.id
const member = ref(null)
const loading = ref(true)

onMounted(async () => {
  member.value = await getMember(id)
  loading.value = false
})

const memberName = computed(() => member.value ? `${member.value.first_name} ${member.value.last_name}` : '…')
const initials = computed(() => {
  if (!member.value) return '?'
  return ((member.value.first_name?.[0] ?? '') + (member.value.last_name?.[0] ?? '')).toUpperCase()
})
</script>
