<script setup>
import { useId } from 'vue'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: [String, Number], default: '' })

defineProps({
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  type: { type: String, default: 'text' },
  multiline: { type: Boolean, default: false },
})

const id = useId()
</script>

<template>
  <div>
    <label v-if="label" :for="id" class="field-label">{{ label }}</label>
    <textarea
      v-if="multiline"
      :id="id"
      v-model="model"
      v-bind="$attrs"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="error || hint ? `${id}-help` : undefined"
      :class="['input', error && 'input-error']"
    />
    <input
      v-else
      :id="id"
      v-model="model"
      :type="type"
      v-bind="$attrs"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="error || hint ? `${id}-help` : undefined"
      :class="['input', error && 'input-error']"
    />
    <p v-if="error" :id="`${id}-help`" class="field-error">{{ error }}</p>
    <p v-else-if="hint" :id="`${id}-help`" class="mt-1.5 text-xs text-gray-500">{{ hint }}</p>
  </div>
</template>
