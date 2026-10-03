<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

defineProps({
  type: { type: String, required: true },
  items: { type: Array, required: true },
})

const emit = defineEmits(['add', 'edit', 'delete'])

const countLabel = (n) => (n === 1 ? '1 transaction' : `${n ?? 0} transactions`)
</script>

<template>
  <section class="card overflow-hidden">
    <header class="flex items-center justify-between border-b border-border px-5 py-4">
      <div class="flex items-center gap-3">
        <span
          :class="[
            'flex h-8 w-8 items-center justify-center rounded-lg',
            type === 'income' ? 'bg-primary/10 text-primary' : 'bg-expense/10 text-expense',
          ]"
        >
          <AppIcon :name="type" class="h-4 w-4" />
        </span>
        <h2 class="font-semibold text-gray-900">
          {{ type === 'income' ? 'Income' : 'Expense' }}
          <span class="ml-1 text-sm font-normal text-gray-500">{{ items.length }}</span>
        </h2>
      </div>
      <BaseButton variant="ghost" size="sm" @click="emit('add', type)">
        <AppIcon name="plus" class="h-4 w-4" />
        Add
      </BaseButton>
    </header>

    <EmptyState
      v-if="items.length === 0"
      icon="categories"
      :title="`No ${type} categories`"
      message="Create one to start recording transactions of this type."
    >
      <BaseButton size="sm" @click="emit('add', type)">Add category</BaseButton>
    </EmptyState>

    <ul v-else class="divide-y divide-border">
      <li v-for="category in items" :key="category.id" class="flex items-center gap-3 px-5 py-3">
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-gray-900">{{ category.name }}</p>
          <p class="text-xs text-gray-500">{{ countLabel(category.transactions_count) }}</p>
        </div>
        <button
          type="button"
          class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
          :aria-label="`Rename ${category.name}`"
          @click="emit('edit', category)"
        >
          <AppIcon name="pencil" class="h-4 w-4" />
        </button>
        <!-- BR-07: categories in use can't be deleted (the server answers 409 as well). -->
        <button
          type="button"
          class="rounded-md p-1.5 text-gray-400 hover:bg-danger/10 hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-gray-400"
          :disabled="category.transactions_count > 0"
          :title="
            category.transactions_count > 0
              ? 'Categories with transactions cannot be deleted'
              : `Delete ${category.name}`
          "
          :aria-label="`Delete ${category.name}`"
          @click="emit('delete', category)"
        >
          <AppIcon name="trash" class="h-4 w-4" />
        </button>
      </li>
    </ul>
  </section>
</template>
