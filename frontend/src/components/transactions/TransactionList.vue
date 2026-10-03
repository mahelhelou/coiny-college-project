<script setup>
import AmountText from '@/components/ui/AmountText.vue'
import TypeBadge from '@/components/ui/TypeBadge.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { formatDate } from '@/utils/dates'

defineProps({
  items: { type: Array, required: true },
})

const emit = defineEmits(['edit', 'delete'])
</script>

<template>
  <!-- Table: md and up -->
  <table class="hidden w-full text-left text-sm md:table">
    <thead class="border-b border-border bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
      <tr>
        <th scope="col" class="px-5 py-3 font-medium">Date</th>
        <th scope="col" class="px-5 py-3 font-medium">Category</th>
        <th scope="col" class="px-5 py-3 font-medium">Description</th>
        <th scope="col" class="px-5 py-3 text-right font-medium">Amount</th>
        <th scope="col" class="w-24 px-5 py-3"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-border">
      <tr v-for="trx in items" :key="trx.id" class="group hover:bg-gray-50">
        <td class="whitespace-nowrap px-5 py-3.5 tabular-nums text-gray-600">
          {{ formatDate(trx.transaction_date) }}
        </td>
        <td class="px-5 py-3.5">
          <div class="flex items-center gap-2">
            <span class="font-medium text-gray-900">{{ trx.category?.name }}</span>
            <TypeBadge :type="trx.type" />
          </div>
        </td>
        <td class="max-w-xs truncate px-5 py-3.5 text-gray-600" :title="trx.description || ''">
          {{ trx.description || '—' }}
        </td>
        <td class="px-5 py-3.5 text-right">
          <AmountText :amount="trx.amount" :type="trx.type" />
        </td>
        <td class="px-5 py-3.5">
          <div class="flex justify-end gap-1">
            <button
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
              :aria-label="`Edit transaction from ${formatDate(trx.transaction_date)}`"
              @click="emit('edit', trx)"
            >
              <AppIcon name="pencil" class="h-4 w-4" />
            </button>
            <button
              type="button"
              class="rounded-md p-1.5 text-gray-400 hover:bg-danger/10 hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger"
              :aria-label="`Delete transaction from ${formatDate(trx.transaction_date)}`"
              @click="emit('delete', trx)"
            >
              <AppIcon name="trash" class="h-4 w-4" />
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Stacked cards: below md -->
  <ul class="divide-y divide-border md:hidden">
    <li v-for="trx in items" :key="trx.id" class="flex items-start gap-3 p-4">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <p class="truncate text-sm font-medium text-gray-900">{{ trx.category?.name }}</p>
          <TypeBadge :type="trx.type" />
        </div>
        <p class="mt-0.5 text-xs tabular-nums text-gray-500">
          {{ formatDate(trx.transaction_date) }}
        </p>
        <p v-if="trx.description" class="mt-1 line-clamp-2 text-sm text-gray-600">
          {{ trx.description }}
        </p>
      </div>
      <div class="flex flex-col items-end gap-1">
        <AmountText :amount="trx.amount" :type="trx.type" class="text-sm" />
        <div class="flex gap-1">
          <button
            type="button"
            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Edit transaction"
            @click="emit('edit', trx)"
          >
            <AppIcon name="pencil" class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="rounded-md p-1.5 text-gray-400 hover:bg-danger/10 hover:text-danger"
            aria-label="Delete transaction"
            @click="emit('delete', trx)"
          >
            <AppIcon name="trash" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </li>
  </ul>
</template>
