import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { errorMessage } from '@/api/http'

export const THEMES = ['light', 'dark', 'system']
const THEME_KEY = 'coiny-theme' // also read by the pre-paint script in index.html

function storedTheme() {
  try {
    const value = localStorage.getItem(THEME_KEY)
    return THEMES.includes(value) ? value : 'system'
  } catch {
    return 'system'
  }
}

export const useUiStore = defineStore('ui', () => {
  // Theme: a per-browser preference, so localStorage (not the API) is the right home.
  const theme = ref(storedTheme())
  const media = window.matchMedia('(prefers-color-scheme: dark)')
  const systemDark = ref(media.matches)
  media.addEventListener('change', (event) => (systemDark.value = event.matches))

  const resolvedTheme = computed(() =>
    theme.value === 'system' ? (systemDark.value ? 'dark' : 'light') : theme.value,
  )

  watch(
    resolvedTheme,
    (mode) => document.documentElement.classList.toggle('dark', mode === 'dark'),
    { immediate: true, flush: 'sync' },
  )

  function setTheme(value) {
    theme.value = value
    try {
      localStorage.setItem(THEME_KEY, value)
    } catch {
      // Storage unavailable (private mode): the choice lasts for this page load only.
    }
  }

  const toasts = ref([])
  let nextId = 1

  function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
  }

  function notify(type, message, timeout) {
    const id = nextId++
    toasts.value.push({ id, type, message })
    if (timeout) setTimeout(() => dismiss(id), timeout)
  }

  const success = (message) => notify('success', message, 3000)
  const error = (message) => notify('error', message, 6000)

  /** Red toast for a failed request; the caller keeps its previous state. */
  const fail = (err, fallback) => error(errorMessage(err, fallback))

  return { theme, resolvedTheme, setTheme, toasts, success, error, fail, dismiss }
})
