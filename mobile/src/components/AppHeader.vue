<template>
  <header class="app-header">
    <button v-if="showBack" class="btn-icon" @click="router.back()" :aria-label="t('back')">‹</button>
    <h1>{{ title }}</h1>
    <slot name="right" />
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const router = useRouter()
defineProps({ title: String, showBack: { type: Boolean, default: false } })
</script>
