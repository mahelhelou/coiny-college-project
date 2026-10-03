<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from '@/composables/useFormErrors'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const { capture, clear, first } = useFormErrors()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const submitting = ref(false)

async function submit() {
  submitting.value = true
  clear()
  try {
    await auth.register(form)
    ui.success(`Welcome to Coiny, ${auth.user.name}!`)
    router.replace({ name: 'dashboard' })
  } catch (error) {
    if (!capture(error)) ui.fail(error, 'Could not create your account.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="card p-6 shadow-sm sm:p-8">
      <h1 class="text-center text-xl font-semibold tracking-tight text-gray-900">
        Create your account
      </h1>
      <p class="mt-1 text-center text-sm text-gray-500">
        We'll set you up with a starter list of categories you can change any time.
      </p>

      <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
        <BaseInput
          v-model="form.name"
          label="Name"
          autocomplete="name"
          maxlength="100"
          required
          :error="first('name')"
          @input="clear('name')"
        />
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
          autocomplete="new-password"
          hint="At least 8 characters."
          required
          :error="first('password')"
          @input="clear('password')"
        />
        <BaseInput
          v-model="form.password_confirmation"
          label="Confirm password"
          type="password"
          autocomplete="new-password"
          required
          :error="first('password_confirmation')"
          @input="clear('password_confirmation')"
        />
        <BaseButton type="submit" block :loading="submitting">Create account</BaseButton>
      </form>
    </div>

    <p class="mt-6 text-center text-sm text-gray-500">
      Already have an account?
      <RouterLink :to="{ name: 'login' }" class="font-medium text-primary hover:underline">
        Log in
      </RouterLink>
    </p>
  </div>
</template>
