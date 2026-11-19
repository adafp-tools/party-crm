<template>
  <div class="auth-screen">
    <div class="auth-logo">🔐</div>
    <div class="auth-title">{{ t('setup_pin') }}</div>
    <div class="auth-subtitle">{{ step === 'confirm' ? t('confirm_pin') : t('setup_pin_desc') }}</div>

    <div v-if="error" class="error-msg">{{ error }}</div>

    <div class="pin-display">
      <div v-for="i in 6" :key="i" class="pin-dot" :class="{ filled: pin.length >= i }" />
    </div>

    <div class="pin-pad">
      <button v-for="n in [1,2,3,4,5,6,7,8,9]" :key="n" class="pin-key" @click="press(String(n))">{{ n }}</button>
      <div class="pin-spacer" />
      <button class="pin-key" @click="press('0')">0</button>
      <button class="pin-key del" @click="del">⌫</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { setupPin } from '../db/encryption.js'
import { _state, setUnlocked } from '../store/index.js'
import { getMe } from '../api/client.js'
import { runSync } from '../sync/index.js'

const { t } = useI18n()
const router = useRouter()
const pin = ref('')
const firstPin = ref('')
const step = ref('enter') // 'enter' | 'confirm'
const error = ref(null)

function press(digit) {
  if (pin.value.length < 6) {
    pin.value += digit
    if (pin.value.length === 6) onPinComplete()
  }
}

function del() { pin.value = pin.value.slice(0, -1) }

async function onPinComplete() {
  error.value = null
  if (step.value === 'enter') {
    firstPin.value = pin.value
    pin.value = ''
    step.value = 'confirm'
    return
  }

  if (pin.value !== firstPin.value) {
    error.value = t('pin_mismatch')
    pin.value = ''
    firstPin.value = ''
    step.value = 'enter'
    return
  }

  await setupPin(pin.value)
  _state.hasPinSet = true

  // Load user data
  try {
    const user = await getMe()
    setUnlocked(user)
    runSync(router)
  } catch {
    setUnlocked(null)
  }

  router.replace('/home')
}
</script>
