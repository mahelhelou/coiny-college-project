<script setup>
import { useId } from 'vue'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: [String, Number], default: '' })

defineProps({
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
})

const id = useId()
</script>

<template>
  <div>
    <label v-if="label" :for="id" class="field-label">{{ label }}</label>
    <select
      :id="id"
      v-model="model"
      v-bind="$attrs"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="error || hint ? `${id}-help` : undefined"
      :class="['input pr-8', error && 'input-error']"
    >
      <slot />
    </select>
    <p v-if="error" :id="`${id}-help`" class="field-error">{{ error }}</p>
    <p v-else-if="hint || $slots.hint" :id="`${id}-help`" class="mt-1.5 text-xs text-gray-500">
      <slot name="hint">{{ hint }}</slot>
    </p>
  </div>
</template>
