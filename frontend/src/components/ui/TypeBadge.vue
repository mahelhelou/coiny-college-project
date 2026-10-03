<script setup>
defineProps({
  type: { type: String, required: true },
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
      type === 'income' ? 'bg-primary/10 text-primary' : 'bg-expense/10 text-expense',
    ]"
  >
    <span aria-hidden="true">{{ type === 'income' ? '+' : '−' }}</span>
    {{ type === 'income' ? 'Income' : 'Expense' }}
  </span>
</template>
