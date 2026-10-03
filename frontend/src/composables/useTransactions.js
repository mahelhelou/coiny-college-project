import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { listTransactions } from '@/api/transactions'
import { useUiStore } from '@/stores/ui'
import { cleanQuery, queryString } from '@/utils/query'

const FILTER_KEYS = ['type', 'category_id', 'date_from', 'date_to', 'search']
const QUERY_KEYS = [...FILTER_KEYS, 'page', 'per_page']

/** TRX-08/09: the list is driven entirely by route.query so it survives reloads and can be shared. */
export function useTransactions() {
  const route = useRoute()
  const router = useRouter()
  const ui = useUiStore()
  const routeName = route.name

  const items = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const loaded = ref(false)

  const filters = computed(() =>
    Object.fromEntries(QUERY_KEYS.map((key) => [key, queryString(route.query[key])])),
  )
  const hasFilters = computed(() => FILTER_KEYS.some((key) => filters.value[key]))

  let requestId = 0

  async function fetch() {
    const id = ++requestId
    loading.value = true
    try {
      const body = await listTransactions(cleanQuery(filters.value))
      if (id !== requestId) return
      items.value = body.data
      meta.value = body.meta
      loaded.value = true
    } catch (error) {
      if (id === requestId) ui.fail(error, 'Could not load transactions.')
    } finally {
      if (id === requestId) loading.value = false
    }
  }

  watch(
    () => route.query,
    () => {
      if (route.name === routeName) fetch()
    },
    { immediate: true },
  )

  /** Any filter change goes back to page 1. */
  function setFilters(patch) {
    router.replace({ query: cleanQuery({ ...route.query, ...patch, page: undefined }) })
  }

  function setPage(page) {
    router.replace({
      query: cleanQuery({ ...route.query, page: page > 1 ? String(page) : undefined }),
    })
  }

  function clearFilters() {
    router.replace({ query: cleanQuery({ per_page: route.query.per_page }) })
  }

  return {
    items,
    meta,
    loading,
    loaded,
    filters,
    hasFilters,
    fetch,
    setFilters,
    setPage,
    clearFilters,
  }
}
