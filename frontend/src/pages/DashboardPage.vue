<script setup>
import { computed, ref } from 'vue'
import { useDashboard } from '@/composables/useDashboard'
import { useCategoriesStore } from '@/stores/categories'
import { formatMoney, isNegative } from '@/utils/money'
import { storeToRefs } from 'pinia'
import { useUiStore } from '@/stores/ui'
import { chartTheme, SLICE_COUNT } from '@/utils/palette'
import StatCard from '@/components/dashboard/StatCard.vue'
import PeriodPicker from '@/components/dashboard/PeriodPicker.vue'
import IncomeExpenseChart from '@/components/dashboard/IncomeExpenseChart.vue'
import ExpenseBreakdown from '@/components/dashboard/ExpenseBreakdown.vue'
import RecentTransactions from '@/components/dashboard/RecentTransactions.vue'
import TransactionFormModal from '@/components/transactions/TransactionFormModal.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const { data, loading, params, errors, load, setPeriod } = useDashboard()
const categories = useCategoriesStore()
categories.load().catch(() => {})

const showForm = ref(false)

const totals = computed(() => data.value?.totals)
const isEmptyPeriod = computed(() => totals.value?.transactions_count === 0)

// Colour follows the category, not its rank: index in the user's expense list (stable per period).
const { resolvedTheme } = storeToRefs(useUiStore())
function colorFor(categoryId) {
  const slices = chartTheme(resolvedTheme.value).slices
  const index = categories.expense.findIndex((c) => c.id === categoryId)
  return slices[index === -1 ? SLICE_COUNT - 1 : index % SLICE_COUNT]
}

const transactionsLink = computed(() => ({
  name: 'transactions',
  query: data.value ? { date_from: data.value.period.from, date_to: data.value.period.to } : {},
}))

const countHint = (n) =>
  n === 1 ? '1 transaction in this period' : `${n} transactions in this period`
</script>

<template>
  <div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="page-title flex items-center gap-3">
          Dashboard
          <AppSpinner v-if="loading && data" class="h-4 w-4 text-gray-400" />
        </h1>
        <p class="mt-1 text-sm text-gray-500">Your money at a glance.</p>
      </div>
      <BaseButton @click="showForm = true">
        <AppIcon name="plus" class="h-4 w-4" />
        Add transaction
      </BaseButton>
    </header>

    <PeriodPicker
      :period="params.period"
      :range="data?.period"
      :errors="errors"
      :loading="loading"
      @change="setPeriod"
    />

    <!-- First load -->
    <div v-if="!data && loading" class="space-y-6" aria-busy="true">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="n in 4" :key="n" class="card h-[124px] animate-pulse bg-gray-100/60" />
      </div>
      <div class="grid gap-6 lg:grid-cols-5">
        <div class="card h-[380px] animate-pulse bg-gray-100/60 lg:col-span-3" />
        <div class="card h-[380px] animate-pulse bg-gray-100/60 lg:col-span-2" />
      </div>
    </div>

    <!-- Failed first load -->
    <div v-else-if="!data" class="card">
      <EmptyState
        icon="alert"
        title="We couldn't load your dashboard"
        message="Check your connection or the selected period, then try again."
      >
        <BaseButton variant="secondary" @click="load">
          <AppIcon name="refresh" class="h-4 w-4" />
          Try again
        </BaseButton>
      </EmptyState>
    </div>

    <div
      v-else
      :class="['space-y-6 transition-opacity', loading && 'pointer-events-none opacity-60']"
    >
      <!-- DSH: period totals -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Totals">
        <StatCard
          label="Income"
          :value="`+${formatMoney(totals.income)}`"
          icon="income"
          tone="income"
          hint="Money in this period"
        />
        <StatCard
          label="Expenses"
          :value="`−${formatMoney(totals.expenses)}`"
          icon="expense"
          tone="expense"
          hint="Money out this period"
        />
        <StatCard
          label="Balance"
          :value="formatMoney(totals.balance)"
          icon="wallet"
          :negative="isNegative(totals.balance)"
          :hint="countHint(totals.transactions_count)"
        />
        <StatCard
          label="All-time balance"
          :value="formatMoney(data.all_time_balance)"
          icon="clock"
          :negative="isNegative(data.all_time_balance)"
          hint="Across all your transactions"
        />
      </section>

      <!-- CHT: charts -->
      <section class="grid gap-6 lg:grid-cols-5">
        <div class="card min-w-0 p-5 lg:col-span-3">
          <div class="mb-1 flex items-baseline justify-between gap-2">
            <h2 class="font-semibold text-gray-900">Income vs expenses</h2>
            <span class="text-xs text-gray-500">
              Per {{ data.income_vs_expenses.granularity }}
            </span>
          </div>
          <EmptyState
            v-if="isEmptyPeriod"
            icon="chart"
            title="No transactions in this period"
            message="Add a transaction or pick another period to see the chart."
          />
          <IncomeExpenseChart
            v-else
            :points="data.income_vs_expenses.points"
            :granularity="data.income_vs_expenses.granularity"
          />
        </div>

        <div class="card min-w-0 p-5 lg:col-span-2">
          <h2 class="mb-5 font-semibold text-gray-900">Spending by category</h2>
          <EmptyState
            v-if="data.expense_by_category.length === 0"
            icon="pie"
            title="No expenses in this period"
            message="Your spending breakdown will appear here."
          />
          <ExpenseBreakdown
            v-else
            :items="data.expense_by_category"
            :total="totals.expenses"
            :color-for="colorFor"
          />
        </div>
      </section>

      <!-- DSH: recent transactions -->
      <section class="card p-5">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Recent transactions</h2>
          <RouterLink
            v-if="data.recent_transactions.length"
            :to="transactionsLink"
            class="text-sm font-medium text-primary hover:underline"
          >
            View all
          </RouterLink>
        </div>
        <EmptyState
          v-if="data.recent_transactions.length === 0"
          icon="receipt"
          title="Nothing recorded yet"
          message="Transactions you add in this period will show up here."
        >
          <BaseButton size="sm" @click="showForm = true">
            <AppIcon name="plus" class="h-4 w-4" />
            Add transaction
          </BaseButton>
        </EmptyState>
        <RecentTransactions v-else :items="data.recent_transactions" class="mt-2" />
      </section>
    </div>

    <TransactionFormModal :open="showForm" @close="showForm = false" @saved="load" />
  </div>
</template>
