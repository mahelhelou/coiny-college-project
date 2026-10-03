<script setup>
import { storeToRefs } from 'pinia'
import { useUiStore } from '@/stores/ui'
import AppIcon from './AppIcon.vue'

defineProps({
  // "sidebar" sits on the always-dark sidebar; "surface" sits on a themed page or card.
  variant: { type: String, default: 'surface' },
  labels: { type: Boolean, default: false },
})

const ui = useUiStore()
const { theme } = storeToRefs(ui)

const options = [
  { value: 'light', label: 'Light', icon: 'sun' },
  { value: 'dark', label: 'Dark', icon: 'moon' },
  { value: 'system', label: 'System', icon: 'monitor' },
]

const styles = {
  surface: {
    track: 'bg-gray-100',
    active: 'bg-surface text-gray-900 shadow-sm',
    idle: 'text-gray-500 hover:text-gray-900',
  },
  sidebar: {
    track: 'bg-white/10',
    active: 'bg-white/15 text-white',
    idle: 'text-white/60 hover:text-white',
  },
}
</script>

<template>
  <div
    role="radiogroup"
    aria-label="Colour theme"
    :class="['inline-flex rounded-lg p-1', styles[variant].track, labels && 'w-full']"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="theme === option.value"
      :aria-label="labels ? undefined : `${option.label} theme`"
      :title="labels ? undefined : `${option.label} theme`"
      :class="[
        'flex h-7 items-center justify-center gap-1.5 rounded-md text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
        labels ? 'flex-1 px-2' : 'w-8',
        theme === option.value ? styles[variant].active : styles[variant].idle,
      ]"
      @click="ui.setTheme(option.value)"
    >
      <AppIcon :name="option.icon" class="h-4 w-4" />
      <span v-if="labels">{{ option.label }}</span>
    </button>
  </div>
</template>
