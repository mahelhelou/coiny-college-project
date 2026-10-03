import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { onUnauthorized } from './api/http'
import { useAuthStore } from './stores/auth'
import './assets/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)

// 401 on any call except /user → the session expired: reset state and send the user to log in.
onUnauthorized(() => {
  useAuthStore().reset()
  if (router.currentRoute.value.meta.requiresAuth) {
    router.replace({ name: 'login', query: { expired: '1' } })
  }
})

router.isReady().then(() => app.mount('#app'))
