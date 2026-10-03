<script setup>
defineProps({
  inverse: { type: Boolean, default: false },
})
</script>

<template>
  <span class="inline-flex items-center gap-2.5">
    <svg viewBox="0 0 32 32" class="h-8 w-8 shrink-0" aria-hidden="true">
      <circle cx="16" cy="16" r="15" class="fill-primary" />
      <circle cx="16" cy="16" r="11" fill="none" class="stroke-on-accent/35" stroke-width="1.5" />
      <path
        d="M20.5 11.8a6 6 0 1 0 0 8.4"
        fill="none"
        class="stroke-on-accent"
        stroke-width="3"
        stroke-linecap="round"
      />
    </svg>
    <span :class="['text-xl font-bold tracking-tight', inverse ? 'text-white' : 'text-gray-900']">
      Coiny
    </span>
  </span>
</template>
