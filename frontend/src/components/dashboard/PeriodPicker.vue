<script setup>
import { ref, watch } from 'vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { formatDate, toIsoDate } from '@/utils/dates'

const props = defineProps({
  period: { type: String, required: true },
  range: { type: Object, default: null }, // { from, to } resolved by the API
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['change'])

const options = [
  { value: 'this_month', label: 'This month' },
  { value: 'last_month', label: 'Last month' },
  { value: 'custom', label: 'Custom' },
]

const selected = ref(props.period)
const from = ref(props.range?.from ?? toIsoDate())
const to = ref(props.range?.to ?? toIsoDate())

watch(
  () => props.period,
  (period) => (selected.value = period),
)

// Seed the custom inputs with whatever range is currently shown.
watch(
  () => props.range,
  (range) => {
    if (range && selected.value !== 'custom') {
      from.value = range.from
      to.value = range.to
    }
  },
)

watch(selected, (value) => {
  if (value !== 'custom') emit('change', { period: value })
})

function apply() {
  emit('change', { period: 'custom', from: from.value, to: to.value })
}
</script>

<template>
  <div class="card flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
      <SegmentedControl v-model="selected" :options="options" label="Period" :disabled="loading" />
      <p v-if="range && selected !== 'custom'" class="text-sm text-gray-500">
        {{ formatDate(range.from) }} – {{ formatDate(range.to) }}
      </p>
    </div>

    <form
      v-if="selected === 'custom'"
      class="flex flex-col gap-3 sm:flex-row sm:items-start"
      novalidate
      @submit.prevent="apply"
    >
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="sr-only" for="period-from">From</label>
          <input
            id="period-from"
            v-model="from"
            type="date"
            :class="['input', errors.from && 'input-error']"
            required
          />
          <p v-if="errors.from" class="field-error">{{ errors.from[0] }}</p>
        </div>
        <div>
          <label class="sr-only" for="period-to">To</label>
          <input
            id="period-to"
            v-model="to"
            type="date"
            :min="from"
            :class="['input', errors.to && 'input-error']"
            required
          />
          <p v-if="errors.to" class="field-error">{{ errors.to[0] }}</p>
        </div>
      </div>
      <BaseButton type="submit" :loading="loading" :disabled="!from || !to">Apply</BaseButton>
    </form>
  </div>
</template>
