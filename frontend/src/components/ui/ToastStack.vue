<script setup>
import { storeToRefs } from 'pinia'
import { useUiStore } from '@/stores/ui'
import AppIcon from './AppIcon.vue'

const ui = useUiStore()
const { toasts } = storeToRefs(ui)
</script>

<template>
  <div
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :role="toast.type === 'error' ? 'alert' : 'status'"
        :class="[
          'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg px-4 py-3 text-sm text-on-accent shadow-lg',
          toast.type === 'error' ? 'bg-danger' : 'bg-primary',
        ]"
      >
        <AppIcon :name="toast.type === 'error' ? 'alert' : 'check'" class="mt-px h-5 w-5" />
        <p class="flex-1 leading-5">{{ toast.message }}</p>
        <button
          type="button"
          class="-m-1 rounded p-1 text-on-accent/80 hover:text-on-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-on-accent"
          aria-label="Dismiss"
          @click="ui.dismiss(toast.id)"
        >
          <AppIcon name="x" class="h-4 w-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
