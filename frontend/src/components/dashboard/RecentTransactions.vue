<script setup>
import AmountText from '@/components/ui/AmountText.vue'
import { formatDate } from '@/utils/dates'

defineProps({
  items: { type: Array, required: true },
})
</script>

<template>
  <ul class="divide-y divide-border">
    <li v-for="trx in items" :key="trx.id" class="flex items-center gap-3 py-3">
      <span
        :class="[
          'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
          trx.type === 'income' ? 'bg-primary/10 text-primary' : 'bg-expense/10 text-expense',
        ]"
        aria-hidden="true"
      >
        {{ trx.category?.name?.[0]?.toUpperCase() }}
      </span>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900">{{ trx.category?.name }}</p>
        <p class="truncate text-xs text-gray-500">
          {{ formatDate(trx.transaction_date) }}
          <template v-if="trx.description"> · {{ trx.description }}</template>
        </p>
      </div>
      <AmountText :amount="trx.amount" :type="trx.type" class="text-sm" />
    </li>
  </ul>
</template>
