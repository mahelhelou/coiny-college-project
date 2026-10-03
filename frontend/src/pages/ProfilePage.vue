<script setup>
import { reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useFormErrors } from '@/composables/useFormErrors'
import { updatePassword } from '@/api/auth'
import { formatMonthYear } from '@/utils/dates'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ThemeSwitcher from '@/components/ui/ThemeSwitcher.vue'

const auth = useAuthStore()
const ui = useUiStore()
const { user } = storeToRefs(auth)

// PRF-01: display name
const profile = reactive({ name: user.value?.name ?? '' })
const profileErrors = useFormErrors()
const savingProfile = ref(false)

async function saveProfile() {
  savingProfile.value = true
  profileErrors.clear()
  try {
    await auth.updateProfile({ name: profile.name.trim() })
    profile.name = user.value.name
    ui.success('Profile updated.')
  } catch (error) {
    if (!profileErrors.capture(error)) ui.fail(error, 'Could not update your profile.')
  } finally {
    savingProfile.value = false
  }
}

// PRF-02: password
const password = reactive({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = useFormErrors()
const savingPassword = ref(false)

async function savePassword() {
  savingPassword.value = true
  passwordErrors.clear()
  try {
    await updatePassword(password)
    Object.assign(password, { current_password: '', password: '', password_confirmation: '' })
    ui.success('Password changed.')
  } catch (error) {
    if (!passwordErrors.capture(error)) ui.fail(error, 'Could not change your password.')
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <header>
      <h1 class="page-title">Profile</h1>
      <p class="mt-1 text-sm text-gray-500">Manage your account details and password.</p>
    </header>

    <section class="card">
      <div class="border-b border-border px-5 py-4">
        <h2 class="font-semibold text-gray-900">Account</h2>
        <p class="mt-0.5 text-sm text-gray-500">
          Member since {{ formatMonthYear(user?.created_at) }}
        </p>
      </div>
      <form class="space-y-4 px-5 py-5" novalidate @submit.prevent="saveProfile">
        <BaseInput
          v-model="profile.name"
          label="Name"
          autocomplete="name"
          maxlength="100"
          :error="profileErrors.first('name')"
          @input="profileErrors.clear('name')"
        />
        <BaseInput
          :model-value="user?.email"
          label="Email"
          type="email"
          disabled
          hint="Your email address can't be changed."
        />
        <div class="flex justify-end">
          <BaseButton type="submit" :loading="savingProfile">Save changes</BaseButton>
        </div>
      </form>
    </section>

    <section class="card">
      <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="font-semibold text-gray-900">Appearance</h2>
          <p class="mt-0.5 text-sm text-gray-500">
            System follows your device setting. Saved in this browser.
          </p>
        </div>
        <div class="sm:w-72">
          <ThemeSwitcher labels />
        </div>
      </div>
    </section>

    <section class="card">
      <div class="flex items-center gap-3 border-b border-border px-5 py-4">
        <span
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
        >
          <AppIcon name="lock" class="h-4 w-4" />
        </span>
        <div>
          <h2 class="font-semibold text-gray-900">Change password</h2>
          <p class="text-sm text-gray-500">Use at least 8 characters.</p>
        </div>
      </div>
      <form class="space-y-4 px-5 py-5" novalidate @submit.prevent="savePassword">
        <BaseInput
          v-model="password.current_password"
          label="Current password"
          type="password"
          autocomplete="current-password"
          :error="passwordErrors.first('current_password')"
          @input="passwordErrors.clear('current_password')"
        />
        <div class="grid gap-4 sm:grid-cols-2">
          <BaseInput
            v-model="password.password"
            label="New password"
            type="password"
            autocomplete="new-password"
            :error="passwordErrors.first('password')"
            @input="passwordErrors.clear('password')"
          />
          <BaseInput
            v-model="password.password_confirmation"
            label="Confirm new password"
            type="password"
            autocomplete="new-password"
            :error="passwordErrors.first('password_confirmation')"
            @input="passwordErrors.clear('password_confirmation')"
          />
        </div>
        <div class="flex justify-end">
          <BaseButton type="submit" :loading="savingPassword">Update password</BaseButton>
        </div>
      </form>
    </section>
  </div>
</template>
