import { createRouter, createWebHistory } from 'vue-router'
import { store } from '../store/index.js'
import { hasToken } from '../api/client.js'
import { hasPinSet, isUnlocked } from '../db/encryption.js'

const routes = [
  { path: '/', redirect: '/home' },
  { path: '/login', component: () => import('../views/LoginView.vue'), meta: { public: true } },
  { path: '/pin-setup', component: () => import('../views/PinSetupView.vue'), meta: { requiresAuth: true } },
  { path: '/pin-unlock', component: () => import('../views/PinUnlockView.vue'), meta: { requiresAuth: true } },
  { path: '/home', component: () => import('../views/HomeView.vue'), meta: { requiresUnlock: true } },
  { path: '/members', component: () => import('../views/MembersView.vue'), meta: { requiresUnlock: true } },
  { path: '/members/new', component: () => import('../views/RegisterMemberView.vue'), meta: { requiresUnlock: true } },
  { path: '/members/:id', component: () => import('../views/MemberDetailView.vue'), meta: { requiresUnlock: true } },
  { path: '/members/:id/dues', component: () => import('../views/DuesPaymentView.vue'), meta: { requiresUnlock: true } },
  { path: '/events', component: () => import('../views/EventsView.vue'), meta: { requiresUnlock: true } },
  { path: '/events/:id', component: () => import('../views/EventAttendanceView.vue'), meta: { requiresUnlock: true } },
  { path: '/tasks', component: () => import('../views/TasksView.vue'), meta: { requiresUnlock: true } },
  { path: '/settings', component: () => import('../views/SettingsView.vue'), meta: { requiresUnlock: true } },
  { path: '/:pathMatch(.*)*', redirect: '/home' },
]

const router = createRouter({
  history: createWebHistory('/app/'),
  routes,
})

router.beforeEach((to) => {
  const authenticated = hasToken()
  const pinSet = hasPinSet()
  const unlocked = isUnlocked()

  if (to.meta.public) return true

  if (!authenticated) return '/login'

  if (to.meta.requiresAuth && !to.meta.requiresUnlock) return true

  if (to.meta.requiresUnlock) {
    if (!pinSet) return '/pin-setup'
    if (!unlocked) return '/pin-unlock'
    return true
  }

  return true
})

export default router
