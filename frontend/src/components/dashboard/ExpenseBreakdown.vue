<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { ArcElement, Chart, Tooltip } from 'chart.js'
import { storeToRefs } from 'pinia'
import { useUiStore } from '@/stores/ui'
import { chartTheme } from '@/utils/palette'
import { formatMoney } from '@/utils/money'

Chart.register(ArcElement, Tooltip)

const { resolvedTheme } = storeToRefs(useUiStore())
const colors = computed(() => chartTheme(resolvedTheme.value))

const props = defineProps({
  items: { type: Array, required: true }, // expense_by_category
  total: { type: String, required: true }, // totals.expenses
  colorFor: { type: Function, required: true }, // stable colour per category id
})

const data = computed(() => ({
  labels: props.items.map((i) => i.name),
  datasets: [
    {
      data: props.items.map((i) => Number(i.total)),
      backgroundColor: props.items.map((i) => props.colorFor(i.category_id)),
      borderColor: colors.value.surface, // 2px surface gap between slices
      borderWidth: 2,
      hoverOffset: 4,
    },
  ],
}))

const options = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: colors.value.tooltipBg,
      padding: 10,
      boxPadding: 4,
      callbacks: {
        label: (ctx) => {
          const item = props.items[ctx.dataIndex]
          return ` ${item.name}: ${formatMoney(item.total)} (${item.percentage}%)`
        },
      },
    },
  },
}))
</script>

<template>
  <div class="flex flex-col gap-6 sm:flex-row sm:items-center lg:flex-col lg:items-stretch">
    <div class="relative mx-auto h-[220px] w-[220px] shrink-0">
      <Doughnut :data="data" :options="options" role="img" aria-label="Expenses by category" />
      <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-xs font-medium text-gray-500">Total spent</span>
        <span class="mt-0.5 text-lg font-semibold tabular-nums text-gray-900">
          {{ formatMoney(total) }}
        </span>
      </div>
    </div>

    <ul class="min-w-0 flex-1 space-y-2.5">
      <li v-for="item in items" :key="item.category_id" class="flex items-center gap-3 text-sm">
        <span
          class="h-2.5 w-2.5 shrink-0 rounded-sm"
          :style="{ backgroundColor: colorFor(item.category_id) }"
        />
        <span class="min-w-0 flex-1 truncate text-gray-700">{{ item.name }}</span>
        <span class="tabular-nums font-medium text-gray-900">{{ formatMoney(item.total) }}</span>
        <span class="w-12 text-right tabular-nums text-gray-500">{{ item.percentage }}%</span>
      </li>
    </ul>
  </div>
</template>
