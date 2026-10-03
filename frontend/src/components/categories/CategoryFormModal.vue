<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import { useCategoriesStore } from '@/stores/categories'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from '@/composables/useFormErrors'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  category: { type: Object, default: null }, // null → create
  defaultType: { type: String, default: 'expense' },
})

const emit = defineEmits(['close'])

const categories = useCategoriesStore()
const ui = useUiStore()
const { capture, clear, first } = useFormErrors()
const formId = useId()

const form = reactive({ name: '', type: 'expense' })
const saving = ref(false)
const isEdit = computed(() => props.category !== null)

const typeOptions = [
  { value: 'expense', label: 'Expense', tone: 'expense' },
  { value: 'income', label: 'Income', tone: 'income' },
]

watch(
  () => props.open,
  (open) => {
    if (!open) return
    clear()
    form.name = props.category?.name ?? ''
    form.type = props.category?.type ?? props.defaultType
  },
)

async function submit() {
  saving.value = true
  clear()
  try {
    if (isEdit.value) {
      // BR-04: the type is immutable — only the name is sent.
      await categories.update(props.category.id, { name: form.name.trim() })
      ui.success('Category renamed.')
    } else {
      await categories.create({ name: form.name.trim(), type: form.type })
      ui.success('Category created.')
    }
    emit('close')
  } catch (error) {
    if (!capture(error)) ui.fail(error, 'Could not save the category.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Rename category' : 'New category'"
    @close="!saving && emit('close')"
  >
    <form :id="formId" class="space-y-4" novalidate @submit.prevent="submit">
      <div>
        <span class="field-label">Type</span>
        <SegmentedControl
          v-model="form.type"
          :options="typeOptions"
          label="Category type"
          :disabled="isEdit"
          block
        />
        <p v-if="isEdit" class="mt-1.5 text-xs text-gray-500">
          The type can't be changed after a category is created.
        </p>
        <p v-if="first('type')" class="field-error">{{ first('type') }}</p>
      </div>
      <BaseInput
        v-model="form.name"
        label="Name"
        maxlength="50"
        placeholder="e.g. Groceries"
        autofocus
        :error="first('name')"
        @input="clear('name')"
      />
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Cancel</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Save' : 'Create category' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
