import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'
import i18n from './i18n/index.js'
import { initState, setupActivityListeners } from './store/index.js'
import { setupSyncListeners } from './sync/index.js'
import './assets/app.css'

initState()
setupActivityListeners()
setupSyncListeners(router)

const app = createApp(App)
app.use(router)
app.use(i18n)
app.mount('#app')
