import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api/auth'
import { useCategoriesStore } from './categories'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const initialized = ref(false)
  let pending = null

  const isAuthenticated = computed(() => user.value !== null)

  /** Probe the session once per page load; a 401 simply means "guest". */
  function init() {
    if (initialized.value) return Promise.resolve()
    pending ??= api
      .fetchUser()
      .then((u) => (user.value = u))
      .catch(() => (user.value = null))
      .finally(() => (initialized.value = true))
    return pending
  }

  async function login(credentials) {
    user.value = await api.login(credentials)
  }

  async function register(payload) {
    user.value = await api.register(payload)
  }

  async function logout() {
    try {
      await api.logout()
    } finally {
      reset()
    }
  }

  async function updateProfile(payload) {
    user.value = await api.updateProfile(payload)
  }

  /** Drop everything tied to the previous user so nothing leaks into the next session. */
  function reset() {
    user.value = null
    useCategoriesStore().reset()
  }

  return { user, initialized, isAuthenticated, init, login, register, logout, updateProfile, reset }
})
