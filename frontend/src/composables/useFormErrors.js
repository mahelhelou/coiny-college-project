import { ref } from 'vue'

/** Maps a 422 response to { field: [messages] } for inline rendering under each field. */
export function useFormErrors() {
  const errors = ref({})

  /** Returns true when the error was a validation error and has been captured. */
  function capture(error) {
    if (error?.response?.status !== 422) return false
    errors.value = error.response.data?.errors ?? {}
    return true
  }

  function clear(field) {
    if (!field) {
      errors.value = {}
      return
    }
    const next = { ...errors.value }
    delete next[field]
    errors.value = next
  }

  const first = (field) => errors.value[field]?.[0] ?? ''

  return { errors, capture, clear, first }
}
