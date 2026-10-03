<script setup>
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCategoriesStore } from '@/stores/categories'
import { useUiStore } from '@/stores/ui'
import CategoryGroup from '@/components/categories/CategoryGroup.vue'
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const store = useCategoriesStore()
const ui = useUiStore()
const { income, expense, loaded, loading } = storeToRefs(store)

// Refresh so transaction counts are current.
function load() {
  store.load({ force: true }).catch((error) => ui.fail(error, 'Could not load categories.'))
}
load()

const formOpen = ref(false)
const editing = ref(null)
const defaultType = ref('expense')
const deleting = ref(null)
const deletingBusy = ref(false)

function openCreate(type = 'expense') {
  editing.value = null
  defaultType.value = type
  formOpen.value = true
}

function openEdit(category) {
  editing.value = category
  formOpen.value = true
}

async function confirmDelete() {
  deletingBusy.value = true
  try {
    await store.remove(deleting.value.id)
    ui.success('Category deleted.')
    deleting.value = null
  } catch (error) {
    // BR-07: 409 when the category is in use — the message comes from the API.
    ui.fail(error, 'Could not delete the category.')
    if (error.response?.status === 409) {
      deleting.value = null
      load()
    }
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
          Categories
          <AppSpinner v-if="loading && loaded" class="h-4 w-4 text-gray-400" />
        </h1>
        <p class="mt-1 text-sm text-gray-500">
          Organise your transactions. Categories in use can be renamed but not deleted.
        </p>
      </div>
      <BaseButton @click="openCreate()">
        <AppIcon name="plus" class="h-4 w-4" />
        New category
      </BaseButton>
    </header>

    <div v-if="!loaded && loading" class="flex justify-center py-16 text-primary" aria-busy="true">
      <AppSpinner class="h-6 w-6" />
    </div>

    <div v-else-if="!loaded" class="card">
      <EmptyState icon="alert" title="We couldn't load your categories">
        <BaseButton variant="secondary" @click="load">
          <AppIcon name="refresh" class="h-4 w-4" />
          Try again
        </BaseButton>
      </EmptyState>
    </div>

    <div v-else class="grid items-start gap-6 lg:grid-cols-2">
      <CategoryGroup
        type="expense"
        :items="expense"
        @add="openCreate"
        @edit="openEdit"
        @delete="deleting = $event"
      />
      <CategoryGroup
        type="income"
        :items="income"
        @add="openCreate"
        @edit="openEdit"
        @delete="deleting = $event"
      />
    </div>

    <CategoryFormModal
      :open="formOpen"
      :category="editing"
      :default-type="defaultType"
      @close="formOpen = false"
    />

    <ConfirmDialog
      :open="deleting !== null"
      title="Delete category?"
      :loading="deletingBusy"
      @cancel="deleting = null"
      @confirm="confirmDelete"
    >
      <template v-if="deleting">
        <strong class="font-semibold text-gray-900">{{ deleting.name }}</strong> will be permanently
        removed.
      </template>
    </ConfirmDialog>
  </div>
</template>
