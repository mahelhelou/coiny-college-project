<script setup>
import { computed, ref, watch } from 'vue'
import { useCategoriesStore } from '@/stores/categories'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import AppIcon from '@/components/ui/AppIcon.vue'

const props = defineProps({
  filters: { type: Object, required: true },
  hasFilters: { type: Boolean, default: false },
})

const emit = defineEmits(['change', 'clear'])

const categories = useCategoriesStore()

const typeOptions = [
  { value: '', label: 'All' },
  { value: 'income', label: 'Income', tone: 'income' },
  { value: 'expense', label: 'Expense', tone: 'expense' },
]

const type = computed({
  get: () => props.filters.type,
  set: (value) => {
    const patch = { type: value }
    // Keep the category only if it still fits the chosen type.
    const current = categories.find(props.filters.category_id)
    if (value && current && current.type !== value) patch.category_id = undefined
    emit('change', patch)
  },
})

const groups = computed(() =>
  [
    { type: 'income', label: 'Income', items: categories.income },
    { type: 'expense', label: 'Expense', items: categories.expense },
  ].filter((g) => !props.filters.type || g.type === props.filters.type),
)

// TRX-10: debounced description search.
const search = ref(props.filters.search)
let timer = null
watch(search, (value) => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    if (value.trim() !== props.filters.search) emit('change', { search: value.trim() })
  }, 350)
})
watch(
  () => props.filters.search,
  (value) => {
    if (value !== search.value.trim()) search.value = value
  },
)

// Below md, the secondary filters collapse behind a toggle.
const expanded = ref(false)
const secondaryCount = computed(
  () => ['category_id', 'date_from', 'date_to'].filter((key) => props.filters[key]).length,
)

const update = (key) => (event) => emit('change', { [key]: event.target.value })
</script>

<template>
  <div class="card p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
      <div class="sm:col-span-2 lg:col-span-1">
        <span class="field-label">Type</span>
        <SegmentedControl v-model="type" :options="typeOptions" label="Type filter" />
      </div>

      <div class="sm:col-span-2 lg:col-span-3">
        <label class="field-label" for="filter-search">Search</label>
        <div class="relative">
          <AppIcon
            name="search"
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            id="filter-search"
            v-model="search"
            type="search"
            class="input pl-9"
            placeholder="Search descriptions"
            maxlength="100"
          />
        </div>
      </div>

      <button
        type="button"
        class="flex h-10 items-center justify-center gap-2 rounded-lg border border-border text-sm font-medium text-gray-700 hover:bg-gray-50 sm:col-span-2 md:hidden"
        :aria-expanded="expanded"
        aria-controls="more-filters"
        @click="expanded = !expanded"
      >
        <AppIcon name="calendar" class="h-4 w-4" />
        {{ expanded ? 'Fewer filters' : 'More filters' }}
        <span
          v-if="secondaryCount"
          class="rounded-full bg-primary px-1.5 text-xs font-semibold text-on-accent"
        >
          {{ secondaryCount }}
        </span>
      </button>

      <div id="more-filters" :class="[expanded ? 'contents' : 'hidden', 'md:contents']">
        <div>
          <label class="field-label" for="filter-category">Category</label>
          <select
            id="filter-category"
            class="input"
            :value="filters.category_id"
            @change="update('category_id')($event)"
          >
            <option value="">All categories</option>
            <optgroup v-for="g in groups" :key="g.type" :label="g.label">
              <option v-for="c in g.items" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </optgroup>
          </select>
        </div>

        <div>
          <label class="field-label" for="filter-from">From</label>
          <input
            id="filter-from"
            type="date"
            class="input"
            :value="filters.date_from"
            @change="update('date_from')($event)"
          />
        </div>

        <div>
          <label class="field-label" for="filter-to">To</label>
          <input
            id="filter-to"
            type="date"
            class="input"
            :min="filters.date_from || undefined"
            :value="filters.date_to"
            @change="update('date_to')($event)"
          />
        </div>

        <button
          type="button"
          class="h-10 rounded-lg border border-border px-3 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!hasFilters"
          @click="emit('clear')"
        >
          Clear filters
        </button>
      </div>
    </div>
  </div>
</template>
