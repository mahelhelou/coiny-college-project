<script setup>
import { ref } from 'vue'
import { useTransactions } from '@/composables/useTransactions'
import { useCategoriesStore } from '@/stores/categories'
import { useUiStore } from '@/stores/ui'
import { deleteTransaction } from '@/api/transactions'
import { formatSigned } from '@/utils/money'
import { formatDate } from '@/utils/dates'
import TransactionFilters from '@/components/transactions/TransactionFilters.vue'
import TransactionList from '@/components/transactions/TransactionList.vue'
import TransactionFormModal from '@/components/transactions/TransactionFormModal.vue'
import PaginationBar from '@/components/ui/PaginationBar.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const {
  items,
  meta,
  loading,
  loaded,
  filters,
  hasFilters,
  fetch,
  setFilters,
  setPage,
  clearFilters,
} = useTransactions()
const categories = useCategoriesStore()
const ui = useUiStore()

categories.load().catch((error) => ui.fail(error, 'Could not load categories.'))

const formOpen = ref(false)
const editing = ref(null)
const deleting = ref(null)
const deletingBusy = ref(false)

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(trx) {
  editing.value = trx
  formOpen.value = true
}

// BR-08: hard delete after confirmation.
async function confirmDelete() {
  deletingBusy.value = true
  try {
    await deleteTransaction(deleting.value.id)
    ui.success('Transaction deleted.')
    deleting.value = null
    // Step back a page if we just emptied the last one.
    if (items.value.length === 1 && meta.value.current_page > 1) {
      setPage(meta.value.current_page - 1)
    } else {
      fetch()
    }
  } catch (error) {
    ui.fail(error, 'Could not delete the transaction.')
  } finally {
    deletingBusy.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="page-title flex items-center gap-3">
          Transactions
          <AppSpinner v-if="loading && loaded" class="h-4 w-4 text-gray-400" />
        </h1>
        <p class="mt-1 text-sm text-gray-500">
          <template v-if="meta">
            {{ meta.total }} {{ meta.total === 1 ? 'transaction' : 'transactions' }}
            {{ hasFilters ? 'match your filters' : 'recorded' }}
          </template>
          <template v-else>Everything you've recorded.</template>
        </p>
      </div>
      <BaseButton @click="openCreate">
        <AppIcon name="plus" class="h-4 w-4" />
        Add transaction
      </BaseButton>
    </header>

    <TransactionFilters
      :filters="filters"
      :has-filters="hasFilters"
      @change="setFilters"
      @clear="clearFilters"
    />

    <div class="card overflow-hidden">
      <div
        v-if="!loaded && loading"
        class="flex justify-center py-16 text-primary"
        aria-busy="true"
      >
        <AppSpinner class="h-6 w-6" />
      </div>

      <EmptyState
        v-else-if="!loaded"
        icon="alert"
        title="We couldn't load your transactions"
        message="Check your connection and try again."
      >
        <BaseButton variant="secondary" @click="fetch">
          <AppIcon name="refresh" class="h-4 w-4" />
          Try again
        </BaseButton>
      </EmptyState>

      <EmptyState
        v-else-if="items.length === 0 && hasFilters"
        icon="search"
        title="No matching transactions"
        message="Try a different category, date range or search."
      >
        <BaseButton variant="secondary" @click="clearFilters">Clear filters</BaseButton>
      </EmptyState>

      <EmptyState
        v-else-if="items.length === 0"
        icon="receipt"
        title="No transactions yet"
        message="Record your first income or expense to start tracking your balance."
      >
        <BaseButton @click="openCreate">
          <AppIcon name="plus" class="h-4 w-4" />
          Add transaction
        </BaseButton>
      </EmptyState>

      <div v-else :class="['transition-opacity', loading && 'opacity-60']">
        <TransactionList :items="items" @edit="openEdit" @delete="deleting = $event" />
      </div>
    </div>

    <PaginationBar
      v-if="meta && meta.total > 0"
      :meta="meta"
      :per-page="filters.per_page"
      :disabled="loading"
      @page="setPage"
      @per-page="setFilters({ per_page: $event === '15' ? undefined : $event })"
    />

    <TransactionFormModal
      :open="formOpen"
      :transaction="editing"
      @close="formOpen = false"
      @saved="fetch"
    />

    <ConfirmDialog
      :open="deleting !== null"
      title="Delete transaction?"
      :loading="deletingBusy"
      @cancel="deleting = null"
      @confirm="confirmDelete"
    >
      <template v-if="deleting">
        This will permanently delete the
        <strong class="font-semibold text-gray-900">
          {{ formatSigned(deleting.amount, deleting.type) }}
        </strong>
        {{ deleting.category?.name }} transaction from {{ formatDate(deleting.transaction_date) }}.
        This can't be undone.
      </template>
    </ConfirmDialog>
  </div>
</template>
