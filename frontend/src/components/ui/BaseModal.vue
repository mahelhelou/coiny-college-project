<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
})

const emit = defineEmits(['close'])

const panel = ref(null)
const titleId = useId()
let previousFocus = null

watch(
  () => props.open,
  async (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) {
      previousFocus = document.activeElement
      await nextTick()
      const el = panel.value
      const target =
        el?.querySelector('[autofocus]') ??
        el?.querySelector('input:not([disabled]), select, textarea') ??
        el?.querySelector('button')
      target?.focus()
    } else {
      previousFocus?.focus?.()
    }
  },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-4"
        @keydown.esc="emit('close')"
        @mousedown.self="emit('close')"
      >
        <div
          ref="panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          class="flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-xl bg-surface shadow-xl sm:max-w-md sm:rounded-lg"
        >
          <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
            <div>
              <h2 :id="titleId" class="text-base font-semibold text-gray-900">{{ title }}</h2>
              <p v-if="description" class="mt-0.5 text-sm text-gray-500">{{ description }}</p>
            </div>
            <button
              type="button"
              class="-m-1.5 rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
              aria-label="Close"
              @click="emit('close')"
            >
              <AppIcon name="x" />
            </button>
          </div>
          <div class="overflow-y-auto px-5 py-5">
            <slot />
          </div>
          <div
            v-if="$slots.footer"
            class="flex flex-col-reverse gap-2 border-t border-border bg-gray-50 px-5 py-3 sm:flex-row sm:justify-end"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
