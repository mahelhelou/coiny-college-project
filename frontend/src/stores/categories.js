import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api/categories'

const byName = (a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' })

export const useCategoriesStore = defineStore('categories', () => {
  const items = ref([])
  const loaded = ref(false)
  const loading = ref(false)
  let pending = null

  const income = computed(() => items.value.filter((c) => c.type === 'income'))
  const expense = computed(() => items.value.filter((c) => c.type === 'expense'))

  const ofType = (type) => (type === 'income' ? income.value : expense.value)
  const find = (id) => items.value.find((c) => c.id === Number(id))

  /** Loaded once and shared; pass force to refresh transaction counts. */
  function load({ force = false } = {}) {
    if (loaded.value && !force) return Promise.resolve()
    pending ??= api
      .listCategories()
      .then((list) => {
        items.value = list
        loaded.value = true
      })
      .finally(() => {
        pending = null
        loading.value = false
      })
    loading.value = true
    return pending
  }

  async function create(payload) {
    const category = await api.createCategory(payload)
    items.value = [...items.value, category].sort(byName)
    return category
  }

  async function update(id, payload) {
    const category = await api.updateCategory(id, payload)
    items.value = items.value.map((c) => (c.id === id ? category : c)).sort(byName)
    return category
  }

  async function remove(id) {
    await api.deleteCategory(id)
    items.value = items.value.filter((c) => c.id !== id)
  }

  function reset() {
    items.value = []
    loaded.value = false
  }

  return {
    items,
    loaded,
    loading,
    income,
    expense,
    ofType,
    find,
    load,
    create,
    update,
    remove,
    reset,
  }
})
