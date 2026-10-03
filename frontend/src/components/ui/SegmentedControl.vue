<script setup>
const model = defineModel({ type: String, default: '' })

defineProps({
  options: { type: Array, required: true }, // [{ value, label, tone? }]
  label: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

const activeTone = {
  income: 'bg-primary text-on-accent shadow-sm',
  expense: 'bg-expense text-on-accent shadow-sm',
  neutral: 'bg-surface text-gray-900 shadow-sm',
}
</script>

<template>
  <div
    role="group"
    :aria-label="label || undefined"
    :class="[
      'inline-flex rounded-lg bg-gray-100 p-1',
      block && 'flex w-full',
      disabled && 'opacity-60',
    ]"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :disabled="disabled"
      :aria-pressed="model === option.value"
      :class="[
        'h-8 whitespace-nowrap rounded-md px-2.5 text-sm font-medium transition-colors sm:px-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed',
        block && 'flex-1',
        model === option.value
          ? activeTone[option.tone || 'neutral']
          : 'text-gray-600 hover:text-gray-900',
      ]"
      @click="model = option.value"
    >
      {{ option.label }}
    </button>
  </div>
</template>
