<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'

defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  icon: { type: String, required: true },
  tone: { type: String, default: 'neutral' }, // income | expense | neutral
  hint: { type: String, default: '' },
  negative: { type: Boolean, default: false },
})

const iconTone = {
  income: 'bg-primary/10 text-primary',
  expense: 'bg-expense/10 text-expense',
  neutral: 'bg-gray-100 text-gray-700',
}
</script>

<template>
  <div class="card p-5">
    <div class="flex items-center justify-between gap-3">
      <p class="text-sm font-medium text-gray-500">{{ label }}</p>
      <span :class="['flex h-9 w-9 items-center justify-center rounded-lg', iconTone[tone]]">
        <AppIcon :name="icon" />
      </span>
    </div>
    <p
      :class="[
        'mt-3 truncate text-2xl font-semibold tabular-nums tracking-tight',
        negative ? 'text-expense' : 'text-gray-900',
      ]"
      :title="value"
    >
      {{ value }}
    </p>
    <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
  </div>
</template>
