<template>
  <nav class="bottom-nav" role="navigation">
    <router-link to="/home" class="nav-item" :class="{ active: route.path === '/home' }">
      <span class="nav-icon">🏠</span>
      <span>{{ t('home') }}</span>
    </router-link>
    <router-link to="/members" class="nav-item" :class="{ active: route.path.startsWith('/members') }">
      <span class="nav-icon">👥</span>
      <span>{{ t('members') }}</span>
    </router-link>
    <router-link to="/events" class="nav-item" :class="{ active: route.path.startsWith('/events') }">
      <span class="nav-icon">📅</span>
      <span>{{ t('events') }}</span>
    </router-link>
    <router-link to="/tasks" class="nav-item" :class="{ active: route.path.startsWith('/tasks') }">
      <span class="nav-icon">✅</span>
      <span>{{ t('tasks') }}</span>
    </router-link>
    <router-link to="/settings" class="nav-item" :class="{ active: route.path === '/settings' }">
      <span class="nav-icon">⚙️</span>
      <span>{{ t('settings') }}</span>
    </router-link>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
const route = useRoute()
const { t } = useI18n()
</script>
