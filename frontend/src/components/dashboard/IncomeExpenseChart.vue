<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js'
import { storeToRefs } from 'pinia'
import { useUiStore } from '@/stores/ui'
import { chartTheme } from '@/utils/palette'
import { formatBucket } from '@/utils/dates'
import { formatCompactMoney, formatMoney } from '@/utils/money'

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip)

const props = defineProps({
  points: { type: Array, required: true },
  granularity: { type: String, required: true },
})

const { resolvedTheme } = storeToRefs(useUiStore())
const colors = computed(() => chartTheme(resolvedTheme.value))

const series = computed(() => [
  { key: 'income', label: 'Income', sign: '+', color: colors.value.income },
  { key: 'expenses', label: 'Expenses', sign: '−', color: colors.value.expense },
])

// Chart.js needs numbers to draw; tooltips show the API's exact strings.
const data = computed(() => ({
  labels: props.points.map((p) => formatBucket(p.bucket, props.granularity)),
  datasets: series.value.map((s) => ({
    label: s.label,
    data: props.points.map((p) => Number(p[s.key])),
    backgroundColor: s.color,
    hoverBackgroundColor: s.color,
    borderRadius: 4,
    borderSkipped: 'start',
    maxBarThickness: 20,
    categoryPercentage: 0.7,
    barPercentage: 0.85,
  })),
}))

const options = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: colors.value.tooltipBg,
      padding: 10,
      boxPadding: 4,
      callbacks: {
        label: (ctx) => {
          const s = series.value[ctx.datasetIndex]
          return ` ${s.label}: ${s.sign}${formatMoney(props.points[ctx.dataIndex][s.key])}`
        },
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      border: { color: colors.value.border },
      ticks: { color: colors.value.ticks, maxRotation: 0, autoSkip: true, autoSkipPadding: 12 },
    },
    y: {
      beginAtZero: true,
      grid: { color: colors.value.grid },
      border: { display: false },
      ticks: {
        color: colors.value.ticks,
        maxTicksLimit: 6,
        callback: (v) => formatCompactMoney(v),
      },
    },
  },
}))
</script>

<template>
  <div>
    <ul class="mb-4 flex items-center gap-4 text-sm text-gray-600">
      <li v-for="s in series" :key="s.key" class="flex items-center gap-2">
        <span class="h-2.5 w-2.5 rounded-sm" :style="{ backgroundColor: s.color }" />
        <span aria-hidden="true" class="font-medium">{{ s.sign }}</span
        >{{ s.label }}
      </li>
    </ul>
    <div class="relative h-[260px] sm:h-[300px]">
      <Bar
        :data="data"
        :options="options"
        :aria-label="`Income versus expenses per ${granularity}`"
        role="img"
      />
    </div>
  </div>
</template>
