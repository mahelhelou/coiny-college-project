<script setup>
import AppSpinner from './AppSpinner.vue'

defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  type: { type: String, default: 'button' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

const variants = {
  primary: 'bg-primary text-on-accent shadow-sm hover:bg-primary/90 focus-visible:ring-primary',
  secondary:
    'border border-border bg-surface text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:ring-primary',
  danger: 'bg-danger text-on-accent shadow-sm hover:bg-danger/90 focus-visible:ring-danger',
  ghost: 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-primary',
}

const sizes = {
  sm: 'h-8 px-3 text-sm',
  md: 'h-10 px-4 text-sm',
  icon: 'h-9 w-9',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    :class="[
      'inline-flex shrink-0 items-center justify-center gap-2 rounded-lg font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
      variants[variant],
      sizes[size],
      block && 'w-full',
    ]"
  >
    <AppSpinner v-if="loading" class="h-4 w-4" />
    <slot />
  </button>
</template>
