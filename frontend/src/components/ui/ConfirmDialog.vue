<script setup>
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Delete' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <BaseModal :open="open" :title="title" @close="!loading && emit('cancel')">
    <p class="text-sm text-gray-600">
      <slot>{{ message }}</slot>
    </p>
    <template #footer>
      <BaseButton variant="secondary" :disabled="loading" @click="emit('cancel')">
        Cancel
      </BaseButton>
      <BaseButton variant="danger" :loading="loading" autofocus @click="emit('confirm')">
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
