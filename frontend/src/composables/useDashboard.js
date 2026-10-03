import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getDashboard } from '@/api/dashboard'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from './useFormErrors'
import { cleanQuery, queryString } from '@/utils/query'

export const PERIODS = ['this_month', 'last_month', 'custom']

/** DSH/CHT: the period lives in route.query (BR-12); every figure comes from the API (BR-17). */
export function useDashboard() {
  const route = useRoute()
  const router = useRouter()
  const ui = useUiStore()
  const { errors, capture, clear, first } = useFormErrors()
  const routeName = route.name

  const data = ref(null)
  const loading = ref(false)

  const params = computed(() => {
    const period = PERIODS.includes(route.query.period) ? route.query.period : 'this_month'
    return period === 'custom'
      ? { period, from: queryString(route.query.from), to: queryString(route.query.to) }
      : { period }
  })

  let requestId = 0

  async function load() {
    const id = ++requestId
    loading.value = true
    clear()
    try {
      const result = await getDashboard(params.value)
      if (id === requestId) data.value = result
    } catch (error) {
      if (id === requestId && !capture(error)) ui.fail(error, 'Could not load the dashboard.')
    } finally {
      if (id === requestId) loading.value = false
    }
  }

  watch(
    () => route.query,
    () => {
      if (route.name === routeName) load()
    },
    { immediate: true },
  )

  function setPeriod({ period, from, to }) {
    const query =
      period === 'custom'
        ? { period, from, to }
        : { period: period === 'this_month' ? undefined : period }
    router.replace({ query: cleanQuery(query) })
  }

  return { data, loading, params, errors, first, load, setPeriod }
}
