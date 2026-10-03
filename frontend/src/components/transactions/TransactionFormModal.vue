<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import { useCategoriesStore } from '@/stores/categories'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from '@/composables/useFormErrors'
import { createTransaction, updateTransaction } from '@/api/transactions'
import { currency } from '@/utils/money'
import { toIsoDate, tomorrowIso } from '@/utils/dates'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  transaction: { type: Object, default: null }, // null → create
})

const emit = defineEmits(['close', 'saved'])

const categories = useCategoriesStore()
const ui = useUiStore()
const { capture, clear, first } = useFormErrors()
const formId = useId()

const form = reactive({
  type: 'expense',
  amount: '',
  category_id: '',
  transaction_date: toIsoDate(),
  description: '',
})
const saving = ref(false)

const isEdit = computed(() => props.transaction !== null)
const options = computed(() => categories.ofType(form.type))

const typeOptions = [
  { value: 'expense', label: 'Expense', tone: 'expense' },
  { value: 'income', label: 'Income', tone: 'income' },
]

watch(
  () => props.open,
  (open) => {
    if (!open) return
    clear()
    categories.load().catch((error) => ui.fail(error, 'Could not load categories.'))
    const t = props.transaction
    Object.assign(form, {
      type: t?.type ?? 'expense',
      amount: t?.amount ?? '',
      category_id: t ? String(t.category_id) : '',
      transaction_date: t?.transaction_date ?? toIsoDate(),
      description: t?.description ?? '',
    })
  },
)

// BR-02: a category must match the transaction type — drop a selection that no longer fits.
watch(
  () => form.type,
  () => {
    if (form.category_id && !options.value.some((c) => String(c.id) === form.category_id)) {
      form.category_id = ''
    }
    clear('category_id')
  },
)

async function submit() {
  saving.value = true
  clear()
  const payload = {
    type: form.type,
    amount: String(form.amount).trim(),
    category_id: form.category_id ? Number(form.category_id) : null,
    transaction_date: form.transaction_date,
    description: form.description.trim() || null, // BR-10
  }
  try {
    const saved = isEdit.value
      ? await updateTransaction(props.transaction.id, payload)
      : await createTransaction(payload)
    ui.success(isEdit.value ? 'Transaction updated.' : 'Transaction added.')
    emit('saved', saved)
    emit('close')
  } catch (error) {
    // 422 keeps the modal open with its values and shows errors under each field.
    if (!capture(error)) ui.fail(error, 'Could not save the transaction.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit transaction' : 'Add transaction'"
    @close="!saving && emit('close')"
  >
    <form :id="formId" class="space-y-4" novalidate @submit.prevent="submit">
      <div>
        <span class="field-label">Type</span>
        <SegmentedControl v-model="form.type" :options="typeOptions" label="Type" block />
        <p v-if="first('type')" class="field-error">{{ first('type') }}</p>
      </div>

      <BaseInput
        v-model="form.amount"
        :label="`Amount (${currency})`"
        inputmode="decimal"
        placeholder="0.00"
        autocomplete="off"
        autofocus
        :error="first('amount')"
        @input="clear('amount')"
      />

      <BaseSelect
        v-model="form.category_id"
        label="Category"
        :error="first('category_id')"
        :disabled="categories.loading && !categories.loaded"
        @change="clear('category_id')"
      >
        <option value="" disabled>Select a category</option>
        <option v-for="c in options" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        <template v-if="categories.loaded && options.length === 0" #hint>
          You have no {{ form.type }} categories yet.
          <RouterLink :to="{ name: 'categories' }" class="font-medium text-primary hover:underline">
            Create one
          </RouterLink>
        </template>
      </BaseSelect>

      <BaseInput
        v-model="form.transaction_date"
        label="Date"
        type="date"
        min="2000-01-01"
        :max="tomorrowIso()"
        :error="first('transaction_date')"
        @input="clear('transaction_date')"
      />

      <BaseInput
        v-model="form.description"
        label="Description (optional)"
        multiline
        rows="2"
        maxlength="255"
        placeholder="e.g. Groceries at the market"
        :error="first('description')"
        @input="clear('description')"
      />
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Cancel</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Save changes' : 'Add transaction' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
