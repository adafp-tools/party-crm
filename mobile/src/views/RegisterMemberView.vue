<template>
  <div class="page">
    <AppHeader :title="t('register_member')" :show-back="true" />
    <div class="page-content">
      <div v-if="success" class="card" style="text-align:center;padding:32px">
        <div style="font-size:48px">✅</div>
        <div style="font-size:18px;font-weight:600;margin:12px 0">{{ t('done') }}</div>
        <div style="color:var(--text-muted);font-size:13px">{{ t('membership_pending') }}</div>
        <button class="btn btn-primary" style="margin-top:20px" @click="router.back()">{{ t('back') }}</button>
      </div>

      <template v-else>
        <div v-if="error" class="error-msg">{{ error }}</div>

        <div class="card">
          <div class="field">
            <label class="label">{{ t('first_name') }} <span style="color:var(--danger)">*</span></label>
            <input v-model="form.first_name" type="text" class="input" autocomplete="given-name" />
          </div>
          <div class="field">
            <label class="label">{{ t('middle_name') }}</label>
            <input v-model="form.middle_name" type="text" class="input" />
          </div>
          <div class="field">
            <label class="label">{{ t('last_name') }} <span style="color:var(--danger)">*</span></label>
            <input v-model="form.last_name" type="text" class="input" autocomplete="family-name" />
          </div>
          <div class="field">
            <label class="label">{{ t('gender') }}</label>
            <select v-model="form.gender" class="input">
              <option v-for="g in genders" :key="g" :value="g">{{ t(g) }}</option>
            </select>
          </div>
          <div class="field">
            <label class="label">{{ t('date_of_birth') }}</label>
            <input v-model="form.date_of_birth" type="date" class="input" />
          </div>
          <div class="field">
            <label class="label">{{ t('phone') }}</label>
            <input v-model="form.phone" type="tel" class="input" inputmode="tel" autocomplete="tel" />
          </div>
          <div class="field">
            <label class="label">{{ t('email_address') }}</label>
            <input v-model="form.email" type="email" class="input" autocomplete="email" />
          </div>
          <div class="field">
            <label class="label">{{ t('category') }}</label>
            <select v-model="form.category" class="input">
              <option value="member">{{ t('member') }}</option>
              <option value="supporter">{{ t('supporter') }}</option>
            </select>
          </div>
        </div>

        <div class="card">
          <div class="checkbox-row">
            <input id="consent" v-model="form.consent" type="checkbox" />
            <label for="consent" style="font-size:14px;cursor:pointer">{{ t('consent') }}</label>
          </div>
        </div>

        <button class="btn btn-primary btn-full" :disabled="saving || !form.consent" @click="submit">
          <span v-if="saving" class="spinner" />
          <span v-else>{{ t('save') }}</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { v4 as uuidv4 } from '../utils/uuid.js'
import { queueAndSync } from '../sync/index.js'
import { putMember } from '../db/schema.js'
import AppHeader from '../components/AppHeader.vue'

const { t } = useI18n()
const router = useRouter()
const genders = ['female', 'male', 'other', 'undisclosed']
const saving = ref(false)
const success = ref(false)
const error = ref(null)

const form = reactive({
  first_name: '',
  middle_name: '',
  last_name: '',
  gender: 'undisclosed',
  date_of_birth: '',
  phone: '',
  email: '',
  category: 'member',
  consent: false,
})

async function submit() {
  if (!form.first_name || !form.last_name) {
    error.value = t('required')
    return
  }
  error.value = null
  saving.value = true

  const clientUuid = uuidv4()
  const now = new Date().toISOString()

  // Store locally first (optimistic)
  await putMember({
    id: clientUuid,
    org_unit_id: null,
    status: 'applicant',
    category: form.category,
    updated_at: now,
    first_name: form.first_name,
    middle_name: form.middle_name || null,
    last_name: form.last_name,
    gender: form.gender,
    date_of_birth: form.date_of_birth || null,
    phone: form.phone || null,
    email: form.email || null,
    membership_no: null,
    consent_at: now,
    consent_version: '1.0',
  })

  // Queue for sync
  await queueAndSync({
    client_uuid: clientUuid,
    type: 'create_member',
    payload: {
      org_unit_id: null,
      first_name: form.first_name,
      middle_name: form.middle_name || null,
      last_name: form.last_name,
      gender: form.gender,
      date_of_birth: form.date_of_birth || null,
      phone: form.phone || null,
      email: form.email || null,
      category: form.category,
      consent_at: now,
      consent_version: '1.0',
    },
  }, router)

  saving.value = false
  success.value = true
}
</script>
