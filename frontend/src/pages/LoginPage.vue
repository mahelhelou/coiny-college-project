<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from '@/composables/useFormErrors'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const { errors, capture, clear, first } = useFormErrors()

const form = reactive({ email: '', password: '' })
const submitting = ref(false)
const expired = route.query.expired === '1'

function safeRedirect() {
  const target = route.query.redirect
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
    ? target
    : { name: 'dashboard' }
}

async function submit() {
  submitting.value = true
  clear()
  try {
    await auth.login(form)
    router.replace(safeRedirect())
  } catch (error) {
    if (error.response?.status === 429) {
      // BR-15: 5 attempts per minute.
      errors.value = { email: ['Too many login attempts. Please wait a minute and try again.'] }
    } else if (!capture(error)) {
      ui.fail(error, 'Could not log in.')
    }
    form.password = ''
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="card p-6 shadow-sm sm:p-8">
      <h1 class="text-center text-xl font-semibold tracking-tight text-gray-900">Welcome back</h1>
      <p class="mt-1 text-center text-sm text-gray-500">
        Log in to see your balance and transactions.
      </p>

      <div
        v-if="expired"
        class="mt-6 flex items-start gap-3 rounded-lg border border-expense/30 bg-expense/5 p-3 text-sm text-gray-700"
        role="status"
      >
        <AppIcon name="clock" class="text-expense" />
        Your session has expired. Please log in again.
      </div>

      <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
        <BaseInput
          v-model="form.email"
          label="Email"
          type="email"
          autocomplete="email"
          placeholder="you@example.com"
          required
          :error="first('email')"
          @input="clear('email')"
        />
        <BaseInput
          v-model="form.password"
          label="Password"
          type="password"
          autocomplete="current-password"
          required
          :error="first('password')"
          @input="clear('password')"
        />
        <BaseButton type="submit" block :loading="submitting">Log in</BaseButton>
      </form>
    </div>

    <p class="mt-6 text-center text-sm text-gray-500">
      New to Coiny?
      <RouterLink :to="{ name: 'register' }" class="font-medium text-primary hover:underline">
        Create an account
      </RouterLink>
    </p>
  </div>
</template>
