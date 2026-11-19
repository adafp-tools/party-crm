<template>
  <div class="page">
    <AppHeader :title="t('record_payment')" :show-back="true" />
    <div class="page-content">
      <div v-if="success" class="card" style="text-align:center;padding:32px">
        <div style="font-size:48px">💳</div>
        <div style="font-size:18px;font-weight:600;margin:12px 0">{{ t('done') }}</div>
        <button class="btn btn-primary" style="margin-top:20px" @click="router.back()">{{ t('back') }}</button>
      </div>

      <template v-else>
        <div v-if="error" class="error-msg">{{ error }}</div>

        <div class="card">
          <div class="field">
            <label class="label">{{ t('dues_plan') }}</label>
            <select v-model="form.dues_plan_id" class="input">
              <option v-for="p in duesPlans" :key="p.id" :value="p.id">
                {{ p.name }} — {{ p.amount }} {{ p.currency }}
              </option>
              <option value="">{{ t('other') }}</option>
            </select>
          </div>

          <div class="field">
            <label class="label">{{ t('amount') }} <span style="color:var(--danger)">*</span></label>
            <input v-model="form.amount" type="number" class="input" inputmode="decimal" min="0" />
          </div>

          <div class="field">
            <label class="label">{{ t('method') }}</label>
            <select v-model="form.method" class="input">
              <option value="cash">{{ t('cash') }}</option>
              <option value="telebirr">Telebirr</option>
              <option value="wave">Wave</option>
              <option value="orange_money">Orange Money</option>
              <option value="bank">{{ t('bank') }}</option>
              <option value="other">{{ t('other') }}</option>
            </select>
          </div>

          <div v-if="form.method !== 'cash'" class="field">
            <label class="label">{{ t('reference') }}</label>
            <input v-model="form.reference" type="text" class="input" />
          </div>

          <div v-if="form.method === 'cash'" class="field">
            <label class="label">{{ t('receipt_no') }}</label>
            <input v-model="form.receipt_no" type="text" class="input" />
          </div>
        </div>

        <button class="btn btn-primary btn-full" :disabled="saving || !form.amount" @click="submit">
          <span v-if="saving" class="spinner" />
          <span v-else>{{ t('save') }}</span>
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { v4 as uuidv4 } from '../utils/uuid.js'
import { queueAndSync } from '../sync/index.js'
import { store } from '../store/index.js'
import AppHeader from '../components/AppHeader.vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const memberId = route.params.id
const duesPlans = store.duesPlans
const saving = ref(false)
const success = ref(false)
const error = ref(null)

const form = reactive({
  dues_plan_id: duesPlans[0]?.id ?? '',
  amount: duesPlans[0]?.amount ?? '',
  method: 'cash',
  reference: '',
  receipt_no: '',
})

async function submit() {
  if (!form.amount) { error.value = t('required'); return }
  error.value = null
  saving.value = true

  await queueAndSync({
    client_uuid: uuidv4(),
    type: 'record_payment',
    payload: {
      member_id: memberId,
      dues_plan_id: form.dues_plan_id || null,
      amount: parseFloat(form.amount),
      currency: store.currency,
      method: form.method,
      reference: form.reference || null,
      receipt_no: form.receipt_no || null,
    },
  }, router)

  saving.value = false
  success.value = true
}
</script>
