<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  icon: { type: String, default: 'receipt' },
  title: { type: String, required: true },
  message: { type: String, default: '' },
})
</script>

<template>
  <div class="flex flex-col items-center px-6 py-12 text-center">
    <span
      class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
    >
      <AppIcon :name="icon" class="h-6 w-6" />
    </span>
    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ title }}</h3>
    <p v-if="message" class="mt-1 max-w-sm text-sm text-gray-500">{{ message }}</p>
    <div v-if="$slots.default" class="mt-5">
      <slot />
    </div>
  </div>
</template>
