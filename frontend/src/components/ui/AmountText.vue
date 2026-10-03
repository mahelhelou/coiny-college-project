<script setup>
import { formatSigned } from '@/utils/money'

defineProps({
  amount: { type: String, required: true },
  type: { type: String, required: true },
})
</script>

<template>
  <span
    :class="[
      'whitespace-nowrap font-semibold tabular-nums',
      type === 'income' ? 'text-primary' : 'text-expense',
    ]"
  >
    <span class="sr-only">{{ type === 'income' ? 'Income' : 'Expense' }}</span>
    {{ formatSigned(amount, type) }}
  </span>
</template>
