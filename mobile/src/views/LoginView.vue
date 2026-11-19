<template>
  <div class="auth-screen">
    <div class="auth-logo">🗳️</div>
    <div class="auth-title">{{ store.partyName || t('app_name') }}</div>

    <div class="auth-form">
      <div v-if="error" class="error-msg">{{ error }}</div>

      <div v-if="!needsTotp">
        <div class="field">
          <label class="label">{{ t('email') }}</label>
          <input v-model="form.email" type="email" class="input" autocomplete="email" inputmode="email" />
        </div>
        <div class="field">
          <label class="label">{{ t('password') }}</label>
          <input v-model="form.password" type="password" class="input" autocomplete="current-password" />
        </div>
      </div>

      <div v-else class="field">
        <div class="auth-subtitle" style="margin-bottom:16px">{{ t('totp_required') }}</div>
        <label class="label">{{ t('totp_code') }}</label>
        <input v-model="form.totpCode" type="text" class="input" inputmode="numeric" maxlength="6" autocomplete="one-time-code" style="font-size:24px;text-align:center;letter-spacing:.2em" />
      </div>

      <button class="btn btn-primary btn-full" :disabled="loading" @click="submit">
        <span v-if="loading" class="spinner" />
        <span v-else>{{ t('login') }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { login, setToken } from '../api/client.js'
import { _state } from '../store/index.js'
import { getMe } from '../api/client.js'

const { t } = useI18n()
const router = useRouter()
const { store } = await import('../store/index.js').then(m => ({ store: m.store }))

const form = reactive({ email: '', password: '', totpCode: '' })
const loading = ref(false)
const error = ref(null)
const needsTotp = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    const deviceName = `${navigator.userAgent.slice(0, 50)}`
    const platform = /android/i.test(navigator.userAgent) ? 'android'
      : /iphone|ipad/i.test(navigator.userAgent) ? 'ios' : 'web'

    const data = await login(
      form.email, form.password, deviceName, platform,
      needsTotp.value ? form.totpCode : null,
    )

    if (data.two_factor_required) {
      needsTotp.value = true
      loading.value = false
      return
    }

    setToken(data.token)
    if (data.device_id) localStorage.setItem('crm_device_id', data.device_id)

    _state.isAuthenticated = true
    _state.partyName = data.user.settings?.party_name ?? ''

    const { hasPinSet } = await import('../db/encryption.js')
    router.push(hasPinSet() ? '/pin-unlock' : '/pin-setup')
  } catch (err) {
    if (err.response?.status === 403) {
      needsTotp.value = true
    } else {
      error.value = t('sign_in_error')
    }
    loading.value = false
  }
}
</script>
