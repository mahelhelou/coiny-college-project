<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  meta: { type: Object, required: true },
  perPage: { type: [String, Number], default: 15 },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['page', 'per-page'])

const perPageOptions = [15, 30, 50, 100]
</script>

<template>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-gray-500">
      Showing
      <span class="font-medium text-gray-900">{{ meta.from ?? 0 }}–{{ meta.to ?? 0 }}</span>
      of <span class="font-medium text-gray-900">{{ meta.total }}</span>
    </p>
    <div class="flex items-center justify-between gap-3 sm:justify-end">
      <label class="flex items-center gap-2 text-sm text-gray-500">
        Per page
        <select
          class="input h-8 w-20 py-0"
          :value="String(perPage || 15)"
          :disabled="disabled"
          @change="emit('per-page', $event.target.value)"
        >
          <option v-for="n in perPageOptions" :key="n" :value="String(n)">{{ n }}</option>
        </select>
      </label>
      <nav class="flex items-center gap-1" aria-label="Pagination">
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-surface text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="disabled || meta.current_page <= 1"
          aria-label="Previous page"
          @click="emit('page', meta.current_page - 1)"
        >
          <AppIcon name="chevron-left" class="h-4 w-4" />
        </button>
        <span class="min-w-[4.5rem] text-center text-sm text-gray-600">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-surface text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="disabled || meta.current_page >= meta.last_page"
          aria-label="Next page"
          @click="emit('page', meta.current_page + 1)"
        >
          <AppIcon name="chevron-right" class="h-4 w-4" />
        </button>
      </nav>
    </div>
  </div>
</template>
